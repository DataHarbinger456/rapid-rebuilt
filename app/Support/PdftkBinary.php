<?php

namespace App\Support;

/**
 * Works out which pdftk this host should use.
 *
 * Shared by the form generator and the preflight check so the diagnostics page
 * can never disagree with what actually runs. Resolution order and reasoning
 * are documented in config/pdftk.php.
 */
final class PdftkBinary
{
    public const SOURCE_CONFIGURED = 'configured';
    public const SOURCE_BUNDLED = 'bundled';
    public const SOURCE_SYSTEM = 'system';

    /**
     * The command the generator should run.
     */
    public static function path(): string
    {
        return self::resolve()['path'];
    }

    /**
     * Which of the three candidates won, so the preflight page can say so.
     * A deployment that silently fell back to a system binary looks identical
     * to one using the bundled install unless someone reports the difference.
     *
     * @return array{path: string, source: string}
     */
    public static function resolve(): array
    {
        $configured = config('pdftk.binary');

        if (is_string($configured) && trim($configured) !== '') {
            return ['path' => trim($configured), 'source' => self::SOURCE_CONFIGURED];
        }

        $bundled = config('pdftk.bundled_path');

        // Executable, not merely present: a half-finished install should fall
        // through to a working system binary rather than break the host.
        if (is_string($bundled) && $bundled !== '' && is_file($bundled) && is_executable($bundled)) {
            return ['path' => $bundled, 'source' => self::SOURCE_BUNDLED];
        }

        return ['path' => (string) config('pdftk.fallback', 'pdftk'), 'source' => self::SOURCE_SYSTEM];
    }

    /**
     * Human label for the resolved source, for the preflight page.
     */
    public static function describeSource(string $source): string
    {
        return match ($source) {
            self::SOURCE_CONFIGURED => 'PDFTK_BINARY override',
            self::SOURCE_BUNDLED => 'bundled in vendor-bin/pdftk',
            default => 'system PATH',
        };
    }
}
