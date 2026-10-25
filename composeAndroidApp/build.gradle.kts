// APK de la app de acopio offline-first. Todo el código vive en :composeApp
// (librería KMP); este módulo solo empaqueta el ícono, el manifiesto y la config de red.
plugins {
    alias(libs.plugins.androidApplication)
}

android {
    namespace  = "com.example.prolechc.acopio"
    compileSdk = libs.versions.android.compileSdk.get().toInt()

    defaultConfig {
        // Distinto de :app:androidApp para poder instalar ambas en el mismo celular
        applicationId = "com.example.prolechc.acopio"
        minSdk        = libs.versions.android.minSdk.get().toInt()
        targetSdk     = libs.versions.android.targetSdk.get().toInt()
        versionCode   = 2
        versionName   = "2.0"
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
