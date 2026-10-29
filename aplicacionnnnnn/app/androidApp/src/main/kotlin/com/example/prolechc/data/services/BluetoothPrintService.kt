package com.example.prolechc.data.services

import android.bluetooth.BluetoothAdapter
import android.bluetooth.BluetoothDevice
import android.bluetooth.BluetoothSocket
import android.content.Context
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.IOException
import java.io.OutputStream
import java.util.UUID

// ══════════════════════════════════════════════════════════════════════════════
//  BluetoothPrintService
//
//  Conecta a una impresora térmica Bluetooth (ESC/POS) y envía la boleta
//  de acopio en texto plano con comandos ESC/POS básicos.
//
//  UUID estándar SPP (Serial Port Profile) — compatible con la mayoría de
//  impresoras térmicas Bluetooth (Xprinter, Rongta, Epson mobi, etc.).
//
//  Uso desde RegistroExitosoScreen:
//    BluetoothPrintService.imprimirBoleta(context, datosBoleta) { resultado ->
//        when (resultado) {
//            is PrintResult.Exito    -> mostrar "Boleta impresa"
//            is PrintResult.Error    -> mostrar mensaje de error
//            is PrintResult.SinImpresora -> pedir al usuario activar Bluetooth
//        }
//    }
// ══════════════════════════════════════════════════════════════════════════════

sealed class PrintResult {
    object Exito : PrintResult()
    object SinImpresora : PrintResult()
    data class Error(val mensaje: String) : PrintResult()
}

data class DatosBoleta(
    val ticketNumero: String,
    val conductorNombre: String,
    val ganaderoNombre: String,
    val dniGanadero: String,
    val litros: Double,
    val porcentajeAgua: String,
    val acidezPh: String,
    val densidad: String,
    val estado: String,             // "APROBADA" | "RECHAZADA" | "OBSERVADA"
    val tieneDescuento: Boolean,
    val rutaNombre: String,
    val fechaHora: String
)

object BluetoothPrintService {

    // UUID SPP estándar para impresoras térmicas Bluetooth
    private val SPP_UUID: UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB")

    // Comandos ESC/POS básicos
    private val ESC_INIT        = byteArrayOf(0x1B, 0x40)           // Inicializar impresora
    private val ESC_BOLD_ON     = byteArrayOf(0x1B, 0x45, 0x01)     // Negrita ON
    private val ESC_BOLD_OFF    = byteArrayOf(0x1B, 0x45, 0x00)     // Negrita OFF
    private val ESC_ALIGN_CTR   = byteArrayOf(0x1B, 0x61, 0x01)     // Alinear centro
    private val ESC_ALIGN_LEFT  = byteArrayOf(0x1B, 0x61, 0x00)     // Alinear izquierda
    private val ESC_SIZE_2X     = byteArrayOf(0x1D, 0x21, 0x11)     // Doble tamaño
    private val ESC_SIZE_NORMAL = byteArrayOf(0x1D, 0x21, 0x00)     // Tamaño normal
    private val ESC_CUT         = byteArrayOf(0x1D, 0x56, 0x41, 0x03) // Cortar papel

    private fun newline() = "\n".toByteArray()
    private fun line(text: String) = (text + "\n").toByteArray(Charsets.UTF_8)
    private fun separator() = ("-".repeat(32) + "\n").toByteArray()

