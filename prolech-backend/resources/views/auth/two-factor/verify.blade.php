<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación WhatsApp — ProLech</title>
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
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.3);
            padding: 40px 36px;
            width: 100%; max-width: 400px;
            text-align: center;
        }
        .icon {
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; margin: 0 auto 20px;
            box-shadow: 0 6px 18px rgba(37,211,102,.35);
        }
        h1 { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .sub { font-size: .85rem; color: #64748b; line-height: 1.6; margin-bottom: 6px; }
        .numero {
            display: inline-block;
            background: #f0fdf4; border: 1px solid #86efac;
            color: #15803d; font-weight: 800;
            padding: 4px 14px; border-radius: 20px;
            font-size: .9rem; margin-bottom: 24px;
            font-family: monospace; letter-spacing: .1em;
        }
        .error-box {
            background: #fef2f2; border: 1px solid #fca5a5;
            color: #dc2626; padding: 11px 14px;
            border-radius: 9px; font-size: .83rem;
            margin-bottom: 18px; text-align: left;
        }
        .ok-box {
            background: #f0fdf4; border: 1px solid #86efac;
            color: #15803d; padding: 11px 14px;
            border-radius: 9px; font-size: .83rem;
            margin-bottom: 18px;
        }
        label {
            display: block; text-align: left;
            font-size: .7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            color: #64748b; margin-bottom: 8px;
        }
        .code-input {
            width: 100%; padding: 16px 12px;
            font-size: 2.4rem; font-weight: 900;
            letter-spacing: .5em; text-align: center;
            border: 2px solid #e2e8f0; border-radius: 12px;
            outline: none; font-family: 'SF Mono', monospace;
            color: #128c7e; background: #f8fafc;
            transition: border-color .15s, box-shadow .15s;
            margin-bottom: 18px;
        }
        .code-input:focus {
            border-color: #25d366;
            box-shadow: 0 0 0 3px rgba(37,211,102,.15);
            background: #fff;
        }
        .code-input.error { border-color: #dc2626; background: #fff8f8; }
        .btn-primary {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff; border: none; border-radius: 11px;
            font-size: .95rem; font-weight: 700;
            cursor: pointer; transition: opacity .15s, transform .1s;
            margin-bottom: 12px;
        }
        .btn-primary:hover { opacity: .92; }
        .btn-primary:active { transform: scale(.98); }
        .btn-primary:disabled { opacity: .45; cursor: not-allowed; }
        .btn-ghost {
            width: 100%; padding: 10px;
            background: none; border: 1.5px solid #e2e8f0;
            border-radius: 9px; font-size: .83rem;
            color: #64748b; cursor: pointer;
            font-weight: 600; transition: border-color .15s;
            margin-bottom: 6px;
        }
        .btn-ghost:hover { border-color: #94a3b8; }

        /* Barra de tiempo */
        .timer-bar { width: 100%; height: 4px; background: #e2e8f0; border-radius: 2px; margin-bottom: 20px; overflow: hidden; }
        .timer-fill { height: 100%; background: #25d366; border-radius: 2px; transition: width 1s linear; }

        .footer { margin-top: 18px; font-size: .76rem; color: #94a3b8; }
        .footer form { display: inline; }
        .footer button { background: none; border: none; cursor: pointer; color: #94a3b8; font-size: .76rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">💬</div>
    <h1>Código por WhatsApp</h1>
    <p class="sub">Enviamos un código de 6 dígitos a tu WhatsApp</p>
    <span class="numero">📱 {{ $telefono }}</span>

    @if(session('resent'))
        <div class="ok-box">{{ session('resent') }}</div>
    @endif

    @if(config('app.debug'))
        <div style="background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1; padding: 10px; border-radius: 9px; font-size: .8rem; margin-bottom: 16px; text-align: left;">
            🛠️ <strong>Modo desarrollo:</strong> Puedes ingresar tu código recibido por WhatsApp o el código maestro de pruebas <code>123456</code>.
        </div>
    @endif

    @if($errors->has('code'))
        <div class="error-box">⚠️ {{ $errors->first('code') }}</div>
    @endif

    {{-- Barra de tiempo --}}
    <div class="timer-bar">
        <div class="timer-fill" id="timer-fill"></div>
    </div>

    <form method="POST" action="{{ route('2fa.verificar.post') }}" id="form-otp">
        @csrf
        <label for="code">Código de verificación</label>
        <input type="text"
               id="code" name="code"
               class="code-input {{ $errors->has('code') ? 'error' : '' }}"
               inputmode="numeric"
               pattern="[0-9]{6}"
               maxlength="6"
               autocomplete="one-time-code"
               placeholder="000000"
               autofocus
               required>
        <button type="submit" class="btn-primary" id="btn-verificar" disabled>
            ✓ Verificar y entrar
        </button>
    </form>

    {{-- Reenviar código --}}
    <form method="POST" action="{{ route('2fa.reenviar') }}">
        @csrf
        <button type="submit" class="btn-ghost" id="btn-reenviar" disabled>
            🔄 Reenviar código
        </button>
    </form>

    <div class="footer">
        <div style="margin-bottom: 14px; font-size: .8rem; color: #64748b;">
            ¿Cambiaste de celular? 
            <a href="{{ route('2fa.setup') }}" style="color: #128c7e; font-weight: 600; text-decoration: underline;">
                Configurar nuevo número
            </a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">← Volver al login</button>
        </form>
    </div>
</div>

<script>
(function () {
    var input      = document.getElementById('code');
    var btnVerif   = document.getElementById('btn-verificar');
    var btnReenviar = document.getElementById('btn-reenviar');
    var fill       = document.getElementById('timer-fill');

    /* Auto-habilitar botones */
    input.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
        btnVerif.disabled = this.value.length < 6;
        if (this.value.length === 6) {
            btnVerif.textContent = '⏳ Verificando...';
            document.getElementById('form-otp').submit();
        }
    });

    /* Habilitar reenvío después de 60 s */
    var reenviarTs = Date.now() + 60000;
    function checkReenviar() {
        if (Date.now() >= reenviarTs) {
            btnReenviar.disabled = false;
            btnReenviar.textContent = '🔄 Reenviar código';
        } else {
            var seg = Math.ceil((reenviarTs - Date.now()) / 1000);
            btnReenviar.textContent = '🔄 Reenviar código (' + seg + 's)';
        }
    }
    checkReenviar();
    setInterval(checkReenviar, 1000);

    /* Barra de 10 minutos de validez */
    var expTs = Date.now() + 600000;
    function actualizarBarra() {
        var resto = Math.max(0, expTs - Date.now());
        fill.style.width = Math.round((resto / 600000) * 100) + '%';
        fill.style.background = resto < 60000 ? '#f59e0b' : '#25d366';
    }
    actualizarBarra();
    setInterval(actualizarBarra, 1000);
}());
</script>
</body>
</html>
