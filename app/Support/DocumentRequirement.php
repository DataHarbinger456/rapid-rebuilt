<?php

namespace App\Support;

/**
 * One document a case has to supply: the catalogue metadata for a document
 * type, plus whether this particular case's service type demands it.
 *
 * Requiredness is not a property of the type — a re-inspection needs repair
 * photos but not the salvage title — so it is carried here rather than in the
 * catalogue.
 */
final class DocumentRequirement
{
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $description,
        public readonly string $accept,
        public readonly bool $multiple,
        public readonly bool $required,
    ) {
    }

    /**
     * The array shape the Blade views and the API have always consumed.
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'description' => $this->description,
            'required' => $this->required,
            'accept' => $this->accept,
            'multiple' => $this->multiple,
        ];
    }
}
