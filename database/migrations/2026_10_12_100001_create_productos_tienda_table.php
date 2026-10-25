<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_tienda', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150);
            $table->string('slug', 160)->unique()
                  ->comment('URL-friendly: generado desde nombre');
            $table->text('descripcion')->nullable();

            // Precio y stock
            $table->decimal('precio_venta', 10, 2);
            $table->unsignedInteger('stock_disponible')->default(0)
                  ->comment('Se incrementa al finalizar un ProduccionLote');

            // Imagen pública (disco "public", path relativo a storage/app/public)
            $table->string('imagen_url', 500)->nullable();

            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            // Relación opcional con la receta que genera este producto
            $table->foreignId('receta_id')
                  ->nullable()
                  ->constrained('recetas')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('estado');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_tienda');
    }
};
