# Reglas R8 para el APK release de ProLech Acopio.

# Mensajes de error legibles en reportes de fallos
-keepattributes SourceFile,LineNumberTable,*Annotation*,Signature,InnerClasses,EnclosingMethod
-renamesourcefileattribute SourceFile

# kotlinx.serialization: los DTO de la API se (de)serializan por los serializer() generados
-keepclassmembers @kotlinx.serialization.Serializable class ** {
    *** Companion;
    static ** INSTANCE;
    kotlinx.serialization.KSerializer serializer(...);
}
-keepclasseswithmembers class **$$serializer { *; }
-keep,includedescriptorclasses class com.example.prolechc.**$$serializer { *; }
-keepclassmembers class com.example.prolechc.** {
    *** Companion;
}
-dontnote kotlinx.serialization.**

# Ktor (motor y plugins se cargan por ServiceLoader)
-keep class io.ktor.** { *; }
-keepclassmembers class io.ktor.** { volatile <fields>; }
-dontwarn io.ktor.**
-dontwarn org.slf4j.**
-dontwarn java.lang.management.**

# Coroutines
-keepnames class kotlinx.coroutines.internal.MainDispatcherFactory {}
-keepnames class kotlinx.coroutines.CoroutineExceptionHandler {}
-dontwarn kotlinx.coroutines.**

# SQLDelight / SQLite
-keep class app.cash.sqldelight.** { *; }
-keep class androidx.sqlite.** { *; }

# Clases de la app referenciadas desde el manifiesto o por reflexión (Koin, Application, servicios)
-keep class com.example.prolechc.** { *; }
-keep class pe.edu.upeu.prolech.** { *; }

# osmdroid (mapas)
-keep class org.osmdroid.** { *; }
-dontwarn org.osmdroid.**

# ML Kit (OCR del Lactoscan) y Google Play Services (GPS)
-keep class com.google.mlkit.** { *; }
-keep class com.google.android.gms.internal.mlkit_vision_text_common.** { *; }
-dontwarn com.google.mlkit.**
-dontwarn com.google.android.gms.**
