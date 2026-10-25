@extends('layouts.admin')

@section('title', 'Nuevo Producto')
@section('page-title', '🛒 Nuevo Producto en Tienda')

@section('content')
<div class="card" style="max-width:720px;">
    <div class="card-header">
        <h3 class="card-title">Crear Producto</h3>
        <p style="color:var(--text-muted); font-size:.82rem; margin-top:2px;">
            El producto aparecerá en la tienda pública una vez activo.
        </p>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.tienda.productos.store') }}"
              enctype="multipart/form-data">
            @csrf
            @include('admin.tienda.productos._form', ['producto' => null])

            <div style="display:flex; gap:10px; margin-top:24px;">
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
                <a href="{{ route('admin.tienda.productos.index') }}"
                   style="padding:9px 18px; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm); font-weight:600; color:var(--text-2); text-decoration:none; font-size:.88rem;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
