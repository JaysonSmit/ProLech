import org.jetbrains.kotlin.gradle.dsl.JvmTarget
import java.util.Properties

plugins {
    alias(libs.plugins.androidApplication)
    alias(libs.plugins.composeCompiler)
}

kotlin {
    compilerOptions { jvmTarget = JvmTarget.JVM_11 }
}

// ── Configuración local (local.properties, no se sube al repositorio) ────────
//   prolech.apiUrl=http://192.168.1.5:8000/
//   MAPS_API_KEY=AIza...
val localProps = Properties().apply {
    val f = rootProject.file("local.properties")
    if (f.exists()) f.inputStream().use { load(it) }
}
val apiUrl     = localProps.getProperty("prolech.apiUrl", "http://10.0.2.2:8000/")
val mapsApiKey = localProps.getProperty("MAPS_API_KEY", "")

android {
    namespace  = "com.example.prolechc"
    compileSdk = libs.versions.android.compileSdk.get().toInt()

    defaultConfig {
        applicationId = "com.example.prolechc"
        minSdk        = libs.versions.android.minSdk.get().toInt()
        targetSdk     = libs.versions.android.targetSdk.get().toInt()
        versionCode   = 1
        versionName   = "1.0"

        buildConfigField("String", "API_URL", "\"$apiUrl\"")
        manifestPlaceholders["MAPS_API_KEY"] = mapsApiKey
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
            excludes += "/META-INF/INDEX.LIST"
            excludes += "/META-INF/DEPENDENCIES"
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    buildFeatures {
        compose     = true
        buildConfig = true
    }
}

dependencies {
    // ── Módulo shared KMP ─────────────────────────────────────────────────────
    implementation(project(":app:shared"))

    // ── Activity ──────────────────────────────────────────────────────────────
    implementation(libs.androidx.activity.compose)

    // ── Compose BOM + artefactos Android puros ────────────────────────────────
    implementation(platform(libs.compose.bom))
    implementation(libs.android.compose.ui)
    implementation(libs.android.compose.ui.graphics)
    implementation(libs.android.compose.ui.tooling.preview)
    implementation(libs.android.compose.foundation)
    implementation(libs.android.compose.animation)
    implementation(libs.android.compose.material3)
    implementation(libs.android.compose.runtime)
    debugImplementation(libs.android.compose.ui.tooling)

    // ── Lifecycle Android (para viewModel() y collectAsStateWithLifecycle) ─────
    implementation(libs.android.lifecycle.viewmodel.compose)
    implementation(libs.android.lifecycle.runtime.compose)

    // ── Retrofit + OkHttp + Gson ──────────────────────────────────────────────
    implementation(libs.retrofit.core)
    implementation(libs.retrofit.gson)
    implementation(libs.okhttp.core)
    implementation(libs.okhttp.logging)
    implementation(libs.gson)

    // ── DataStore ─────────────────────────────────────────────────────────────
    implementation(libs.datastore.preferences)

    // ── Coroutines Android ────────────────────────────────────────────────────
    implementation(libs.kotlinx.coroutinesAndroid)

    // ── GPS + Maps ────────────────────────────────────────────────────────────
    implementation(libs.play.services.location)
    implementation(libs.maps.compose)

    // ── Accompanist Permissions ───────────────────────────────────────────────
    implementation(libs.accompanist.permissions)

    // ── ML Kit OCR — lectura automática ticket LACTOMAT ───────────────────────
    implementation("com.google.mlkit:text-recognition:16.0.1")

    // ── CameraX — captura de foto para OCR ───────────────────────────────────
    implementation("androidx.camera:camera-core:1.4.2")
    implementation("androidx.camera:camera-camera2:1.4.2")
    implementation("androidx.camera:camera-lifecycle:1.4.2")
    implementation("androidx.camera:camera-view:1.4.2")

    // ── WorkManager — sincronización automática de acopios offline ───────────
    implementation("androidx.work:work-runtime-ktx:2.10.1")
}
