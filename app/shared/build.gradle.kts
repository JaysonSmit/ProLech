import org.jetbrains.kotlin.gradle.ExperimentalWasmDsl
import org.jetbrains.kotlin.gradle.dsl.JvmTarget

plugins {
    alias(libs.plugins.kotlinMultiplatform)
    alias(libs.plugins.androidMultiplatformLibrary)
    alias(libs.plugins.composeMultiplatform)
    alias(libs.plugins.composeCompiler)
}

kotlin {
    listOf(
        iosArm64(),
        iosSimulatorArm64()
    ).forEach { iosTarget ->
        iosTarget.binaries.framework {
            baseName = "Shared"
            isStatic = true
        }
    }

    jvm()

    js { browser() }

    @OptIn(ExperimentalWasmDsl::class)
    wasmJs { browser() }

    android {
        namespace   = "com.example.prolechc.app.shared"
        compileSdk  = libs.versions.android.compileSdk.get().toInt()
        minSdk      = libs.versions.android.minSdk.get().toInt()

        compilerOptions { jvmTarget = JvmTarget.JVM_11 }

        androidResources { enable = true }

        withHostTest {
            isIncludeAndroidResources = true
        }
        withDeviceTestBuilder {
            sourceSetTreeName = "test"
        }.configure {
            instrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
        }
    }

    sourceSets {
        androidMain.dependencies {
            implementation(libs.kmp.compose.uiTooling)
            implementation(libs.kmp.compose.uiToolingPreview)
        }
        commonMain.dependencies {
            api(project(":core"))
            implementation(libs.kmp.compose.runtime)
            implementation(libs.kmp.compose.foundation)
            implementation(libs.kmp.compose.material3)
            implementation(libs.kmp.compose.ui)
            implementation(libs.kmp.compose.resources)
            implementation(libs.kotlinx.datetime)
            implementation(libs.kmp.compose.uiToolingPreview)
            implementation(libs.kmp.lifecycle.viewmodel.compose)
            implementation(libs.kmp.lifecycle.runtime.compose)
            implementation(libs.kmp.navigation.compose)
        }
        commonTest.dependencies {
            implementation(libs.kotlin.test)
        }
        jsMain.dependencies {
            implementation(libs.wrappers.browser)
        }
    }
}

dependencies {
    androidRuntimeClasspath(libs.kmp.compose.uiTooling)
}
