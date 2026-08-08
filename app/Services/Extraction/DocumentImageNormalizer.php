<?php

namespace App\Services\Extraction;

use App\Models\UploadedFile;
use Illuminate\Support\Facades\Process;
use Prism\Prism\ValueObjects\Media\Image;
use RuntimeException;

/**
 * Turns an uploaded document into an Image the provider can read.
 *
 * Salvage title uploads accept image/* and PDF. Rather than branching on
 * provider-specific PDF support, a PDF's first page is rasterised with
 * pdftoppm (already a dependency of this repo's form tooling) so every
 * extractor sees exactly one kind of input. Uniform provider behaviour is
 * worth more than saving the shell-out.
 */
class DocumentImageNormalizer
{
    /**
     * Rendering resolution for rasterised PDFs. 200 DPI keeps a scanned title's
     * VIN legible without pushing the encoded image past provider size limits.
     */
    private const PDF_DPI = 200;

    private const RASTERIZE_TIMEOUT_SECONDS = 60;

    public function toImage(UploadedFile $file): Image
    {
        $disk = $file->disk ?? 'private';
        $path = $file->file_path ?? $file->path;

        if ($path === null || $path === '') {
            throw new RuntimeException("Uploaded file {$file->id} has no stored path.");
        }

        if ($file->is_pdf) {
            $pagePath = $this->rasterizeFirstPage($file);

            try {
                // fromLocalPath reads the bytes eagerly, so the temp page can
                // go as soon as the Image exists.
                return Image::fromLocalPath($pagePath, 'image/png');
            } finally {
                @unlink($pagePath);
            }
        }

        if (! $file->is_image) {
            throw new RuntimeException(
                "Cannot extract from {$file->mime_type}; only images and PDFs are supported."
            );
        }

        return Image::fromStoragePath(path: $path, diskName: $disk);
    }

    /**
     * Writes page 1 as a PNG in the system temp directory and returns its path.
     *
     * The source is copied out of the disk first because the private disk may
     * be S3 in production, where there is no local file for pdftoppm to read.
     */
    private function rasterizeFirstPage(UploadedFile $file): string
    {
        // tempnam() creates the file it names, so appending .pdf leaves that
        // empty original behind unless it is unlinked too.
        $sourceStub = tempnam(sys_get_temp_dir(), 'extraction_src_');
        $sourcePath = $sourceStub . '.pdf';
        file_put_contents($sourcePath, $file->getContent());

        // pdftoppm appends "-1.png" to the prefix it is given.
        $prefix = tempnam(sys_get_temp_dir(), 'extraction_page_');
        $outputPath = "{$prefix}-1.png";

        try {
            $result = Process::timeout(self::RASTERIZE_TIMEOUT_SECONDS)->run([
                'pdftoppm', '-png', '-r', (string) self::PDF_DPI, '-f', '1', '-l', '1',
                $sourcePath, $prefix,
            ]);

            if ($result->failed()) {
                throw new RuntimeException(
                    "Could not rasterise PDF for uploaded file {$file->id}: "
                    . trim($result->errorOutput())
                );
            }
        } finally {
            @unlink($sourcePath);
            @unlink($sourceStub);
            // tempnam() created the prefix as an empty file; pdftoppm writes
            // alongside it rather than into it.
            @unlink($prefix);
        }

        if (! is_file($outputPath)) {
            throw new RuntimeException("pdftoppm produced no page image for uploaded file {$file->id}.");
        }

        return $outputPath;
    }
}
