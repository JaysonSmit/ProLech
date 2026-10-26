<?php

namespace Tests\Feature;

use App\Models\ProductoTienda;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Botón «Publicar» de Recetas: el modal y su script deben llegar a la página. */
class PublicarRecetaTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdmin(): static
    {
        $admin = User::factory()->create(['rol' => 'admin', 'activo' => true]);
        return $this->actingAs($admin)->withSession(['2fa_verified' => true]);
    }

    public function test_la_pagina_de_recetas_incluye_el_script_del_modal_publicar(): void
    {
        Receta::create(['nombre' => 'Mantequilla con Sal 250g', 'rendimiento_esperado_unidades' => 1, 'activa' => true]);

        $this->comoAdmin()->get('/admin/recetas')->assertOk()
            ->assertSee('abrirModalPublicar(', false)
            ->assertSee('function abrirModalPublicar', false);   // antes faltaba: el layout no tenía @stack('scripts')
    }

    public function test_publicar_crea_el_producto_en_la_tienda(): void
    {
        $receta = Receta::create(['nombre' => 'Mantequilla con Sal 250g', 'descripcion' => 'Con sal', 'rendimiento_esperado_unidades' => 1, 'activa' => true]);

        $this->comoAdmin()->post('/admin/recetas/publicar', ['receta_id' => $receta->id, 'precio_venta' => 9.5])
            ->assertRedirect(route('admin.recetas.index'))->assertSessionHas('success');

        $producto = ProductoTienda::where('receta_id', $receta->id)->sole();
        $this->assertSame('Mantequilla con Sal 250g', $producto->nombre);
        $this->assertEquals(9.5, (float) $producto->precio_venta);
        $this->assertSame('activo', $producto->estado);
    }
}
