package com.example.prolechc

import android.Manifest
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Bundle
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.activity.result.contract.ActivityResultContracts
import androidx.activity.viewModels
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.core.content.ContextCompat
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.lifecycleScope
import androidx.work.WorkInfo
import androidx.work.WorkManager
import com.example.prolechc.data.local.ColaAcopiosOffline
import com.example.prolechc.data.services.FotoEvidenciaService
import com.example.prolechc.data.services.SyncAcopiosWorker
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import com.example.prolechc.data.repository.AcopioRepositoryAdapter
import com.example.prolechc.data.repository.AuthRepositoryAdapter
import com.example.prolechc.data.repository.MiRutaRepositoryAdapter
import com.example.prolechc.navigation.ProLechNavGraph
import com.example.prolechc.ui.screens.MapaChoferScreen
import com.example.prolechc.ui.screens.NotificacionesScreen
import com.example.prolechc.ui.screens.ReunionesScreen
import com.example.prolechc.ui.screens.SolicitarRutaScreen
import com.example.prolechc.ui.theme.ProLechTheme
import com.example.prolechc.ui.viewmodels.NotificacionesViewModel
import com.example.prolechc.viewmodel.ProLechViewModel

// ══════════════════════════════════════════════════════════════════════════════
//  MainActivity — Entry point Android
//
//  Instancia y conecta:
//    1. ProLechViewModel    — lógica principal (acopios, ruta, historial)
//    2. NotificacionesViewModel — badge de campana + pantalla de notificaciones
//
//  El NotificacionesViewModel corre en la Activity para que el conteo de
//  notificaciones esté disponible aunque el usuario esté en cualquier pantalla.
// ══════════════════════════════════════════════════════════════════════════════

class MainActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)

        // ── Repositorios Android ──────────────────────────────────────────────
        val authRepo   = AuthRepositoryAdapter(applicationContext)
        val miRutaRepo = MiRutaRepositoryAdapter(applicationContext)
        val acopioRepo = AcopioRepositoryAdapter(applicationContext)

        // ── ViewModels ────────────────────────────────────────────────────────
        val vm: ProLechViewModel by viewModels {
            ProLechViewModel.factory(authRepo, miRutaRepo, acopioRepo)
        }

        val notifVm: NotificacionesViewModel by viewModels {
            NotificacionesViewModel.Factory(application)
        }

        // ── Acopios offline: si quedaron pendientes, programar su envío ───────
        lifecycleScope.launch {
            if (ColaAcopiosOffline.hayPendientes(applicationContext)) {
                SyncAcopiosWorker.programar(applicationContext)
            }
        }

        // Cuando el sincronizador automático termina, refrescar la UI
        lifecycleScope.launch {
            WorkManager.getInstance(applicationContext)
                .getWorkInfosForUniqueWorkFlow(SyncAcopiosWorker.NOMBRE)
                .collect { infos ->
                    vm.refrescarPendientes()
                    if (infos.any { it.state == WorkInfo.State.SUCCEEDED }) vm.cargarHistorial()
                }
        }

        setContent {
            ProLechTheme {

                // Badge: conteo de notificaciones no leídas — reactivo
                val notifState by notifVm.uiState.collectAsStateWithLifecycle()
                val badgeCount = notifState.noLeidasCount

                // ── Cámara: foto de evidencia de leche rechazada ──────────────
                var fotoUri by remember { mutableStateOf<Uri?>(null) }
                val camaraLauncher = rememberLauncherForActivityResult(
                    ActivityResultContracts.TakePicture()
                ) { tomada ->
                    val uri = fotoUri
                    if (tomada && uri != null) {
                        lifecycleScope.launch {
                            val base64 = withContext(Dispatchers.Default) {
                                FotoEvidenciaService.aBase64(applicationContext, uri)
                            }
                            vm.onFotoEvidencia(base64)
                        }
                    }
                }
                val abrirCamara = {
                    val uri = FotoEvidenciaService.crearUri(applicationContext)
                    fotoUri = uri
                    camaraLauncher.launch(uri)
                }
                val permisoCamaraLauncher = rememberLauncherForActivityResult(
                    ActivityResultContracts.RequestPermission()
                ) { concedido ->
                    if (concedido) abrirCamara()
                    else Toast.makeText(this, "Se necesita permiso de cámara para la evidencia", Toast.LENGTH_LONG).show()
                }

                ProLechNavGraph(
                    vm                  = vm,
                    notificacionesCount = badgeCount,

                    onTomarFotoEvidencia = {
                        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA)
                            == PackageManager.PERMISSION_GRANTED) abrirCamara()
                        else permisoCamaraLauncher.launch(Manifest.permission.CAMERA)
                    },

                    // ── Pantalla de notificaciones (Android-specific) ──────────
                    notificacionesScreen = { onBack ->
                        NotificacionesScreen(onBack = onBack)
                    },

                    // ── Mapa GPS ──────────────────────────────────────────────
                    mapaChoferScreen = { onBack ->
                        MapaChoferScreen(onBack = onBack, mainVm = vm)
                    },

                    // ── Solicitar cambio de ruta ──────────────────────────────
                    solicitarRutaScreen = { onBack ->
                        SolicitarRutaScreen(onBack = onBack)
                    },

                    // ── Reuniones / capacitaciones ────────────────────────────
                    reunionesScreen = { onBack ->
                        ReunionesScreen(onBack = onBack)
                    },

                    // ── Exportar boleta PDF ───────────────────────────────────
                    onExportarPdf = { acopioId ->
                        val acopio = vm.historialCompleto.find { it.id == acopioId }
                        if (acopio != null) {
                            com.example.prolechc.data.services.PdfBoletaService
                                .generarYCompartir(this@MainActivity, acopio)
                        }
                    },

                    // ── Escanear ticket LACTOMAT (OCR) ────────────────────────
                    escanearTicketScreen = { _, onCancelar ->
                        com.example.prolechc.ui.screens.EscanearTicketScreen(
                            onResultado = { resultado ->
                                val r = resultado as com.example.prolechc.data.services.ResultadoLactomat
                                vm.aplicarResultadoOcr(
                                    grasa            = r.grasa,
                                    sng              = r.sng,
                                    densidad         = r.densidad,
                                    proteina         = r.proteina,
                                    lactosa          = r.lactosa,
                                    sales            = r.sales,
                                    totalSolidos     = r.totalSolidos,
                                    aguaAniadida     = r.aguaAniadida,
                                    puntoCongelacion = r.puntoCongelacion,
                                    ph               = r.ph
                                )
                                // Volver al formulario con datos rellenados
                                onCancelar()
                            },
                            onCancelar = onCancelar
                        )
                    },

                    // ── Impresión Bluetooth ───────────────────────────────────
                    onImprimirBoleta = { ticketNumero, conductorNombre, ganaderoNombre,
                                        dniGanadero, litros, porcentajeAgua, acidezPh,
                                        densidad, estado, tieneDescuento, rutaNombre ->
                        val datos = com.example.prolechc.data.services.DatosBoleta(
                            ticketNumero    = ticketNumero,
                            conductorNombre = conductorNombre,
                            ganaderoNombre  = ganaderoNombre,
                            dniGanadero     = dniGanadero,
                            litros          = litros,
                            porcentajeAgua  = porcentajeAgua,
                            acidezPh        = acidezPh,
                            densidad        = densidad,
                            estado          = estado,
                            tieneDescuento  = tieneDescuento,
                            rutaNombre      = rutaNombre,
                            fechaHora       = java.time.LocalDateTime.now()
                                .format(java.time.format.DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"))
                        )
                        when (val r = com.example.prolechc.data.services.BluetoothPrintService
                                .imprimirBoleta(applicationContext, datos)) {
                            is com.example.prolechc.data.services.PrintResult.Exito ->
                                "✓ Boleta impresa correctamente"
                            is com.example.prolechc.data.services.PrintResult.SinImpresora ->
                                "Activa el Bluetooth y empareja la impresora"
                            is com.example.prolechc.data.services.PrintResult.Error ->
                                r.mensaje
                        }
                    }
                )
            }
        }
    }
}
