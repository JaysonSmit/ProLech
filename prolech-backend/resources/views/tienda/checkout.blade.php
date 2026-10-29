@extends('layouts.tienda')

@section('title', 'Finalizar compra')

@php
    // Paso al que volver si el servidor devolvió errores de validación
    $pasoConError = $errors->hasAny(['metodo_pago', 'comprobante_yape', 'pago_referencia']) ? 2 : 1;
    $pasoInicial  = $errors->any() ? $pasoConError : 1;
@endphp

@push('styles')
<style>
    .co-wrap { padding-top: 28px; padding-bottom: 30px; }
    .co-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 26px; }
    .co-head h1 { font-size: clamp(1.7rem, 3vw, 2.3rem); font-weight: 800; color: var(--brand-dark); letter-spacing: -.8px; }
    .co-head p { color: var(--text-muted); font-size: .9rem; margin-top: 4px; }
    .secure { display: inline-flex; gap: 8px; align-items: center; background: #fff; border: 1px solid var(--border); padding: 8px 14px; border-radius: 999px; font-size: .78rem; font-weight: 700; color: var(--text-2); }

    /* Stepper */
    .steps-bar { display: flex; gap: 8px; margin-bottom: 22px; }
    .sb-step {
        flex: 1; display: flex; align-items: center; gap: 10px;
        background: #fff; border: 1.5px solid var(--border); border-radius: 16px;
        padding: 12px 14px; font-size: .84rem; font-weight: 700; color: var(--text-muted);
        transition: all .2s;
    }
    .sb-step .n {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--bg); font-weight: 800; font-size: .8rem;
    }
    .sb-step.active { border-color: var(--brand); color: var(--brand-dark); box-shadow: 0 10px 20px -14px rgba(31,111,235,.9); }
    .sb-step.active .n { background: var(--grad); color: #fff; }
    .sb-step.done { color: #166534; border-color: #bbf7d0; background: #f0fdf4; }
    .sb-step.done .n { background: #22c55e; color: #fff; }

    .co-grid { display: grid; grid-template-columns: 1fr 380px; gap: 26px; align-items: start; }

    .panel { background: #fff; border: 1px solid var(--border); border-radius: 24px; box-shadow: var(--shadow-md); }
    .panel-head { padding: 20px 24px 0; }
    .panel-head h2 { font-size: 1.15rem; font-weight: 800; color: var(--brand-dark); }
    .panel-head p { font-size: .82rem; color: var(--text-muted); margin-top: 3px; }
    .panel-body { padding: 20px 24px 24px; }

    .step-pane { display: none; animation: paneIn .3s ease; }
    .step-pane.active { display: block; }
    @keyframes paneIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: none; } }

    .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field.full { grid-column: 1 / -1; }
    .field label { font-size: .8rem; font-weight: 700; color: var(--text-2); }
    .field label .opt { color: var(--text-muted); font-weight: 500; }
    .field input, .field textarea {
        width: 100%; padding: 12px 14px; border-radius: 14px;
        border: 1.5px solid var(--border); background: var(--bg);
        font: inherit; font-size: .9rem; color: var(--text);
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .field input:focus, .field textarea:focus { outline: none; border-color: var(--brand); background: #fff; box-shadow: 0 0 0 4px rgba(31,111,235,.12); }
    .field input.bad { border-color: #ef4444; background: #fff5f5; }
    .field .hint { font-size: .74rem; color: var(--text-muted); }
    .error-msg { color: #dc2626; font-size: .76rem; font-weight: 600; }

    .co-alert { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 16px; padding: 14px 18px; font-weight: 600; font-size: .9rem; margin-bottom: 18px; }

    /* Métodos de pago */
    .pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .pay-opt { position: relative; cursor: pointer; }
    .pay-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .pay-card {
        display: block;
        border: 2px solid var(--border); border-radius: 18px; padding: 16px 14px;
        text-align: center; background: #fff; height: 100%;
        transition: border-color .15s, box-shadow .15s, transform .15s;
    }
    .pay-card .ico { width: 48px; height: 48px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 8px; }
    .pay-card b { display: block; font-size: .95rem; color: var(--brand-dark); }
    .pay-card small { font-size: .74rem; color: var(--text-muted); }
    .pay-opt:hover .pay-card { transform: translateY(-2px); }
    .pay-opt input:checked + .pay-card { border-color: var(--brand); box-shadow: 0 14px 26px -16px rgba(31,111,235,.9); background: var(--cream); }
    .pay-opt input:focus-visible + .pay-card { outline: 3px solid rgba(31,111,235,.35); outline-offset: 2px; }

    .pay-detail { display: none; margin-top: 18px; border-radius: 20px; padding: 20px; animation: paneIn .25s ease; }
    .pay-detail.visible { display: block; }

    .yape { background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%); border: 1px solid #ddd6fe; }
    .yape-grid { display: grid; grid-template-columns: 150px 1fr; gap: 20px; align-items: center; }
    .yape-qr {
        width: 150px; height: 150px; border-radius: 18px; background: #fff; padding: 12px;
        box-shadow: 0 10px 24px -12px rgba(109,40,217,.5);
        display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px;
    }
    .yape-qr i { border-radius: 2px; background: #5b21b6; }
    .yape-qr i.w { background: transparent; }
    .yape h3 { color: #5b21b6; font-size: 1.05rem; font-weight: 800; }
    .yape .num { font-size: 1.5rem; font-weight: 800; color: #4c1d95; letter-spacing: 1px; margin: 4px 0; }
    .yape p { font-size: .82rem; color: #6d28d9; }
    .copy-btn { margin-top: 8px; border: none; background: #6d28d9; color: #fff; font: inherit; font-weight: 700; font-size: .78rem; padding: 7px 12px; border-radius: 999px; cursor: pointer; }
    .dropzone {
        margin-top: 16px; display: block; border: 2px dashed #c4b5fd; border-radius: 16px; background: rgba(255,255,255,.7);
        padding: 18px; text-align: center; cursor: pointer; transition: background .15s, border-color .15s;
    }
    .dropzone:hover { background: #fff; border-color: #7c3aed; }
    .dropzone input { display: none; }
    .dropzone b { color: #5b21b6; }
    .dropzone small { display: block; color: var(--text-muted); font-size: .74rem; margin-top: 4px; }
    .dz-preview { display: none; margin: 10px auto 0; max-height: 140px; border-radius: 12px; }

    .tarjeta { background: var(--bg); border: 1px solid var(--border); }
    .cc {
        position: relative; max-width: 340px; aspect-ratio: 1.586; margin: 0 auto 20px;
        border-radius: 20px; padding: 22px; color: #fff;
        background: linear-gradient(135deg, #0b1f44 0%, #1f6feb 60%, #38bdf8 100%);
        box-shadow: 0 24px 40px -20px rgba(11,31,68,.8);
        display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;
    }
    .cc::after { content: ""; position: absolute; width: 220px; height: 220px; border-radius: 50%; right: -60px; top: -80px; background: rgba(255,255,255,.12); }
    .cc-top { display: flex; justify-content: space-between; align-items: center; }
    .cc-chip { width: 44px; height: 32px; border-radius: 7px; background: linear-gradient(135deg, #fde68a, #f2a541); }
    .cc-brand { font-weight: 800; font-size: 1.1rem; letter-spacing: 1px; font-style: italic; }
    .cc-num { font-family: 'SF Mono', Consolas, monospace; font-size: 1.25rem; letter-spacing: 2px; }
    .cc-meta { display: flex; justify-content: space-between; font-size: .8rem; text-transform: uppercase; }
    .cc-meta small { display: block; font-size: .6rem; opacity: .7; }
    .demo-note { font-size: .78rem; color: var(--text-muted); background: #fff; border: 1px dashed var(--border); padding: 10px 12px; border-radius: 12px; margin-top: 14px; }

    .efectivo { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-size: .88rem; line-height: 1.6; }

    /* Revisión */
    .review-block { border: 1px solid var(--border); border-radius: 18px; padding: 16px 18px; margin-bottom: 12px; display: flex; justify-content: space-between; gap: 12px; }
    .review-block h4 { font-size: .78rem; text-transform: uppercase; letter-spacing: .8px; color: var(--text-muted); margin-bottom: 6px; }
    .review-block p { font-size: .9rem; color: var(--text); line-height: 1.55; }
    .review-block button { align-self: flex-start; background: none; border: none; color: var(--brand); font: inherit; font-weight: 700; font-size: .82rem; cursor: pointer; }
    .terms { display: flex; gap: 10px; align-items: flex-start; font-size: .82rem; color: var(--text-2); margin: 14px 0 4px; }
    .terms input { width: 18px; height: 18px; accent-color: var(--brand); margin-top: 1px; }

    .nav-btns { display: flex; justify-content: space-between; gap: 12px; margin-top: 22px; }
    .btn-back { background: #fff; border: 1.5px solid var(--border); color: var(--text-2); font: inherit; font-weight: 700; padding: 12px 20px; border-radius: 999px; cursor: pointer; }
    .btn-back:hover { border-color: var(--brand); color: var(--brand); }
    .btn-next {
        flex: 1; max-width: 320px; margin-left: auto; border: none; border-radius: 999px; cursor: pointer;
        background: var(--grad); color: #fff; font: inherit; font-weight: 800; font-size: .95rem; padding: 14px 22px;
        box-shadow: 0 14px 28px -12px rgba(31,111,235,.85); transition: filter .15s, transform .15s;
    }
    .btn-next:hover { filter: brightness(1.07); transform: translateY(-1px); }
    .btn-next:disabled { opacity: .6; cursor: wait; transform: none; }

    /* Resumen */
    .summary { position: sticky; top: 92px; overflow: hidden; }
    .summary .panel-head { background: var(--grad-dark); color: #fff; padding: 18px 22px; }
    .summary .panel-head h2 { color: #fff; }
    .summary .panel-head p { color: #cfe1ff; }
    .s-item { display: flex; gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px dashed var(--border); }
    .s-item:last-child { border-bottom: none; }
    .s-item img { width: 54px; height: 54px; border-radius: 12px; object-fit: cover; background: var(--cream); flex-shrink: 0; }
    .s-item .q { position: relative; }
    .s-item .q span { position: absolute; top: -6px; right: -6px; background: var(--brand-dark); color: #fff; font-size: .68rem; font-weight: 800; min-width: 20px; height: 20px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; }
    .s-item .nm { flex: 1; min-width: 0; font-weight: 700; font-size: .86rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .s-item .nm small { display: block; font-weight: 500; color: var(--text-muted); font-size: .76rem; }
    .s-item .pr { font-weight: 800; color: var(--brand-dark); font-size: .9rem; }
    .totals { border-top: 1px solid var(--border); margin-top: 6px; padding-top: 12px; display: grid; gap: 8px; font-size: .86rem; color: var(--text-2); }
    .totals div { display: flex; justify-content: space-between; }
    .totals .grand { font-size: 1.05rem; font-weight: 800; color: var(--brand-dark); padding-top: 10px; border-top: 1px solid var(--border); }
    .totals .grand span:last-child { font-size: 1.5rem; background: var(--grad); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
    .badges span { font-size: .72rem; font-weight: 700; background: var(--cream); color: var(--brand); padding: 5px 10px; border-radius: 999px; }

    @media (max-width: 960px) {
        .co-grid { grid-template-columns: 1fr; }
        .summary { position: static; order: -1; }
    }
    @media (max-width: 640px) {
        .fields { grid-template-columns: 1fr; }
        .pay-grid { grid-template-columns: 1fr; }
        .pay-card { display: flex; align-items: center; gap: 12px; text-align: left; }
        .pay-card .ico { margin-bottom: 0; }
        .yape-grid { grid-template-columns: 1fr; justify-items: center; text-align: center; }
        .sb-step .lbl { display: none; }
        .sb-step { justify-content: center; }
        .panel-body, .panel-head { padding-left: 18px; padding-right: 18px; }
    }
</style>
@endpush

@section('content')
<div class="container co-wrap">

    <div class="co-head">
        <div>
            <h1>Finalizar compra</h1>
            <p>Tres pasos y recibes tu boleta al instante.</p>
        </div>
        <span class="secure">🔒 Compra segura</span>
    </div>

    @if($errors->has('checkout'))
        <div class="co-alert">⚠️ {{ $errors->first('checkout') }}</div>
    @endif
    @error('carrito')<div class="co-alert">⚠️ {{ $message }}</div>@enderror

    {{-- Carrito vacío --}}
    <div id="cart-empty-warning" style="display:none;">
        <div class="panel" style="text-align:center; padding:60px 20px;">
            <div style="font-size:3rem; margin-bottom:12px;">🧺</div>
            <h3 style="font-size:1.2rem; color:var(--brand-dark);">Tu carrito está vacío</h3>
            <p style="color:var(--text-muted); margin-top:6px;">Elige tus lácteos favoritos para continuar.</p>
            <a href="{{ route('tienda.index') }}#catalogo" class="btn-primary" style="margin-top:20px;">Ir al catálogo</a>
        </div>
    </div>

    <form id="checkout-form" method="POST" action="{{ route('tienda.checkout.store') }}"
          enctype="multipart/form-data" style="display:none;" novalidate>
        @csrf
        <input type="hidden" name="carrito" id="carrito-input">
        <input type="hidden" name="pago_referencia" id="pago-referencia" value="{{ old('pago_referencia') }}">

        <div class="steps-bar" aria-label="Pasos de la compra">
            <div class="sb-step" data-step="1"><span class="n">1</span><span class="lbl">Tus datos</span></div>
            <div class="sb-step" data-step="2"><span class="n">2</span><span class="lbl">Pago</span></div>
            <div class="sb-step" data-step="3"><span class="n">3</span><span class="lbl">Confirmar</span></div>
        </div>

        <div class="co-grid">
            <div class="panel">

                {{-- ══════════ PASO 1: DATOS ══════════ --}}
                <section class="step-pane" data-pane="1">
                    <div class="panel-head">
                        <h2>👤 ¿A quién se lo enviamos?</h2>
                        <p>Usaremos estos datos para la entrega y para tu boleta.</p>
                    </div>
                    <div class="panel-body">
                        <div class="fields">
                            <div class="field full">
                                <label for="cliente_nombre">Nombre completo</label>
                                <input type="text" id="cliente_nombre" name="cliente_nombre" value="{{ old('cliente_nombre') }}"
                                       placeholder="Ej. María Quispe Mamani" maxlength="150" autocomplete="name" required>
                                @error('cliente_nombre')<div class="error-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="cliente_documento">DNI <span class="opt">(para tu boleta)</span></label>
                                <input type="text" id="cliente_documento" name="cliente_documento" value="{{ old('cliente_documento') }}"
                                       placeholder="8 dígitos" maxlength="12" inputmode="numeric">
                                <span class="hint">Obligatorio en compras desde S/ 700.</span>
                                @error('cliente_documento')<div class="error-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field">
                                <label for="cliente_telefono">Celular / WhatsApp</label>
                                <input type="tel" id="cliente_telefono" name="cliente_telefono" value="{{ old('cliente_telefono') }}"
                                       placeholder="9XX XXX XXX" maxlength="20" autocomplete="tel" required>
                                @error('cliente_telefono')<div class="error-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field full">
                                <label for="cliente_email">Correo <span class="opt">(opcional)</span></label>
                                <input type="email" id="cliente_email" name="cliente_email" value="{{ old('cliente_email') }}"
                                       placeholder="tucorreo@ejemplo.com" maxlength="150" autocomplete="email">
                                @error('cliente_email')<div class="error-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field full">
                                <label for="cliente_direccion">Dirección de entrega</label>
                                <input type="text" id="cliente_direccion" name="cliente_direccion" value="{{ old('cliente_direccion') }}"
                                       placeholder="Jr. Los Álamos 123, Huata, Puno" maxlength="300" autocomplete="street-address" required>
                                @error('cliente_direccion')<div class="error-msg">{{ $message }}</div>@enderror
                            </div>
                            <div class="field full">
                                <label for="notas">Referencia o indicaciones <span class="opt">(opcional)</span></label>
                                <textarea id="notas" name="notas" rows="2" maxlength="500"
                                          placeholder="Casa celeste frente a la plaza…">{{ old('notas') }}</textarea>
                                @error('notas')<div class="error-msg">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="nav-btns">
                            <a href="{{ route('tienda.index') }}#catalogo" class="btn-back">← Seguir comprando</a>
                            <button type="button" class="btn-next" onclick="irAPaso(2)">Continuar al pago →</button>
                        </div>
                    </div>
                </section>

                {{-- ══════════ PASO 2: PAGO ══════════ --}}
                <section class="step-pane" data-pane="2">
                    <div class="panel-head">
                        <h2>💳 ¿Cómo quieres pagar?</h2>
                        <p>Elige el método que te sea más cómodo.</p>
                    </div>
                    <div class="panel-body">
                        <div class="pay-grid" role="radiogroup" aria-label="Método de pago">
                            <label class="pay-opt">
                                <input type="radio" name="metodo_pago" value="Yape" {{ old('metodo_pago', 'Yape') === 'Yape' ? 'checked' : '' }}>
                                <span class="pay-card">
                                    <span class="ico" style="background:#ede9fe;">💜</span>
                                    <span><b>Yape</b><small>Paga al instante con QR</small></span>
                                </span>
                            </label>
                            <label class="pay-opt">
                                <input type="radio" name="metodo_pago" value="Tarjeta" {{ old('metodo_pago') === 'Tarjeta' ? 'checked' : '' }}>
                                <span class="pay-card">
                                    <span class="ico" style="background:var(--cream);">💳</span>
                                    <span><b>Tarjeta</b><small>Débito o crédito</small></span>
                                </span>
                            </label>
                            <label class="pay-opt">
                                <input type="radio" name="metodo_pago" value="Efectivo" {{ old('metodo_pago') === 'Efectivo' ? 'checked' : '' }}>
                                <span class="pay-card">
                                    <span class="ico" style="background:#dcfce7;">💵</span>
                                    <span><b>Efectivo</b><small>Contra entrega</small></span>
                                </span>
                            </label>
                        </div>
                        @error('metodo_pago')<div class="error-msg" style="margin-top:8px;">{{ $message }}</div>@enderror

                        {{-- Yape --}}
                        <div class="pay-detail yape" data-metodo="Yape">
                            <div class="yape-grid">
                                <div class="yape-qr" id="yape-qr" aria-label="Código QR de Yape"></div>
                                <div>
                                    <h3>Yapea el total a</h3>
                                    <div class="num" id="yape-num">{{ $yapeNumero }}</div>
                                    <p>A nombre de <b>{{ $yapeTitular }}</b> · Monto: <b class="js-total">S/ 0.00</b></p>
                                    <button type="button" class="copy-btn" onclick="copiarNumero()">📋 Copiar número</button>
                                </div>
                            </div>
                            <div class="fields" style="margin-top:16px;">
                                <div class="field full">
                                    <label for="yape-operacion">Nº de operación <span class="opt">(opcional)</span></label>
                                    <input type="text" id="yape-operacion" inputmode="numeric" maxlength="12" placeholder="Aparece en tu constancia de Yape">
                                </div>
                            </div>
                            <label class="dropzone" for="comprobante_yape">
                                <input type="file" id="comprobante_yape" name="comprobante_yape" accept="image/jpeg,image/png,image/webp">
                                <span id="dz-text">📸 <b>Sube la captura de tu Yape</b><small>JPG, PNG o WebP · máx. 4 MB</small></span>
                                <img id="dz-preview" class="dz-preview" alt="Vista previa del comprobante">
                            </label>
                            @error('comprobante_yape')<div class="error-msg" style="margin-top:8px;">{{ $message }}</div>@enderror
                        </div>

                        {{-- Tarjeta --}}
                        <div class="pay-detail tarjeta" data-metodo="Tarjeta">
                            <div class="cc" aria-hidden="true">
                                <div class="cc-top"><span class="cc-chip"></span><span class="cc-brand" id="cc-brand">CARD</span></div>
                                <div class="cc-num" id="cc-num">•••• •••• •••• ••••</div>
                                <div class="cc-meta">
                                    <div><small>Titular</small><span id="cc-name">TU NOMBRE</span></div>
                                    <div><small>Vence</small><span id="cc-exp">MM/AA</span></div>
                                </div>
                            </div>
                            {{-- Sin atributo "name": los datos de la tarjeta nunca se envían al servidor --}}
                            <div class="fields">
                                <div class="field full">
                                    <label for="card-number">Número de tarjeta</label>
                                    <input type="text" id="card-number" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" autocomplete="cc-number">
                                </div>
                                <div class="field full">
                                    <label for="card-name">Titular</label>
                                    <input type="text" id="card-name" placeholder="Como aparece en la tarjeta" autocomplete="cc-name">
                                </div>
                                <div class="field">
                                    <label for="card-exp">Vencimiento</label>
                                    <input type="text" id="card-exp" inputmode="numeric" maxlength="5" placeholder="MM/AA" autocomplete="cc-exp">
                                </div>
                                <div class="field">
                                    <label for="card-cvv">CVV</label>
                                    <input type="password" id="card-cvv" inputmode="numeric" maxlength="4" placeholder="•••" autocomplete="cc-csc">
                                </div>
                            </div>
                            <p class="demo-note">🧪 Pasarela en modo demostración: no se realiza ningún cargo. Solo guardamos la marca y los últimos 4 dígitos para tu boleta.</p>
                        </div>

                        {{-- Efectivo --}}
                        <div class="pay-detail efectivo" data-metodo="Efectivo">
                            💵 <b>Pagas al recibir tu pedido.</b> Ten el monto exacto de
                            <b class="js-total">S/ 0.00</b> o avísanos en las indicaciones con cuánto pagarás para llevar vuelto.
                        </div>

                        <div class="nav-btns">
                            <button type="button" class="btn-back" onclick="irAPaso(1)">← Atrás</button>
                            <button type="button" class="btn-next" onclick="irAPaso(3)">Revisar pedido →</button>
                        </div>
                    </div>
                </section>

                {{-- ══════════ PASO 3: CONFIRMAR ══════════ --}}
                <section class="step-pane" data-pane="3">
                    <div class="panel-head">
                        <h2>✅ Revisa y confirma</h2>
                        <p>Al confirmar generamos tu boleta de venta.</p>
                    </div>
                    <div class="panel-body">
                        <div class="review-block">
                            <div>
                                <h4>Entrega</h4>
                                <p id="rv-datos"></p>
                            </div>
                            <button type="button" onclick="irAPaso(1)">Editar</button>
                        </div>
                        <div class="review-block">
                            <div>
                                <h4>Pago</h4>
                                <p id="rv-pago"></p>
                            </div>
                            <button type="button" onclick="irAPaso(2)">Cambiar</button>
                        </div>
                        <div class="review-block">
                            <div>
                                <h4>Comprobante</h4>
                                <p>🧾 Boleta de venta electrónica <span id="rv-doc"></span></p>
                            </div>
                        </div>

                        <label class="terms">
                            <input type="checkbox" id="acepto">
                            <span>Confirmo que mis datos son correctos y acepto que ProLech me contacte para coordinar la entrega.</span>
                        </label>
                        <div class="error-msg" id="acepto-error" style="display:none;">Marca la casilla para continuar.</div>

                        <div class="nav-btns">
                            <button type="button" class="btn-back" onclick="irAPaso(2)">← Atrás</button>
                            <button type="submit" class="btn-next" id="submit-btn">Pagar <span class="js-total">S/ 0.00</span> y generar boleta</button>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ══════════ RESUMEN ══════════ --}}
            <aside class="panel summary">
                <div class="panel-head">
                    <h2>🧺 Tu pedido</h2>
                    <p id="s-count">0 productos</p>
                </div>
                <div class="panel-body">
                    <div id="summary-items"></div>
                    <div class="totals">
                        <div><span>Op. gravada</span><span id="s-gravada">S/ 0.00</span></div>
                        <div><span>IGV (18 %)</span><span id="s-igv">S/ 0.00</span></div>
                        <div><span>Entrega</span><span>Se coordina por teléfono</span></div>
                        <div class="grand"><span>Total</span><span class="js-total">S/ 0.00</span></div>
                    </div>
                    <div class="badges">
                        <span>🔒 Pago seguro</span><span>🧾 Boleta</span><span>🥛 Hecho en Huata</span>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form   = document.getElementById('checkout-form');
    const items  = ProLechCart.items();
    let pasoActual = 1;

    if (!items || items.length === 0) {
        document.getElementById('cart-empty-warning').style.display = '';
        return;
    }
    form.style.display = '';

    // ── Resumen ──────────────────────────────────────────────────────
    const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const soles = n => 'S/ ' + n.toFixed(2);
    const total = items.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const gravada = Math.round(total / 1.18 * 100) / 100;

    document.getElementById('summary-items').innerHTML = items.map(i => `
        <div class="s-item">
            <div class="q"><img src="${esc(i.imagen)}" alt="" onerror="this.src='/images/producto-placeholder.png'"><span>${i.cantidad}</span></div>
            <div class="nm">${esc(i.nombre)}<small>S/ ${i.precio.toFixed(2)} c/u</small></div>
            <div class="pr">${soles(i.precio * i.cantidad)}</div>
        </div>`).join('');
    const unidades = items.reduce((s, i) => s + i.cantidad, 0);
    document.getElementById('s-count').textContent = unidades + (unidades === 1 ? ' producto' : ' productos');
    document.getElementById('s-gravada').textContent = soles(gravada);
    document.getElementById('s-igv').textContent = soles(total - gravada);
    document.querySelectorAll('.js-total').forEach(el => el.textContent = soles(total));

    // ── QR decorativo de Yape (patrón fijo a partir del número) ──────
    const qr = document.getElementById('yape-qr');
    const semilla = document.getElementById('yape-num').textContent.replace(/\D/g, '') || '987654321';
    for (let k = 0; k < 49; k++) {
        const r = Math.floor(k / 7), c = k % 7;
        const esquina = (r < 2 && c < 2) || (r < 2 && c > 4) || (r > 4 && c < 2);
        const lleno = esquina || ((+semilla[k % semilla.length] + k * 7) % 3 !== 0);
        qr.insertAdjacentHTML('beforeend', `<i class="${lleno ? '' : 'w'}"></i>`);
    }

    // ── Pasos ────────────────────────────────────────────────────────
    function metodo() { return form.querySelector('input[name="metodo_pago"]:checked')?.value || 'Yape'; }

    function marcar(input, ok) { input.classList.toggle('bad', !ok); return ok; }

    function validarPaso1() {
        const nombre = document.getElementById('cliente_nombre');
        const tel    = document.getElementById('cliente_telefono');
        const dir    = document.getElementById('cliente_direccion');
        const doc    = document.getElementById('cliente_documento');
        const email  = document.getElementById('cliente_email');
        let ok = true;
        ok = marcar(nombre, nombre.value.trim().length >= 3) && ok;
        ok = marcar(tel, /^[0-9 +]{6,20}$/.test(tel.value.trim())) && ok;
        ok = marcar(dir, dir.value.trim().length >= 5) && ok;
        const docVal = doc.value.trim();
        ok = marcar(doc, (docVal === '' && total < 700) || /^(\d{8}|[A-Za-z0-9]{9,12})$/.test(docVal)) && ok;
        ok = marcar(email, email.value.trim() === '' || /^\S+@\S+\.\S+$/.test(email.value.trim())) && ok;
        if (!ok) form.querySelector('.bad')?.focus();
        return ok;
    }

    function validarPaso2() {
        const m = metodo();
        if (m === 'Yape') {
            const file = document.getElementById('comprobante_yape');
            if (!file.files.length) {
                const dz = document.querySelector('.dropzone');
                dz.style.borderColor = '#ef4444';
                dz.scrollIntoView({ behavior: 'smooth', block: 'center' });
                ProLechCartToast('Sube la captura de tu pago con Yape.');
                return false;
            }
            const op = document.getElementById('yape-operacion').value.replace(/\D/g, '');
            document.getElementById('pago-referencia').value = op ? 'Yape op. ' + op : '';
        }
        if (m === 'Tarjeta') {
            const num = document.getElementById('card-number');
            const exp = document.getElementById('card-exp');
            const cvv = document.getElementById('card-cvv');
            const name = document.getElementById('card-name');
            const digitos = num.value.replace(/\D/g, '');
            let ok = true;
            ok = marcar(num, luhn(digitos)) && ok;
            ok = marcar(name, name.value.trim().length >= 3) && ok;
            ok = marcar(exp, vigente(exp.value)) && ok;
            ok = marcar(cvv, /^\d{3,4}$/.test(cvv.value)) && ok;
            if (!ok) { form.querySelector('.tarjeta .bad')?.focus(); return false; }
            document.getElementById('pago-referencia').value = marca(digitos) + ' •••• ' + digitos.slice(-4);
        }
        if (m === 'Efectivo') document.getElementById('pago-referencia').value = '';
        return true;
    }

    function pintarRevision() {
        const v = id => esc(document.getElementById(id).value.trim());
        document.getElementById('rv-datos').innerHTML =
            `<b>${v('cliente_nombre')}</b> · ${v('cliente_telefono')}<br>${v('cliente_direccion')}`;
        const m = metodo();
        const ref = document.getElementById('pago-referencia').value;
        document.getElementById('rv-pago').innerHTML = {
            Yape: '💜 Yape · captura adjunta' + (ref ? ' · ' + esc(ref) : ''),
            Tarjeta: '💳 ' + esc(ref),
            Efectivo: '💵 Efectivo contra entrega',
        }[m];
        const doc = v('cliente_documento');
        document.getElementById('rv-doc').textContent = doc ? '· DNI ' + doc : '· Cliente varios';
    }

    window.irAPaso = function (n, desplazar = true) {
        // Avanzar exige que los pasos anteriores estén completos
        if (n > pasoActual) {
            if (pasoActual === 1 && !validarPaso1()) return;
            if (n === 3 && !validarPaso2()) return;
        }
        if (n === 3) pintarRevision();
        pasoActual = n;
        document.querySelectorAll('.step-pane').forEach(p => p.classList.toggle('active', +p.dataset.pane === n));
        document.querySelectorAll('.sb-step').forEach(s => {
            const k = +s.dataset.step;
            s.classList.toggle('active', k === n);
            s.classList.toggle('done', k < n);
        });
        if (desplazar) window.scrollTo({ top: form.offsetTop - 90, behavior: 'smooth' });
    };

    // ── Método de pago ───────────────────────────────────────────────
    function mostrarMetodo() {
        const m = metodo();
        document.querySelectorAll('.pay-detail').forEach(d => d.classList.toggle('visible', d.dataset.metodo === m));
    }
    form.querySelectorAll('input[name="metodo_pago"]').forEach(r => r.addEventListener('change', mostrarMetodo));
    mostrarMetodo();

    // Vista previa de la captura de Yape
    document.getElementById('comprobante_yape').addEventListener('change', e => {
        const f = e.target.files[0];
        const img = document.getElementById('dz-preview');
        document.querySelector('.dropzone').style.borderColor = '';
        if (!f) { img.style.display = 'none'; return; }
        if (f.size > 4 * 1024 * 1024) { ProLechCartToast('La imagen supera los 4 MB.'); e.target.value = ''; return; }
        img.src = URL.createObjectURL(f);
        img.style.display = 'block';
        document.getElementById('dz-text').innerHTML = '✅ <b>' + esc(f.name) + '</b><small>Toca para cambiar la imagen</small>';
    });

    window.copiarNumero = function () {
        const num = document.getElementById('yape-num').textContent.replace(/\s/g, '');
        navigator.clipboard?.writeText(num).then(() => ProLechCartToast('Número copiado: ' + num, 'ok'));
    };

    // ── Tarjeta (solo visual) ────────────────────────────────────────
    function luhn(n) {
        if (!/^\d{13,19}$/.test(n)) return false;
        let s = 0;
        for (let i = 0; i < n.length; i++) {
            let d = +n[n.length - 1 - i];
            if (i % 2) { d *= 2; if (d > 9) d -= 9; }
            s += d;
        }
        return s % 10 === 0;
    }
    function marca(n) {
        if (/^4/.test(n)) return 'VISA';
        if (/^(5[1-5]|2[2-7])/.test(n)) return 'MASTERCARD';
        if (/^3[47]/.test(n)) return 'AMEX';
        if (/^3(0[0-5]|[68])/.test(n)) return 'DINERS';
        return 'TARJETA';
    }
    function vigente(v) {
        const m = /^(\d{2})\/(\d{2})$/.exec(v);
        if (!m || +m[1] < 1 || +m[1] > 12) return false;
        const fin = new Date(2000 + +m[2], +m[1], 1);
        return fin > new Date();
    }
    document.getElementById('card-number').addEventListener('input', e => {
        const d = e.target.value.replace(/\D/g, '').slice(0, 16);
        e.target.value = d.replace(/(.{4})/g, '$1 ').trim();
        document.getElementById('cc-num').textContent = (d.padEnd(16, '•').replace(/(.{4})/g, '$1 ')).trim();
        document.getElementById('cc-brand').textContent = d ? marca(d) : 'CARD';
    });
    document.getElementById('card-name').addEventListener('input', e => {
        document.getElementById('cc-name').textContent = e.target.value.toUpperCase() || 'TU NOMBRE';
    });
    document.getElementById('card-exp').addEventListener('input', e => {
        let d = e.target.value.replace(/\D/g, '').slice(0, 4);
        if (d.length >= 3) d = d.slice(0, 2) + '/' + d.slice(2);
        e.target.value = d;
        document.getElementById('cc-exp').textContent = d || 'MM/AA';
    });

    // ── Envío ────────────────────────────────────────────────────────
    form.addEventListener('submit', e => {
        if (!document.getElementById('acepto').checked) {
            e.preventDefault();
            document.getElementById('acepto-error').style.display = 'block';
            return;
        }
        if (!validarPaso1() || !validarPaso2()) { e.preventDefault(); return; }
        document.getElementById('carrito-input').value = ProLechCart.getItemsJson();
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Procesando pago…';
    });

    // Toast reutilizando el estilo del carrito
    window.ProLechCartToast = function (msg, tipo = 'warn') {
        const t = document.createElement('div');
        t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:${tipo === 'warn' ? '#9a3412' : '#0b1f44'};color:#fff;padding:11px 22px;border-radius:999px;font-size:.85rem;font-weight:600;z-index:999;box-shadow:0 10px 24px rgba(0,0,0,.25);animation:fadeInUp .25s ease;`;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 2800);
    };

    // Tras un error del servidor se vuelve al paso afectado
    pasoActual = {{ $pasoInicial }};
    irAPaso(pasoActual, false);
})();
</script>
@endpush
