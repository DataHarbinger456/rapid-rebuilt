<?php

namespace App\Support;

use App\Models\UserCase;
use InvalidArgumentException;

/**
 * Resolves which documents a case must supply, from config/documents.php.
 *
 * The list varies by service_type, so it cannot live in a class constant, and
 * a case is not always in scope (the creation wizard picks the service type
 * after the document step is rendered) — hence the two entry points.
 */
final class DocumentRequirements
{
    private const FALLBACK_KEY = 'default';

    /**
     * @return array<string, DocumentRequirement> keyed by document type
     */
    public static function forCase(UserCase $case): array
    {
        return self::forServiceType($case->service_type);
    }

    /**
     * Unknown service types — and the no-case-yet creation wizard — fall back to
     * the default list, so a service type added to UserCase but not yet to
     * config/documents.php still resolves to the full set rather than nothing.
     *
     * @return array<string, DocumentRequirement> keyed by document type
     */
    public static function forServiceType(?string $serviceType): array
    {
        $lists = config('documents.requirements', []);
        $list = $lists[$serviceType] ?? $lists[self::FALLBACK_KEY] ?? ['required' => [], 'optional' => []];

        $requirements = [];

        foreach ($list['required'] ?? [] as $type) {
            $requirements[$type] = self::build($type, true);
        }

        foreach ($list['optional'] ?? [] as $type) {
            // A type named in both lists is required — the stricter rule wins.
            if (!isset($requirements[$type])) {
                $requirements[$type] = self::build($type, false);
            }
        }

        return $requirements;
    }

    /**
     * The full catalogue of customer-supplied document types, requiredness
     * excluded. Uploads validate against this, not against a case's list.
     *
     * @return array<string, array{label: string, description: string, accept: string, multiple: bool}>
     */
    public static function catalogue(): array
    {
        return config('documents.types', []);
    }

    public static function isKnownType(string $type): bool
    {
        return array_key_exists($type, self::catalogue());
    }

    /**
     * Human label for any document type, falling back to the raw key so a
     * timeline entry never renders blank for a type outside the catalogue.
     */
    public static function label(string $type): string
    {
        return self::catalogue()[$type]['label'] ?? $type;
    }

    private static function build(string $type, bool $required): DocumentRequirement
    {
        $meta = self::catalogue()[$type] ?? null;

        if ($meta === null) {
            throw new InvalidArgumentException(
                "config/documents.php requires unknown document type [{$type}]; add it to the 'types' catalogue."
            );
        }

        return new DocumentRequirement(
            type: $type,
            label: $meta['label'] ?? $type,
            description: $meta['description'] ?? '',
            accept: $meta['accept'] ?? 'image/*,.pdf',
            multiple: (bool) ($meta['multiple'] ?? false),
            required: $required,
        );
    }
}
