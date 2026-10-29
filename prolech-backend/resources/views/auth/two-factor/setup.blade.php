<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar verificación WhatsApp — ProLech</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, sans-serif;
            background: linear-gradient(135deg, #0b1f44 0%, #052e16 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff; border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.3);
            padding: 36px; width: 100%; max-width: 500px;
        }
        .header { text-align: center; margin-bottom: 26px; }
        .icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin: 0 auto 14px;
            box-shadow: 0 6px 18px rgba(37,211,102,.35);
        }
        h1 { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .sub { font-size: .84rem; color: #64748b; line-height: 1.5; }

        .step {
            display: flex; gap: 14px; align-items: flex-start;
            padding: 14px 16px; margin-bottom: 12px;
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 11px;
        }
        .step-num {
            width: 28px; height: 28px; flex-shrink: 0;
            background: #25d366; color: #fff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .85rem;
        }
        .step-body strong { display: block; font-size: .88rem; color: #0f172a; margin-bottom: 3px; }
        .step-body p { font-size: .8rem; color: #64748b; line-height: 1.5; }
        .step-body code {
            display: inline-block;
            background: #f0fdf4; border: 1px solid #86efac;
            color: #15803d; font-weight: 700;
            padding: 2px 10px; border-radius: 5px;
            font-size: .82rem; margin-top: 4px;
            font-family: monospace; user-select: all;
        }

        .divider {
            display: flex; align-items: center; gap: 10px;
            margin: 20px 0; font-size: .72rem;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: #94a3b8;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }

        label {
            display: block; font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            color: #64748b; margin-bottom: 7px;
        }
        input[type="text"] {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #e2e8f0; border-radius: 9px;
            font-size: .9rem; outline: none; font-family: inherit;
            transition: border-color .15s; margin-bottom: 14px;
            background: #f8fafc;
        }
        input[type="text"]:focus {
            border-color: #25d366;
            box-shadow: 0 0 0 3px rgba(37,211,102,.12);
            background: #fff;
        }
        .invalid { border-color: #dc2626 !important; background: #fff8f8 !important; }
        .error-msg { color: #dc2626; font-size: .75rem; margin-top: -10px; margin-bottom: 12px; display: block; }

        .btn {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff; border: none; border-radius: 11px;
            font-size: .95rem; font-weight: 700; cursor: pointer;
            transition: opacity .15s; margin-top: 4px;
        }
        .btn:hover { opacity: .9; }

        .alert-info {
            background: #eff6ff; border: 1px solid #bfdbfe;
            color: #1d4ed8; padding: 11px 14px;
            border-radius: 9px; font-size: .8rem; line-height: 1.5;
            margin-bottom: 18px;
        }
        .footer { margin-top: 16px; text-align: center; }
        .footer form { display: inline; }
        .footer button { background: none; border: none; cursor: pointer; color: #94a3b8; font-size: .76rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <div class="icon">💬</div>
        <h1>Activa la verificación por WhatsApp</h1>
        <p class="sub">Sigue estos 3 pasos — solo necesitas hacerlo una vez</p>
    </div>

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;
                padding:11px 14px;border-radius:9px;font-size:.83rem;margin-bottom:16px">
        ⚠️ {{ $errors->first() }}
    </div>
    @endif

    {{-- PASOS ──────────────────────────────────────────────────────── --}}
    <div class="step">
        <div class="step-num">1</div>
        <div class="step-body">
            <strong>Agrega este número a tus contactos de WhatsApp</strong>
            <p>Guarda el número <strong>+34 644 59 21 97</strong> como "CallMeBot" en tu celular.</p>
        </div>
    </div>

    <div class="step">
        <div class="step-num">2</div>
        <div class="step-body">
            <strong>Envíale exactamente este mensaje por WhatsApp</strong>
            <p>Abre WhatsApp → busca "CallMeBot" → envía este texto:</p>
            <code>I allow callmebot to send me messages</code>
            <p style="margin-top:6px">Recibirás tu <strong>API Key personal</strong> en segundos.</p>
        </div>
    </div>

    <div class="step">
        <div class="step-num">3</div>
        <div class="step-body">
            <strong>Ingresa tu número y la API Key aquí abajo</strong>
            <p>Completa el formulario y te enviaremos un código de prueba al instante.</p>
        </div>
    </div>

    <div class="alert-info">
        💡 <strong>¿Qué es CallMeBot?</strong> Es un servicio gratuito y confiable que permite
        enviar mensajes de WhatsApp automáticamente. No guarda tus datos personales.
        <a href="https://www.callmebot.com" target="_blank" style="color:#1d4ed8">callmebot.com →</a>
    </div>

    <div class="divider">Ingresa tus datos</div>

    <form method="POST" action="{{ route('2fa.setup.post') }}">
        @csrf
        <label for="telefono">
            Tu número de WhatsApp
            <span style="font-weight:400;text-transform:none;font-size:.7rem">
                — con código de país (ej: 51901344157)
            </span>
        </label>
        <input type="text"
               id="telefono" name="telefono"
               value="{{ old('telefono', preg_replace('/\D/', '', $user->telefono ?? '51901344157')) }}"
               placeholder="51901344157"
               class="{{ $errors->has('telefono') ? 'invalid' : '' }}"
               inputmode="numeric"
               maxlength="20">
        @error('telefono')<span class="error-msg">{{ $message }}</span>@enderror

        <label for="callmebot_apikey">
            Tu API Key de CallMeBot
            <span style="font-weight:400;text-transform:none;font-size:.7rem">
                — la recibirás en WhatsApp tras el paso 2
            </span>
        </label>
        <input type="text"
               id="callmebot_apikey" name="callmebot_apikey"
               value="{{ old('callmebot_apikey') }}"
               placeholder="Ej: 1234567"
               class="{{ $errors->has('callmebot_apikey') ? 'invalid' : '' }}"
               maxlength="60">
        @error('callmebot_apikey')<span class="error-msg">{{ $message }}</span>@enderror

        <button type="submit" class="btn">
            📲 Enviar código de prueba a mi WhatsApp
        </button>
    </form>

    <div class="footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">← Cerrar sesión</button>
        </form>
    </div>
</div>
</body>
</html>
