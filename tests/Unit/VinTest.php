<?php

namespace Tests\Unit;

use App\Support\Vin;
use PHPUnit\Framework\TestCase;

/**
 * Vin is pure, so this extends PHPUnit's TestCase directly rather than the
 * framework one — no database, no app boot.
 *
 * The fixtures are mostly not invented: except where noted, the valid VINs are
 * real ones transcribed from salvage titles, and the invalid ones are the
 * actual misreads a vision model produced from photos of those same titles.
 */
class VinTest extends TestCase
{
    /**
     * Real VINs, read off real titles and independently checksum-verified.
     */
    public static function validVins(): array
    {
        return [
            'Honda Accord' => ['1HGCY2F82SA070581'],
            'Honda Civic' => ['19XFC1F38LE000963'],
            'Toyota Prius' => ['JTDZN3EU9C3149404'],
            'Ford F-350' => ['2FTJW36K6RCA01057'],
            'Ford Explorer' => ['1FMSK8DH1LGB73874'],
            'Toyota Camry' => ['4T1C11AK7LU387042'],
            'Kia Optima' => ['5XXGN4A76DG222937'],
            'Hyundai Kona' => ['KMHK62AA8MU660081'],
            // Not from the sample set: the standard reference VIN for the
            // check-digit-is-X case, which a naive modulo implementation
            // renders as "10" and gets wrong.
            'checksum resolves to X' => ['1M8GDM9AXKP042788'],
        ];
    }

    /**
     * Misreads a vision model actually produced from the sample photos.
     */
    public static function invalidVins(): array
    {
        return [
            'transposed digits' => ['1FMSK8DH1LGB37874'],
            'letter for digit' => ['UTDZN3EU8C3148404'],
            'dropped character shifts the tail' => ['19XCC1F38L000963A'],
            'mangled middle' => ['KM8K62AA8M0660081'],
            'garbled throughout' => ['1GNRDED19E853E853'],
        ];
    }

    /**
     * @dataProvider validVins
     */
    public function test_a_real_vin_passes(string $vin): void
    {
        $this->assertTrue(Vin::isValid($vin), "{$vin} should pass its check digit.");
    }

    /**
     * @dataProvider invalidVins
     */
    public function test_a_misread_vin_fails(string $vin): void
    {
        $this->assertFalse(Vin::isValid($vin), "{$vin} should fail its check digit.");
    }

    public function test_it_is_case_and_whitespace_insensitive(): void
    {
        // Staff paste from the document; the value should not hinge on that.
        $this->assertTrue(Vin::isValid('  1hgcy2f82sa070581  '));
    }

    public function test_malformed_input_is_not_valid(): void
    {
        $this->assertFalse(Vin::isValid(null));
        $this->assertFalse(Vin::isValid(''));
        $this->assertFalse(Vin::isValid('   '));
        // Too short, too long.
        $this->assertFalse(Vin::isValid('1HGCY2F82SA07058'));
        $this->assertFalse(Vin::isValid('1HGCY2F82SA0705811'));
        // I, O and Q are not in the VIN alphabet.
        $this->assertFalse(Vin::isValid('1HGCY2F82SA07O581'));
        $this->assertFalse(Vin::isValid('IHGCY2F82SA070581'));
        $this->assertFalse(Vin::isValid('1HGCY2F82SA07Q581'));
        // Punctuation.
        $this->assertFalse(Vin::isValid('1HGCY2F82SA-70581'));
    }

    public function test_well_formed_separates_shape_from_checksum(): void
    {
        // 17 valid characters but the checksum disagrees: probably one misread
        // character, which is a different problem from "not a VIN".
        $this->assertTrue(Vin::isWellFormed('1FMSK8DH1LGB37874'));
        $this->assertFalse(Vin::isValid('1FMSK8DH1LGB37874'));

        $this->assertFalse(Vin::isWellFormed('NOT A VIN'));
        $this->assertFalse(Vin::isWellFormed(null));
    }

    public function test_it_reports_the_expected_check_digit(): void
    {
        // Position 9 of this VIN is 2 and it validates, so 2 is expected.
        $this->assertSame('2', Vin::expectedCheckDigit('1HGCY2F82SA070581'));
        $this->assertSame('X', Vin::expectedCheckDigit('1M8GDM9AXKP042788'));
        $this->assertNull(Vin::expectedCheckDigit('NOT A VIN'));
        $this->assertNull(Vin::expectedCheckDigit(null));
    }

    /**
     * The checksum catches the large majority of single-character misreads,
     * which is why it is worth running at all.
     */
    public function test_it_catches_almost_every_single_character_change(): void
    {
        [$mutations, $detected] = $this->mutate('5XXGN4A76DG222937');

        $this->assertGreaterThan(0.9, $detected / $mutations);
    }

    /**
     * ...but not all of them, and staff need to know that.
     *
     * ISO 3779 transliterates several characters to the same value (A=J=1,
     * B=K=S=2, C=L=T=3 ...), so swapping one for another leaves the sum
     * unchanged and the checksum blind. This is why a passing check digit is
     * not proof: in the live sample run, one model returned a wrong VIN that
     * validated cleanly.
     */
    public function test_same_transliteration_value_swaps_are_invisible(): void
    {
        // B and K both transliterate to 2, so this swap cannot be detected.
        $this->assertTrue(Vin::isValid('5XXGN4A76DG222937'));
        $this->assertTrue(Vin::isValid('5XXGN4A76DG22B937'));
        $this->assertTrue(Vin::isValid('5XXGN4A76DG22K937'));

        // A and J both transliterate to 1.
        $this->assertSame(
            Vin::expectedCheckDigit('1HGCY2F82SA070581'),
            Vin::expectedCheckDigit('1HGCY2F82SJ070581')
        );
    }

    /**
     * @return array{0: int, 1: int} [mutations tried, mutations detected]
     */
    private function mutate(string $vin): array
    {
        $mutations = 0;
        $detected = 0;

        foreach (str_split('ABCDEFGHJKLMNPRSTUVWXYZ0123456789') as $replacement) {
            foreach (range(0, 16) as $position) {
                if ($vin[$position] === $replacement) {
                    continue;
                }

                $mutated = $vin;
                $mutated[$position] = $replacement;
                $mutations++;

                if (! Vin::isValid($mutated)) {
                    $detected++;
                }
            }
        }

        return [$mutations, $detected];
    }
}
