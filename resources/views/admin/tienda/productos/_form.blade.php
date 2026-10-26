{{--
    Partial compartido por create.blade.php y edit.blade.php.
    Variable: $producto (null en create, instancia en edit).
--}}
<div class="form-grid">

    {{-- Nombre --}}
    <div class="form-group span2">
        <label for="nombre">Nombre <span class="req">*</span></label>
        <input type="text" id="nombre" name="nombre" maxlength="150"
               value="{{ old('nombre', $producto?->nombre) }}"
               placeholder="Ej: Queso Tipo Paria 500g" required>
        @error('nombre')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

    {{-- Slug --}}
    <div class="form-group span2">
        <label for="slug">Slug URL <span class="hint">(se genera automáticamente si lo dejas vacío)</span></label>
        <input type="text" id="slug" name="slug" maxlength="160"
               value="{{ old('slug', $producto?->slug) }}"
               placeholder="queso-tipo-paria-500g">
        @error('slug')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

    {{-- Descripción --}}
    <div class="form-group full">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" maxlength="2000"
                  placeholder="Descripción visible al cliente en la tienda…">{{ old('descripcion', $producto?->descripcion) }}</textarea>
        @error('descripcion')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

    {{-- Precio --}}
    <div class="form-group">
        <label for="precio_venta">Precio Venta (S/) <span class="req">*</span></label>
        <input type="number" id="precio_venta" name="precio_venta"
               min="0.01" step="0.01"
               value="{{ old('precio_venta', $producto?->precio_venta) }}"
               required placeholder="0.00">
        @error('precio_venta')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
        <div id="ayuda-precio" style="font-size:.78rem;margin-top:6px;font-weight:600"></div>
        <button type="button" id="usar-sugerido" style="display:none;margin-top:6px;padding:5px 10px;border-radius:6px;border:1px solid var(--border);background:var(--bg);cursor:pointer;font-size:.76rem"></button>
    </div>
    @if(isset($recetas) && $recetas->first()?->costo !== null)
    <script>
    (function () {
        // Costo por unidad de cada receta (insumos actuales) y precio sugerido con el margen objetivo
        const COSTOS = @json($recetas->mapWithKeys(fn ($r) => [$r->id => ['costo' => $r->costo, 'sugerido' => $r->sugerido]]));
        const precio = document.getElementById('precio_venta');
        const receta = () => document.getElementById('receta_id');   // está más abajo en el formulario
        const ayuda = document.getElementById('ayuda-precio'), boton = document.getElementById('usar-sugerido');
        function pintar() {
            const c = COSTOS[receta()?.value];
            if (!c) { ayuda.textContent = ''; boton.style.display = 'none'; return; }
            const p = parseFloat(precio.value);
            boton.style.display = 'inline-block';
            boton.textContent = `Usar precio sugerido: S/ ${Number(c.sugerido).toFixed(2)}`;
            if (!p) { ayuda.style.color = 'var(--text-muted)'; ayuda.textContent = `Costo por unidad: S/ ${Number(c.costo).toFixed(2)}`; return; }
            const m = (p - c.costo) / p * 100;
            ayuda.style.color = m < 10 ? '#dc2626' : (m < 20 ? '#d97706' : '#15803d');
            ayuda.textContent = `Costo S/ ${Number(c.costo).toFixed(2)} · ganancia S/ ${(p - c.costo).toFixed(2)} · margen ${m.toFixed(1)} %` + (m < 0 ? ' — ¡por debajo del costo!' : '');
        }
        boton.addEventListener('click', () => { precio.value = Number(COSTOS[receta().value].sugerido).toFixed(2); pintar(); });
        precio.addEventListener('input', pintar);
        document.addEventListener('DOMContentLoaded', () => { document.getElementById('receta_id')?.addEventListener('change', pintar); pintar(); });
    })();
    </script>
    @endif

    {{-- Stock --}}
    <div class="form-group">
        <label for="stock_disponible">Stock Inicial (uds.) <span class="req">*</span></label>
        <input type="number" id="stock_disponible" name="stock_disponible"
               min="0" step="1"
               value="{{ old('stock_disponible', $producto?->stock_disponible ?? 0) }}"
               required>
        <span style="color:var(--text-muted); font-size:.75rem;">
            💡 El stock aumenta automáticamente al cerrar un Lote de Producción.
        </span>
        @error('stock_disponible')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

    {{-- Estado --}}
    <div class="form-group">
        <label for="estado">Estado <span class="req">*</span></label>
        <select id="estado" name="estado" required>
            <option value="activo"   {{ old('estado', $producto?->estado ?? 'activo') === 'activo'   ? 'selected' : '' }}>Activo</option>
            <option value="inactivo" {{ old('estado', $producto?->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
        </select>
        @error('estado')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

    {{-- Receta asociada (opcional) --}}
    <div class="form-group">
        <label for="receta_id">Receta asociada <span class="hint">(opcional)</span></label>
        <select id="receta_id" name="receta_id">
            <option value="">— Sin receta —</option>
            @foreach($recetas as $r)
                <option value="{{ $r->id }}" {{ old('receta_id', $producto?->receta_id) == $r->id ? 'selected' : '' }}>
                    {{ $r->nombre }}
                </option>
            @endforeach
        </select>
        @error('receta_id')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

    {{-- Imagen --}}
    <div class="form-group full">
        <label for="imagen">
            Imagen del Producto
            @if($producto?->imagen_url)
                <span class="hint">— sube una nueva para reemplazar la actual</span>
            @endif
        </label>

        @if($producto?->imagen_url)
            <div style="margin-bottom:10px;">
                <img src="{{ asset('storage/'.$producto->imagen_url) }}"
                     alt="{{ $producto->nombre }}"
                     style="height:100px; border-radius:8px; border:1px solid var(--border); object-fit:cover;">
            </div>
        @endif

        <input type="file" id="imagen" name="imagen"
               accept="image/jpg,image/jpeg,image/png,image/webp"
               style="padding:6px 0;">
        <span style="color:var(--text-muted); font-size:.75rem;">
            JPG, PNG o WebP — máx. 2 MB. Se guardará en storage/tienda/productos/.
        </span>
        @error('imagen')<span style="color:var(--red); font-size:.78rem;">{{ $message }}</span>@enderror
    </div>

</div>
