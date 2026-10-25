package com.example.prolechc.data.services

import android.content.Context
import androidx.work.BackoffPolicy
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import com.example.prolechc.data.local.ColaAcopiosOffline
import java.util.concurrent.TimeUnit

// ══════════════════════════════════════════════════════════════════════════════
//  SyncAcopiosWorker — Sincronización automática de acopios offline
//
//  WorkManager lo ejecuta solo cuando hay red (aunque la app esté cerrada o
//  el celular se haya reiniciado). Si la red se cae a mitad del envío o el
//  servidor falla (5xx), se reintenta con espera exponencial desde 30 s.
// ══════════════════════════════════════════════════════════════════════════════

class SyncAcopiosWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        val r = ColaAcopiosOffline.enviarPendientes(applicationContext)
        return when {
            // Sin sesión: no tiene sentido reintentar; se reprograma al iniciar sesión
            r.sesionExpirada              -> Result.success()
            r.sinRed || r.restantes > 0   -> Result.retry()
            else                          -> Result.success()
        }
    }

    companion object {
        const val NOMBRE = "sync_acopios"

        /** Programa un envío en cuanto haya red. Seguro de llamar varias veces. */
        fun programar(context: Context) {
            val request = OneTimeWorkRequestBuilder<SyncAcopiosWorker>()
                .setConstraints(
                    Constraints.Builder()
                        .setRequiredNetworkType(NetworkType.CONNECTED)
                        .build()
                )
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 30, TimeUnit.SECONDS)
                .build()

            WorkManager.getInstance(context.applicationContext)
                .enqueueUniqueWork(NOMBRE, ExistingWorkPolicy.APPEND_OR_REPLACE, request)
        }
    }
}
