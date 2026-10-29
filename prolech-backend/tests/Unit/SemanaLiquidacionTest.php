<?php

namespace Tests\Unit;

use App\Models\Acopio;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** La semana de liquidación va de jueves a miércoles y debe contener a "hoy" cualquier día. */
class SemanaLiquidacionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function dias(): array
    {
        return [
            'jueves'    => ['2026-10-22', '2026-10-22', '2026-10-28'],
            'sábado'    => ['2026-10-24', '2026-10-22', '2026-10-28'],
            'domingo'   => ['2026-10-25', '2026-10-22', '2026-10-28'],
            'lunes'     => ['2026-10-26', '2026-10-22', '2026-10-28'],
            'miércoles' => ['2026-10-28', '2026-10-22', '2026-10-28'],
        ];
    }

    #[DataProvider('dias')]
    public function test_la_semana_contiene_el_dia_de_hoy(string $hoy, string $inicio, string $fin): void
    {
        Carbon::setTestNow(Carbon::parse("$hoy 10:00"));

        $this->assertSame([$inicio, $fin], Acopio::rangoSemanaLiquidacion());
    }
}
