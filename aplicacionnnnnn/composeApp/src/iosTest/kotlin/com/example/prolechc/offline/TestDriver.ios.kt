package com.example.prolechc.offline

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.native.inMemoryDriver
import pe.edu.upeu.prolech.db.AppDatabase

actual fun crearDriverEnMemoria(): SqlDriver = inMemoryDriver(AppDatabase.Schema)
