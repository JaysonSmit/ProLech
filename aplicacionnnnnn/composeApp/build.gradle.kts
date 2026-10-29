import org.jetbrains.kotlin.gradle.dsl.JvmTarget

// Librería KMP con toda la app offline-first (UI Compose, SQLDelight, Ktor, Koin, GPS).
// AGP 9 no permite 'kotlin.multiplatform' + 'com.android.application' en el mismo
// módulo: el APK lo genera el módulo :composeAndroidApp, que depende de este.
plugins {
    alias(libs.plugins.kotlinMultiplatform)
    alias(libs.plugins.androidMultiplatformLibrary)
    alias(libs.plugins.composeMultiplatform)
    alias(libs.plugins.composeCompiler)
    alias(libs.plugins.kotlinSerialization)
    alias(libs.plugins.sqldelight)
}

kotlin {
    android {
        namespace  = "com.example.prolechc.offline"
        compileSdk = libs.versions.android.compileSdk.get().toInt()
        minSdk     = libs.versions.android.minSdk.get().toInt()

        compilerOptions { jvmTarget = JvmTarget.JVM_11 }

        // Empaqueta los composeResources (logo de la pantalla de carga) en el APK
        androidResources { enable = true }

        // Tests de commonTest en la JVM (src/androidHostTest)
        withHostTest { }
    }

    listOf(
        iosArm64(),
        iosSimulatorArm64()
    ).forEach { iosTarget ->
        iosTarget.binaries.framework {
            baseName = "ComposeApp"
            isStatic = true
        }
    }

    sourceSets {

        // ── commonMain ────────────────────────────────────────────────────────
        commonMain.dependencies {
            // Compose Multiplatform
            implementation(libs.kmp.compose.runtime)
            implementation(libs.kmp.compose.foundation)
            implementation(libs.kmp.compose.material3)
            implementation(libs.kmp.compose.ui)
            implementation(libs.kmp.compose.resources)
            implementation(libs.kmp.compose.uiToolingPreview)

            // Lifecycle + Navigation KMP
            implementation(libs.kmp.lifecycle.viewmodel.compose)
            implementation(libs.kmp.lifecycle.runtime.compose)
            implementation(libs.kmp.navigation.compose)

            // Ktor Client KMP
            implementation(libs.ktor.client.core)
            implementation(libs.ktor.client.content.negotiation)
            implementation(libs.ktor.serialization.json)
            implementation(libs.ktor.client.logging)
            implementation(libs.ktor.client.auth)

            // Koin KMP
            implementation(libs.koin.core)
            implementation(libs.koin.compose)
            implementation(libs.koin.compose.viewmodel)

            // Coroutines
            implementation("org.jetbrains.kotlinx:kotlinx-coroutines-core:${libs.versions.kotlinx.coroutines.get()}")

            // kotlinx-datetime
            implementation(libs.kotlinx.datetime)

            // SQLDelight: Flow reactivo (asFlow().mapToList())
            implementation(libs.sqldelight.coroutines.extensions)
        }

        // ── androidMain ───────────────────────────────────────────────────────
        androidMain.dependencies {
            // Ktor Android Engine
            implementation(libs.ktor.client.android)

            // Koin Android (androidContext, inject en el Service)
            api(libs.koin.android)

            // DataStore Preferences (TokenStorage)
            implementation(libs.datastore.preferences)

            // Compose tooling + Activity
            implementation(libs.kmp.compose.uiTooling)
            api(libs.androidx.activity.compose)

            // Coroutines Android
            implementation(libs.kotlinx.coroutinesAndroid)

            // SQLDelight Android driver
            implementation(libs.sqldelight.android.driver)

            // GPS (FusedLocationProvider) + mapa OpenStreetMap sin API key
            implementation(libs.play.services.location)
            implementation(libs.osmdroid.android)

            // OCR del Lactoscan en el celular (sin internet)
            implementation("com.google.mlkit:text-recognition:16.0.1")
        }

        // ── iosMain ───────────────────────────────────────────────────────────
        iosMain.dependencies {
            // Ktor Darwin Engine
            implementation(libs.ktor.client.darwin)

            // SQLDelight Native driver
            implementation(libs.sqldelight.native.driver)
        }

        commonTest.dependencies {
            implementation(libs.kotlin.test)
            implementation(libs.ktor.client.mock)
            implementation(libs.kotlinx.coroutines.test)
        }

        // Driver JDBC en memoria para correr los tests de commonTest en la JVM
        getByName("androidHostTest").dependencies {
            implementation(libs.sqldelight.sqlite.driver)
            implementation(libs.kotlin.testJunit)
        }
    }
}

sqldelight {
    databases {
        create("AppDatabase") {
            packageName.set("pe.edu.upeu.prolech.db")
        }
    }
}

// Recursos de Compose (logo de la pantalla de carga): clase Res con un paquete fijo
compose.resources {
    packageOfResClass = "com.example.prolechc.recursos"
    generateResClass = always
}
