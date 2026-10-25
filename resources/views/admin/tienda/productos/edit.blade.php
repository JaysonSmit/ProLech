@extends('layouts.admin')

@section('title', 'Editar Producto')
@section('page-title', '✏️ Editar Producto')

@section('content')
<div class="card" style="max-width:720px;">
    <div class="card-header">
        <h3 class="card-title">{{ $producto->nombre }}</h3>
        <p style="color:var(--text-muted); font-size:.82rem; margin-top:2px;">
            Stock actual: <strong style="color:var(--brand);">{{ $producto->stock_disponible }} uds.</strong>
        </p>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.tienda.productos.update', $producto) }}"
              enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('admin.tienda.productos._form', compact('producto'))

            <div style="display:flex; gap:10px; margin-top:24px;">
                <button type="submit" class="btn btn-primary">Actualizar Producto</button>
                <a href="{{ route('admin.tienda.productos.index') }}"
                   style="padding:9px 18px; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm); font-weight:600; color:var(--text-2); text-decoration:none; font-size:.88rem;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
