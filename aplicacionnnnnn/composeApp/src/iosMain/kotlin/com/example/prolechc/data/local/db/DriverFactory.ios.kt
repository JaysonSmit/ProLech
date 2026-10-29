package com.example.prolechc.data.local.db

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.native.NativeSqliteDriver
import pe.edu.upeu.prolech.db.AppDatabase

actual class DriverFactory {
    actual fun createDriver(): SqlDriver =
        NativeSqliteDriver(AppDatabase.Schema, NOMBRE_BD)
}
