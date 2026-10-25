<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar código — ProLech</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, sans-serif;
            background: linear-gradient(135deg, #0b1f44 0%, #052e16 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .card {
            background: #fff; border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.3);
            padding: 40px 36px; width: 100%; max-width: 400px;
            text-align: center;
        }
        .icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; margin: 0 auto 18px;
            box-shadow: 0 6px 18px rgba(37,211,102,.35);
        }
        h1 { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .sub { font-size: .85rem; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        .ok-box {
            background: #f0fdf4; border: 1px solid #86efac;
            color: #15803d; padding: 12px 14px;
            border-radius: 9px; font-size: .85rem; margin-bottom: 20px;
            font-weight: 600;
        }
        .error-box {
            background: #fef2f2; border: 1px solid #fca5a5;
            color: #dc2626; padding: 11px 14px;
            border-radius: 9px; font-size: .83rem; margin-bottom: 18px;
        }
        label {
            display: block; text-align: left; font-size: .7rem;
            font-weight: 700; text-transform: uppercase;
            letter-spacing: .5px; color: #64748b; margin-bottom: 8px;
        }
        .code-input {
            width: 100%; padding: 16px 12px;
            font-size: 2.4rem; font-weight: 900;
            letter-spacing: .5em; text-align: center;
            border: 2px solid #e2e8f0; border-radius: 12px;
            outline: none; font-family: 'SF Mono', monospace;
            color: #128c7e; background: #f8fafc;
            transition: border-color .15s; margin-bottom: 20px;
        }
        .code-input:focus {
            border-color: #25d366;
            box-shadow: 0 0 0 3px rgba(37,211,102,.15); background: #fff;
        }
        .btn {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff; border: none; border-radius: 11px;
            font-size: .95rem; font-weight: 700; cursor: pointer;
        }
        .btn:disabled { opacity: .45; cursor: not-allowed; }
        .back { margin-top: 14px; font-size: .78rem; color: #94a3b8; }
        .back a { color: #25d366; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">✅</div>
    <h1>¡Código enviado!</h1>
    <p class="sub">
        Revisa tu WhatsApp. Ingresa el código de 6 dígitos
        para confirmar que todo funciona.
    </p>

    <div class="ok-box">
        📲 Código enviado a tu WhatsApp. Válido por 10 minutos.
    </div>

    @if(config('app.debug'))
        <div style="background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1; padding: 10px; border-radius: 9px; font-size: .8rem; margin-bottom: 16px; text-align: left;">
            🛠️ <strong>Modo desarrollo:</strong> Puedes ingresar tu código recibido por WhatsApp o el código maestro de pruebas <code>123456</code>.
        </div>
    @endif

    @if($errors->has('code'))
        <div class="error-box">⚠️ {{ $errors->first('code') }}</div>
    @endif

    <form method="POST" action="{{ route('2fa.setup.confirmar.post') }}" id="form-conf">
        @csrf
        <label for="code">Código de confirmación</label>
        <input type="text"
               id="code" name="code"
               class="code-input"
               inputmode="numeric"
               maxlength="6"
               placeholder="000000"
               autofocus required>
        <button type="submit" class="btn" id="btn-conf" disabled>
            🔐 Confirmar y activar
        </button>
    </form>

    <div class="back">
        ¿No recibiste el código?
        <a href="{{ route('2fa.setup') }}">← Volver a intentar</a>
    </div>
</div>
<script>
(function () {
    var input = document.getElementById('code');
    var btn   = document.getElementById('btn-conf');
    input.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
        btn.disabled = this.value.length < 6;
        if (this.value.length === 6) {
            btn.textContent = '⏳ Verificando...';
            document.getElementById('form-conf').submit();
        }
    });
}());
</script>
</body>
</html>
