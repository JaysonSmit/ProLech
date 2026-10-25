rootProject.name = "Prolechc"

pluginManagement {
    repositories {
        google {
            mavenContent {
                includeGroupAndSubgroups("androidx")
                includeGroupAndSubgroups("com.android")
                includeGroupAndSubgroups("com.google")
            }
        }
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositories {
        google {
            mavenContent {
                includeGroupAndSubgroups("androidx")
                includeGroupAndSubgroups("com.android")
                includeGroupAndSubgroups("com.google")
            }
        }
        mavenCentral()
    }
}

plugins {
    id("org.gradle.toolchains.foojay-resolver-convention") version "1.0.0"
}

include(":app:androidApp")
include(":app:desktopApp")
include(":app:shared")
include(":app:webApp")
include(":core")
// App de acopio offline-first (Ktor + Koin + SQLDelight + GPS):
//   :composeApp        → librería KMP ('com.android.kotlin.multiplatform.library', AGP 9)
//   :composeAndroidApp → genera el APK "ProLech Acopio"
include(":composeApp")
include(":composeAndroidApp")
// El módulo :server es un backend Ktor independiente.
// Está excluido del build Android para no requerir sus
// dependencias (Exposed, PostgreSQL, HikariCP, Ktor server).
// Para ejecutarlo: abre el módulo server como proyecto JVM separado.
// include(":server")