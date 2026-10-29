<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProLech · Ingresar al Sistema</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --brand:       #1f6feb;
        --brand-mid:   #1858c4;
        --brand-light: #3b8bff;
        --accent:      #f59e0b;
        --bg:          #f0f7f1;
        --card:        #ffffff;
        --border:      #d1e7d9;
        --text:        #0f172a;
        --text-muted:  #64748b;
        --red:         #dc2626;
        --red-bg:      #fef2f2;
    }

    html, body {
        height: 100%;
        font-family: 'Inter', -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    body {
        background: var(--bg);
        display: grid;
        grid-template-columns: 1fr 480px;
        min-height: 100vh;
    }

    /* ── PANEL IZQUIERDO — hero verde ─────────────────────────── */
    .hero {
        background: linear-gradient(145deg, #0b1f44 0%, #123a7a 55%, #1f6feb 100%);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        padding: 60px;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: -80px; right: -80px;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: rgba(255,255,255,.04);
    }

    .hero::after {
        content: '';
        position: absolute;
        bottom: -120px; left: -60px;
        width: 500px; height: 500px;
        border-radius: 50%;
        background: rgba(255,255,255,.03);
    }

    .hero-logo {
        font-size: 2.6rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -1px;
        margin-bottom: 6px;
        position: relative;
        z-index: 1;
    }

    .hero-logo span { color: #7cc4ff; }

    .hero-sub {
        font-size: .9rem;
        color: rgba(255,255,255,.6);
        margin-bottom: 48px;
        position: relative;
        z-index: 1;
        letter-spacing: .5px;
    }

    .hero-features {
        display: flex;
        flex-direction: column;
        gap: 20px;
        position: relative;
        z-index: 1;
    }

    .hero-feature {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .hero-feature-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: rgba(255,255,255,.1);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .hero-feature-text h4 {
        color: #fff;
        font-size: .88rem;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .hero-feature-text p {
        color: rgba(255,255,255,.55);
        font-size: .78rem;
        line-height: 1.4;
    }

    .hero-badge {
        position: absolute;
        bottom: 32px; left: 60px;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 20px;
        padding: 6px 14px;
        font-size: .72rem;
        color: rgba(255,255,255,.6);
        z-index: 1;
    }

    .hero-badge strong { color: #7cc4ff; }

    /* ── PANEL DERECHO — formulario ───────────────────────────── */
    .login-panel {
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 48px 52px;
        position: relative;
    }

    .login-panel-top {
        position: absolute;
        top: 24px; right: 24px;
        font-size: .78rem;
        color: var(--text-muted);
    }

    .login-panel-top a {
        color: var(--brand);
        font-weight: 600;
        text-decoration: none;
        padding: 6px 14px;
        border: 1.5px solid var(--border);
        border-radius: 20px;
        transition: background .15s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .login-panel-top a:hover { background: var(--bg); }

    .login-header {
        margin-bottom: 36px;
    }

    .login-header .eyebrow {
        font-size: .72rem;
        font-weight: 700;
        color: var(--brand-light);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        margin-bottom: 8px;
    }

    .login-header h1 {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -.5px;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    .login-header p {
        font-size: .86rem;
        color: var(--text-muted);
        line-height: 1.5;
    }

    /* ── FORM FIELDS ──────────────────────────────────────────── */
    .field {
        margin-bottom: 18px;
    }

    .field label {
        display: block;
        font-size: .72rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 6px;
    }

    .input-wrap {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 13px; top: 50%;
        transform: translateY(-50%);
        font-size: .9rem;
        pointer-events: none;
        opacity: .5;
    }

    .field input {
        width: 100%;
        padding: 11px 13px 11px 38px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: .9rem;
        font-family: inherit;
        color: var(--text);
        background: #f8fafc;
        transition: border-color .15s, box-shadow .15s, background .15s;
        outline: none;
    }

    .field input:focus {
        border-color: var(--brand);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(31,111,235,.1);
    }

    .toggle-pw {
        position: absolute;
        right: 12px; top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        font-size: .9rem;
        padding: 4px;
        color: var(--text-muted);
        opacity: .6;
        transition: opacity .15s;
    }

    .toggle-pw:hover { opacity: 1; }

    /* ── REMEMBER + SUBMIT ────────────────────────────────────── */
    .remember-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
    }

    .remember-row input[type="checkbox"] {
        width: 16px; height: 16px;
        accent-color: var(--brand);
        cursor: pointer;
        flex-shrink: 0;
    }

    .remember-row label {
        font-size: .82rem;
        color: var(--text-muted);
        cursor: pointer;
        font-weight: 500;
    }

    .btn-login {
        width: 100%;
        padding: 13px;
        background: var(--brand);
        color: #fff;
        font-weight: 700;
        font-size: .95rem;
        font-family: inherit;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: background .15s, transform .1s, box-shadow .15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        letter-spacing: .2px;
    }

    .btn-login:hover {
        background: var(--brand-mid);
        box-shadow: 0 4px 12px rgba(31,111,235,.25);
    }

    .btn-login:active { transform: scale(.99); }

    /* ── ERROR ────────────────────────────────────────────────── */
    .alert-error {
        background: var(--red-bg);
        border: 1px solid #fca5a5;
        border-left: 3px solid var(--red);
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: .84rem;
        color: #991b1b;
        font-weight: 500;
        animation: shake .35s ease;
    }

    /* ── QUICK ACCESS ─────────────────────────────────────────── */
    .quick-access {
        margin-top: 28px;
        padding-top: 24px;
        border-top: 1px solid #f1f5f9;
    }

    .quick-access-title {
        font-size: .72rem;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .quick-access-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
    }

    .quick-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .quick-chip {
        padding: 6px 12px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: .76rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all .15s;
        display: flex;
        align-items: center;
        gap: 5px;
        user-select: none;
    }

    .quick-chip:hover {
        border-color: var(--brand);
        color: var(--brand);
        background: #eef5ff;
    }

    .quick-chip .role {
        font-size: .66rem;
        background: #e2e8f0;
        border-radius: 4px;
        padding: 1px 5px;
        color: var(--text-muted);
    }

    /* ── FOOTER DEL PANEL ─────────────────────────────────────── */
    .login-footer {
        position: absolute;
        bottom: 24px; left: 52px; right: 52px;
        font-size: .73rem;
        color: var(--text-muted);
        text-align: center;
    }

    /* ── LOADING STATE ────────────────────────────────────────── */
    .btn-login.loading {
        pointer-events: none;
        opacity: .8;
    }

    .spinner {
        display: none;
        width: 16px; height: 16px;
        border: 2px solid rgba(255,255,255,.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }

    .btn-login.loading .spinner { display: block; }
    .btn-login.loading .btn-text { display: none; }

    /* ── RESPONSIVE ───────────────────────────────────────────── */
    @media (max-width: 860px) {
        body { grid-template-columns: 1fr; }
        .hero { display: none; }
        .login-panel { padding: 40px 28px; }
        .login-panel-top { top: 16px; right: 16px; }
        .login-footer { left: 28px; right: 28px; }
    }

    /* ── ANIMATIONS ───────────────────────────────────────────── */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%       { transform: translateX(-6px); }
        40%       { transform: translateX(6px); }
        60%       { transform: translateX(-4px); }
        80%       { transform: translateX(4px); }
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .login-panel > * {
        animation: fadeInUp .4s ease both;
    }

    .login-panel > *:nth-child(2) { animation-delay: .05s; }
    .login-panel > *:nth-child(3) { animation-delay: .1s; }
    </style>
</head>
<body>
@include('partials.pantalla-carga')

    {{-- ── PANEL IZQUIERDO ── --}}
    <div class="hero">
        <div class="hero-logo">🧀 Pro<span>Lech</span></div>
        <div class="hero-sub">SISTEMA ERP AGRÍCOLA · HUATA, PUNO</div>

        <div class="hero-features">
            <div class="hero-feature">
                <div class="hero-feature-icon">🥛</div>
                <div class="hero-feature-text">
                    <h4>Control de Acopio</h4>
                    <p>Registro de litros, calidad y rutas de recolección en tiempo real.</p>
                </div>
            </div>
            <div class="hero-feature">
                <div class="hero-feature-icon">🏭</div>
                <div class="hero-feature-text">
                    <h4>Producción por Recetas</h4>
                    <p>Lotes de queso con trazabilidad completa de insumos y costos.</p>
                </div>
            </div>
            <div class="hero-feature">
                <div class="hero-feature-icon">🛒</div>
                <div class="hero-feature-text">
                    <h4>Tienda & Ventas</h4>
                    <p>Catálogo público con stock en tiempo real y múltiples métodos de pago.</p>
                </div>
            </div>
            <div class="hero-feature">
                <div class="hero-feature-icon">📊</div>
                <div class="hero-feature-text">
                    <h4>Liquidaciones & Reportes</h4>
                    <p>Pagos a ganaderos, auditorías y reportes gerenciales automáticos.</p>
                </div>
            </div>
        </div>

        <div class="hero-badge">
            Versión 2.0 · <strong>ProLech ERP</strong> · Sistema activo
        </div>
    </div>

    {{-- ── PANEL DERECHO ── --}}
    <div class="login-panel">

        {{-- Link a la tienda pública --}}
        <div class="login-panel-top">
            <a href="{{ route('tienda.index') }}">
                🛒 Ver Tienda
            </a>
        </div>

        {{-- Encabezado --}}
        <div class="login-header">
            <div class="eyebrow">✦ Bienvenido de vuelta</div>
            <h1>Ingresar al Sistema</h1>
            <p>Usa tu correo y contraseña asignados por la administración de ProLech.</p>
        </div>

        {{-- Error --}}
        @if($errors->any())
            <div class="alert-error">
                <span>⚠️</span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Formulario --}}
        <form action="{{ route('login.post') }}" method="POST" id="login-form">
            @csrf

            <div class="field">
                <label for="email">Correo electrónico</label>
                <div class="input-wrap">
                    <span class="input-icon">✉️</span>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           placeholder="admin@prolech.com"
                           required autofocus autocomplete="email">
                </div>
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <div class="input-wrap">
                    <span class="input-icon">🔑</span>
                    <input type="password" id="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePassword()" id="pw-toggle"
                            aria-label="Mostrar contraseña">👁</button>
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Recordarme en este dispositivo</label>
            </div>

            <button type="submit" class="btn-login" id="submit-btn">
                <span class="btn-text">Ingresar al Sistema</span>
                <div class="spinner"></div>
            </button>
        </form>

        {{-- Acceso rápido (demo): SOLO en desarrollo, nunca publicar contraseñas en producción --}}
        @if(app()->environment('local'))
        <div class="quick-access">
            <div class="quick-access-title">Acceso rápido demo</div>
            <div class="quick-chips">
                <div class="quick-chip" onclick="fillLogin('admin@prolech.com','prolech2024')">
                    👑 Admin <span class="role">admin</span>
                </div>
                <div class="quick-chip" onclick="fillLogin('calidad@prolech.com','prolech2024')">
                    🔬 Calidad <span class="role">calidad</span>
                </div>
                <div class="quick-chip" onclick="fillLogin('produccion@prolech.com','prolech2024')">
                    🏭 Producción <span class="role">jefe</span>
                </div>
                <div class="quick-chip" onclick="fillLogin('despacho@prolech.com','prolech2024')">
                    🚚 Despacho <span class="role">desp.</span>
                </div>
            </div>
        </div>
        @endif

        <div class="login-footer">
            ProLech ERP &copy; {{ date('Y') }} · Todos los derechos reservados
        </div>
    </div>

<script>
// ── Toggle contraseña ────────────────────────────────────────────
function togglePassword() {
    const input  = document.getElementById('password');
    const toggle = document.getElementById('pw-toggle');
    const isText = input.type === 'text';
    input.type   = isText ? 'password' : 'text';
    toggle.textContent = isText ? '👁' : '🙈';
}

// ── Relleno rápido de credenciales demo ─────────────────────────
function fillLogin(email, password) {
    document.getElementById('email').value    = email;
    document.getElementById('password').value = password;
    document.getElementById('email').focus();
    // Pequeño feedback visual
    const chips = document.querySelectorAll('.quick-chip');
    chips.forEach(c => c.style.background = '');
    event.currentTarget.style.background = '#eef5ff';
    event.currentTarget.style.borderColor = '#1f6feb';
    event.currentTarget.style.color = '#1f6feb';
    setTimeout(() => {
        event.currentTarget.style.background = '';
        event.currentTarget.style.borderColor = '';
        event.currentTarget.style.color = '';
    }, 1200);
}

// ── Loading state al enviar ──────────────────────────────────────
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('submit-btn');
    btn.classList.add('loading');
});
</script>

</body>
</html>
