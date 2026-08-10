<?php

namespace App\Services\Preflight;

/**
 * The outcome of one production dependency check.
 *
 * Severity is deliberately three-valued rather than a boolean. Some
 * dependencies stop a feature dead (no pdftk means no state forms at all);
 * others only degrade it (no NHTSA means the VIN cross-check falls back to the
 * soft notice it was designed to show). Collapsing those into pass/fail would
 * make a working deployment look broken.
 */
readonly class PreflightCheck
{
    public const PASS = 'pass';

    /** Broken, and something a user can reach is now dead. */
    public const FAIL = 'fail';

    /** Degraded, but the feature still works in its designed fallback. */
    public const WARN = 'warn';

    /**
     * @param  string  $name  short label, e.g. 'pdftk'
     * @param  string  $status  one of PASS, FAIL, WARN
     * @param  string  $detail  what was actually found — never a secret value
     * @param  string  $impact  what stops working, in the operator's terms
     * @param  string|null  $remedy  how to fix it, null when passing
     */
    public function __construct(
        public string $name,
        public string $status,
        public string $detail,
        public string $impact = '',
        public ?string $remedy = null,
    ) {}

    public static function pass(string $name, string $detail): self
    {
        return new self($name, self::PASS, $detail);
    }

    public static function fail(string $name, string $detail, string $impact, string $remedy): self
    {
        return new self($name, self::FAIL, $detail, $impact, $remedy);
    }

    public static function warn(string $name, string $detail, string $impact, string $remedy): self
    {
        return new self($name, self::WARN, $detail, $impact, $remedy);
    }

    public function passed(): bool
    {
        return $this->status === self::PASS;
    }

    public function failed(): bool
    {
        return $this->status === self::FAIL;
    }

    /**
     * The word shown beside the icon. Status is never carried by colour alone:
     * a red dot and a green dot are the same dot to a colourblind operator, and
     * this page exists to be read under time pressure.
     */
    public function label(): string
    {
        return match ($this->status) {
            self::PASS => 'Pass',
            self::WARN => 'Degraded',
            default => 'Fail',
        };
    }

    /**
     * Feather icon name, matching the icon set the admin theme already loads.
     */
    public function icon(): string
    {
        return match ($this->status) {
            self::PASS => 'check-circle',
            self::WARN => 'alert-triangle',
            default => 'x-circle',
        };
    }

    /**
     * Bootstrap badge classes. Solid fills with a deliberate text colour:
     * bg-warning is a light amber that fails contrast against white, so it
     * takes dark text while the other two take light.
     */
    public function badgeClass(): string
    {
        return match ($this->status) {
            self::PASS => 'bg-success text-white',
            self::WARN => 'bg-warning text-dark',
            default => 'bg-danger text-white',
        };
    }
}
