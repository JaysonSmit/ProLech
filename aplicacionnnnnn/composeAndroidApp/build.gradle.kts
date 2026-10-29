// APK de la app de acopio offline-first. Todo el código vive en :composeApp
// (librería KMP); este módulo solo empaqueta el ícono, el manifiesto y la config de red.
import java.util.Properties

plugins {
    alias(libs.plugins.androidApplication)
}

// Firma del release: keystore.properties (fuera de git). Sin él, el release sale sin firmar.
val firma = Properties().apply {
    rootProject.file("keystore.properties").takeIf { it.exists() }?.inputStream()?.use { load(it) }
}

android {
    namespace  = "com.example.prolechc.acopio"
    compileSdk = libs.versions.android.compileSdk.get().toInt()

    defaultConfig {
        // Identificador propio: OpenStreetMap bloquea los mosaicos a las apps "com.example.*"
        // (el mapa salía "Access blocked") y Google Play tampoco las acepta.
        // Distinto de :app:androidApp para poder instalar ambas en el mismo celular.
        applicationId = "pe.prolech.acopio"
        minSdk        = libs.versions.android.minSdk.get().toInt()
        targetSdk     = libs.versions.android.targetSdk.get().toInt()
        versionCode   = 3
        versionName   = "2.1"
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
            excludes += "/META-INF/INDEX.LIST"
            excludes += "/META-INF/DEPENDENCIES"
        }
    }

    signingConfigs {
        if (firma.getProperty("storeFile") != null) {
            create("release") {
                storeFile     = rootProject.file(firma.getProperty("storeFile"))
                storePassword = firma.getProperty("storePassword")
                keyAlias      = firma.getProperty("keyAlias")
                keyPassword   = firma.getProperty("keyPassword")
            }
        }
    }

    buildTypes {
        release {
            // R8: reduce y ofusca el código (APK más liviano y más difícil de copiar)
            isMinifyEnabled   = true
            isShrinkResources = true
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            signingConfig = signingConfigs.findByName("release")
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }
}

dependencies {
    implementation(project(":composeApp"))
}
