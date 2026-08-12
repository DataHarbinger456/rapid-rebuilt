<?php

namespace Tests\Concerns;

use App\Models\UserCase;
use App\Services\PDFGeneratorService;
use App\Support\PdftkBinary;
use Illuminate\Support\Facades\Storage;
use mikehaertl\pdftk\Pdf;
use ReflectionMethod;
use RuntimeException;

/**
 * Shared harness for testing an HSMV form's field map.
 *
 * Two different things are worth asserting about a filled form, and they catch
 * different bugs:
 *
 *   stampedValues()          the field => value set the generator hands to
 *                            pdftk. Cheap, and the right tool for "did this
 *                            vehicle tick the right box".
 *
 *   fieldValuesInFilledPdf() the values actually present inside a real filled
 *                            document. Slower, but the only thing that catches
 *                            a field NAME that does not exist in the template:
 *                            pdftk silently drops an unknown name, so the box
 *                            prints blank while every value-level assertion
 *                            still passes.
 *
 * Production flattens its output, which bakes values into the page and removes
 * the form fields, leaving nothing to read back. The readback here fills the
 * same template with the same values through the same pdftk binary and skips
 * only the flatten step.
 *
 * A using class names its form with formKey(). That is deliberately abstract
 * rather than defaulted to the 82040: a second form's suite that forgot to
 * override it would silently assert against the first form's map, which is the
 * exact class of silent mismatch this harness exists to catch.
 */
trait ReadsPdfFormFields
{
    /**
     * The config/hsmv_forms.php entry this suite exercises, e.g. 'hsmv_82040'.
     */
    abstract protected function formKey(): string;

    /**
     * @return array{template: string, fields: array<string, mixed>}
     */
    protected function formConfig(): array
    {
        $config = config('hsmv_forms.' . $this->formKey());

        if (!is_array($config)) {
            throw new RuntimeException(
                "No config/hsmv_forms.php entry for [{$this->formKey()}]. "
                . 'A form with no field map cannot be read back.'
            );
        }

        return $config;
    }

    /**
     * @return array<string, mixed> pdf field name => mapping
     */
    protected function mappedFields(): array
    {
        return $this->formConfig()['fields'] ?? [];
    }

    protected function formTemplatePath(): string
    {
        return base_path($this->formConfig()['template']);
    }

    /**
     * The exact field => value set the generator hands to pdftk, through the
     * real config, the real prepareFormData() and the real mapFields().
     *
     * Both are protected on the service, so this reaches them by reflection
     * rather than duplicating their logic, which would defeat the point.
     *
     * @return array<string, string>
     */
    protected function stampedValues(UserCase $case): array
    {
        $service = app(PDFGeneratorService::class);

        $prepare = new ReflectionMethod($service, 'prepareFormData');
        $map = new ReflectionMethod($service, 'mapFields');
        $prepare->setAccessible(true);
        $map->setAccessible(true);

        return $map->invoke(
            $service,
            $this->mappedFields(),
            $prepare->invoke($service, $case)
        );
    }

    /**
     * Fill the official template unflattened and read every field back out.
     *
     * @return array<string, string> pdf field name => value inside the document
     */
    protected function fieldValuesInFilledPdf(UserCase $case): array
    {
        $label = $this->formLabel();
        $out = tempnam(sys_get_temp_dir(), $label . '_') . '.pdf';

        $pdf = new Pdf(
            $this->formTemplatePath(),
            ['command' => PdftkBinary::path()]
        );

        $filled = $pdf
            ->fillForm($this->stampedValues($case))
            ->needAppearances()
            ->saveAs($out);

        $this->assertNotFalse($filled, "pdftk could not fill the {$label}: " . $pdf->getError());

        $dump = $this->dumpFormFields($out);

        @unlink($out);

        return $this->parseFieldValues($dump);
    }

    /**
     * Every field name in the blank template, as pdftk reports it.
     *
     * @return array<int, string>
     */
    protected function fieldNamesInTemplate(): array
    {
        return $this->parseFieldNames($this->dumpFormFields($this->formTemplatePath()));
    }

    /**
     * Raw `pdftk … dump_data_fields_utf8` output for a PDF on disk.
     */
    protected function dumpFormFields(string $absolutePath): string
    {
        return (string) shell_exec(
            escapeshellarg(PdftkBinary::path()) . ' '
            . escapeshellarg($absolutePath)
            . ' dump_data_fields_utf8'
        );
    }

    /**
     * A field with no FieldValue line reads as an empty string, so a caller can
     * assert "this box is blank" without special-casing a missing key.
     *
     * @return array<string, string>
     */
    protected function parseFieldValues(string $dump): array
    {
        $values = [];

        // Each record is a block separated by a line of dashes.
        foreach (preg_split('/\n(?=---)/', $dump) as $block) {
            if (!preg_match('/^FieldName: (.*)$/m', $block, $name)) {
                continue;
            }

            preg_match('/^FieldValue: (.*)$/m', $block, $value);

            $values[trim($name[1])] = isset($value[1]) ? trim($value[1]) : '';
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    protected function parseFieldNames(string $dump): array
    {
        preg_match_all('/^FieldName: (.*)$/m', $dump, $matches);

        return array_map('trim', $matches[1]);
    }

    /**
     * The text layer of a generated PDF on the private disk.
     *
     * Checkbox marks flatten into page graphics and leave no text, so only
     * typed values are assertable this way. Pass $layout for pdftotext's
     * -layout mode, which preserves column positions.
     */
    protected function textOfStoredPdf(string $storagePath, bool $layout = false): string
    {
        $absolute = Storage::disk('private')->path($storagePath);

        return (string) shell_exec(
            'pdftotext ' . ($layout ? '-layout ' : '') . escapeshellarg($absolute) . ' -'
        );
    }

    /**
     * 'hsmv_82040' reads as '82040' in a message or a temp file name.
     */
    protected function formLabel(): string
    {
        return str_replace('hsmv_', '', $this->formKey());
    }
}
