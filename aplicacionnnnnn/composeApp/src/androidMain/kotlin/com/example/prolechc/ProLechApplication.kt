package com.example.prolechc

import android.app.Application
import com.example.prolechc.di.initKoin
import org.koin.android.ext.koin.androidContext
import org.koin.android.ext.koin.androidLogger

class ProLechApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        initKoin {
            androidLogger()
            androidContext(this@ProLechApplication)
        }
    }
}