    /**
     * Imprime la boleta en la impresora Bluetooth emparejada.
     * Busca automáticamente la primera impresora disponible en los dispositivos emparejados.
     * Si hay múltiples, usa la primera cuyo nombre contenga palabras clave de impresora.
     */
    suspend fun imprimirBoleta(
        context: Context,
        datos: DatosBoleta
    ): PrintResult = withContext(Dispatchers.IO) {

        val bluetoothAdapter = BluetoothAdapter.getDefaultAdapter()
            ?: return@withContext PrintResult.Error("Este dispositivo no tiene Bluetooth.")

        if (!bluetoothAdapter.isEnabled) {
            return@withContext PrintResult.SinImpresora
        }

        @Suppress("MissingPermission")
        val impresora: BluetoothDevice? = bluetoothAdapter.bondedDevices
            ?.firstOrNull { device ->
                val nombre = device.name?.lowercase() ?: ""
                // Detectar impresoras comunes por nombre
                nombre.contains("print") || nombre.contains("pos") ||
                nombre.contains("xprinter") || nombre.contains("rongta") ||
                nombre.contains("epson") || nombre.contains("thermal") ||
                nombre.contains("btp")
            }
            ?: bluetoothAdapter.bondedDevices?.firstOrNull() // fallback: primer dispositivo

        if (impresora == null) {
            return@withContext PrintResult.Error(
                "No se encontró ninguna impresora Bluetooth emparejada.\n" +
                "Empareja la impresora en los ajustes de Bluetooth del dispositivo."
            )
        }

        var socket: BluetoothSocket? = null
        return@withContext try {
            @Suppress("MissingPermission")
            socket = impresora.createRfcommSocketToServiceRecord(SPP_UUID)
            bluetoothAdapter.cancelDiscovery()
            socket.connect()

            val stream: OutputStream = socket.outputStream
            escribirBoleta(stream, datos)
            stream.flush()

            PrintResult.Exito
        } catch (e: IOException) {
            PrintResult.Error("No se pudo conectar a la impresora: ${e.message}")
        } finally {
            try { socket?.close() } catch (_: IOException) {}
        }
    }

    private fun escribirBoleta(stream: OutputStream, d: DatosBoleta) {
        // ── Cabecera ──────────────────────────────────────────────────────────
        stream.write(ESC_INIT)
        stream.write(ESC_ALIGN_CTR)
        stream.write(ESC_BOLD_ON)
        stream.write(ESC_SIZE_2X)
        stream.write(line("PROLECH"))
        stream.write(ESC_SIZE_NORMAL)
        stream.write(line("Planta Quesera - Huata"))
        stream.write(line("BOLETA DE ACOPIO"))
        stream.write(ESC_BOLD_OFF)
        stream.write(separator())

        // ── Ticket y estado ───────────────────────────────────────────────────
        stream.write(ESC_ALIGN_CTR)
        stream.write(ESC_BOLD_ON)
        stream.write(line("TICKET: ${d.ticketNumero}"))
        stream.write(ESC_BOLD_OFF)

        val estadoStr = when (d.estado.uppercase()) {
            "RECHAZADA", "RECHAZADO" -> "*** RECHAZADO ***"
            "OBSERVADA",  "OBSERVADO"  -> "** OBSERVADO **"
            else                         -> "APROBADO"
        }
        stream.write(line(estadoStr))
        if (d.tieneDescuento) stream.write(line(">> CON DESCUENTO (agua ~1%) <<"))
        stream.write(separator())

        // ── Datos del acopio ──────────────────────────────────────────────────
        stream.write(ESC_ALIGN_LEFT)
        stream.write(line("Fecha/Hora: ${d.fechaHora}"))
        stream.write(line("Conductor:  ${d.conductorNombre}"))
        stream.write(line("Ruta:       ${d.rutaNombre}"))
        stream.write(newline())
        stream.write(ESC_BOLD_ON)
        stream.write(line("Productor:  ${d.ganaderoNombre}"))
        stream.write(ESC_BOLD_OFF)
        stream.write(line("DNI:        ${d.dniGanadero}"))
        stream.write(separator())

        // ── Análisis de calidad ───────────────────────────────────────────────
        stream.write(line("ANALISIS DE CALIDAD:"))
        stream.write(ESC_BOLD_ON)
        stream.write(line("Litros:     ${"%.2f".format(d.litros)} L"))
        stream.write(ESC_BOLD_OFF)
        if (d.porcentajeAgua.isNotBlank()) stream.write(line("% Agua:     ${d.porcentajeAgua}%"))
        if (d.acidezPh.isNotBlank())       stream.write(line("Acidez pH:  ${d.acidezPh} pH"))
        if (d.densidad.isNotBlank())        stream.write(line("Densidad:   ${d.densidad} g/mL"))
        stream.write(separator())

        // ── Firma ─────────────────────────────────────────────────────────────
        stream.write(ESC_ALIGN_CTR)
        stream.write(newline())
        stream.write(line("Firma del Productor:"))
        stream.write(newline())
        stream.write(line("_________________________"))
        stream.write(newline())
        stream.write(line(d.ganaderoNombre))
        stream.write(newline())
        stream.write(line("ProLech Sistema - prolech.com"))
        stream.write(newline())
        stream.write(newline())
        stream.write(ESC_CUT)
    }
}
