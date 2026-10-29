package com.example.prolechc.data.local.db

import app.cash.sqldelight.db.SqlDriver

/** Crea el driver SQLite propio de cada plataforma. */
expect class DriverFactory {
    fun createDriver(): SqlDriver
}

const val NOMBRE_BD = "prolech.db"
