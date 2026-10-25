package com.example.prolechc.data.api

import android.content.Context
import com.example.prolechc.BuildConfig
import com.example.prolechc.data.local.TokenDataStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

// ══════════════════════════════════════════════════════════════════════════════
//  RetrofitClient — Singleton del cliente HTTP
//
//  BASE_URL apunta a 10.0.2.2:8000 (emulador de Android Studio → localhost PC).
//  En producción cambia BASE_URL por tu dominio HTTPS y elimina
//  android:usesCleartextTraffic="true" del Manifest.
//
//  AuthInterceptor inyecta "Authorization: Bearer <token>" en cada request
//  que no sea el de login (el token se lee desde DataStore en el momento
//  de la petición para tener siempre el valor más reciente).
// ══════════════════════════════════════════════════════════════════════════════

object RetrofitClient {

    // ── Configuración ─────────────────────────────────────────────────────────

    /** Se define en local.properties (prolech.apiUrl); por defecto 10.0.2.2:8000 del emulador. */
    val BASE_URL: String = BuildConfig.API_URL

    // ── Inicialización lazy (necesita Context para DataStore) ─────────────────

    @Volatile
    private var instance: ProLechApiService? = null

    /**
     * Devuelve la instancia compartida de [ProLechApiService].
     * Llama a esto desde tu ViewModel o repositorio pasando el applicationContext.
     */
    fun getInstance(context: Context): ProLechApiService {
        return instance ?: synchronized(this) {
            instance ?: buildService(context.applicationContext).also { instance = it }
        }
    }

    private fun buildService(context: Context): ProLechApiService {
        val tokenStore = TokenDataStore(context)

        val okHttpClient = OkHttpClient.Builder()
            .addInterceptor(authInterceptor(tokenStore))
            .addInterceptor(loggingInterceptor())
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()

        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(ProLechApiService::class.java)
    }

    // ── Interceptores ─────────────────────────────────────────────────────────

    /**
     * Inyecta el Bearer Token en cada petición.
     * Omite el header en /api/login para no interferir con el flujo de auth.
     */
    private fun authInterceptor(tokenStore: TokenDataStore): Interceptor = Interceptor { chain ->
        val original = chain.request()

        // No agregar token en el endpoint de login
        if (original.url.encodedPath.endsWith("/api/login")) {
            return@Interceptor chain.proceed(original)
        }

        // Leer el token de DataStore de forma síncrona (el interceptor no es suspendible)
        val token = runBlocking { tokenStore.tokenFlow.first() }

        val request = if (token != null) {
            original.newBuilder()
                .header("Authorization", "Bearer $token")
                .header("Accept", "application/json")
                .build()
        } else {
            original.newBuilder()
                .header("Accept", "application/json")
                .build()
        }

        chain.proceed(request)
    }

    /**
     * Log de peticiones HTTP en consola (solo en builds debug).
     * Muestra headers + body para facilitar el desarrollo.
     */
    private fun loggingInterceptor(): HttpLoggingInterceptor =
        HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }
}
