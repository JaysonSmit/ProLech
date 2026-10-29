@extends('layouts.admin')

@section('title', 'Exportar')
@section('page-title', '📤 Exportar datos')

@section('content')
<style>
    .exp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .exp { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 22px; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 10px; }
    .exp .ico { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: #eef5ff; }
    .exp h3 { font-size: 1.05rem; } .exp p { font-size: .84rem; color: var(--text-muted); flex: 1; }
    .exp .acciones { display: flex; gap: 8px; flex-wrap: wrap; }
    @media (max-width: 1000px) { .exp-grid { grid-template-columns: 1fr; } }
</style>

<form method="GET" class="card" style="padding:18px 22px; display:flex; gap:14px; align-items:end; flex-wrap:wrap">
    <div class="form-group"><label>Desde</label><input type="date" name="desde" value="{{ $desde }}"></div>
    <div class="form-group"><label>Hasta</label><input type="date" name="hasta" value="{{ $hasta }}"></div>
    <button class="btn btn-ghost">Aplicar rango</button>
    <span style="color:var(--text-muted); font-size:.8rem">Excel (.xlsx) con encabezado fijo y números que se pueden sumar · CSV para otros programas · PDF listo para imprimir o enviar.</span>
</form>

@php $q = http_build_query(['desde' => $desde, 'hasta' => $hasta]); @endphp
<div class="exp-grid">
    <div class="exp">
        <div class="ico">🥛</div>
        <h3>Acopios</h3>
        <p>Todas las entregas del rango (app clásica y app offline): ganadero, zona, chofer, litros, agua, densidad, grasa y calidad.</p>
        <div class="acciones">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.operaciones.exportar.acopios') }}?{{ $q }}&formato=xlsx">⬇ Excel</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.operaciones.exportar.acopios') }}?{{ $q }}&formato=pdf">⬇ PDF</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.operaciones.exportar.acopios') }}?{{ $q }}">CSV</a>
            <a class="btn btn-ghost btn-sm" target="_blank" href="{{ route('admin.operaciones.exportar.acopios.imprimir') }}?{{ $q }}">🖨 Imprimir</a>
        </div>
    </div>
    <div class="exp">
        <div class="ico">💵</div>
        <h3>Liquidaciones</h3>
        <p>Pagos semanales a ganaderos que se cruzan con el rango: litros aprobados, precio, descuentos y neto.</p>
        <div class="acciones">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.operaciones.exportar.liquidaciones') }}?{{ $q }}&formato=xlsx">⬇ Excel</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.operaciones.exportar.liquidaciones') }}?{{ $q }}&formato=pdf">⬇ PDF (planilla)</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.operaciones.exportar.liquidaciones') }}?{{ $q }}">CSV</a>
        </div>
    </div>
    <div class="exp">
        <div class="ico">🗺️</div>
        <h3>Rutas del mes</h3>
        <p>Ganaderos por zona con su chofer asignado este mes, dirección y coordenadas GPS. El PDF trae una casilla por parada para marcar en campo.</p>
        <div class="acciones">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.operaciones.exportar.rutas') }}?formato=xlsx">⬇ Excel</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.operaciones.exportar.rutas') }}?formato=pdf">⬇ PDF (hoja de ruta)</a>
            <a class="btn btn-ghost btn-sm" href="{{ route('admin.operaciones.exportar.rutas') }}">CSV</a>
        </div>
    </div>
</div>
@endsection
