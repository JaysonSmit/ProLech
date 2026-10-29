{{-- Pantalla de carga con el logo: completa la primera vez de la sesión; luego solo una barra fina arriba --}}
<style>
    #pl-carga { position: fixed; inset: 0; z-index: 99999; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px;
                background: radial-gradient(circle at 50% 40%, #ffffff 0%, #f4f6fb 60%, #e9f0fc 100%); transition: opacity .5s ease, visibility .5s ease; }
    #pl-carga img { width: min(320px, 70vw); animation: plEntrar .8s cubic-bezier(.2,.8,.2,1) both, plRespirar 1.8s ease-in-out .8s infinite; filter: drop-shadow(0 18px 30px rgba(29,63,158,.18)); }
    #pl-carga .pl-barra { width: 170px; height: 5px; border-radius: 99px; background: #e2e8f0; overflow: hidden; }
    #pl-carga .pl-barra span { display: block; height: 100%; width: 40%; border-radius: 99px; background: linear-gradient(90deg, #1d3f9e, #e53935, #f2b705); animation: plCorrer 1.1s ease-in-out infinite; }
    #pl-carga small { font: 600 13px/1 system-ui, sans-serif; color: #64748b; letter-spacing: .5px; animation: plEntrar .8s .2s both; }
    #pl-carga.fuera { opacity: 0; visibility: hidden; }
    #pl-carga.breve { background: transparent; pointer-events: none; justify-content: flex-start; }
    #pl-carga.breve img, #pl-carga.breve small { display: none; }
    #pl-carga.breve .pl-barra { width: 100%; height: 3px; border-radius: 0; background: transparent; }
    @keyframes plEntrar { from { opacity: 0; transform: translateY(14px) scale(.85); } to { opacity: 1; transform: none; } }
    @keyframes plRespirar { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.04); } }
    @keyframes plCorrer { 0% { transform: translateX(-110%); } 100% { transform: translateX(260%); } }
    @media (prefers-reduced-motion: reduce) { #pl-carga * { animation: none !important; } }
</style>
<div id="pl-carga" aria-hidden="true">
    <img src="{{ asset('images/logo-ecolacteos.png') }}" alt="Ecolácteos Huata">
    <div class="pl-barra"><span></span></div>
    <small>ProLech · Huata, Puno</small>
</div>
<script>
(function () {
    var el = document.getElementById('pl-carga'), clave = 'pl-carga-vista', primera = true;
    try { primera = !sessionStorage.getItem(clave); sessionStorage.setItem(clave, '1'); } catch (e) {}
    if (!primera) el.classList.add('breve');
    var inicio = Date.now(), minimo = primera ? 1200 : 0;
    function ocultar() { setTimeout(function () { el.classList.add('fuera'); }, Math.max(0, minimo - (Date.now() - inicio))); }
    if (document.readyState === 'complete') ocultar(); else window.addEventListener('load', ocultar);
    setTimeout(function () { el.classList.add('fuera'); }, 4000);   // por si una imagen tarda demasiado
})();
</script>
