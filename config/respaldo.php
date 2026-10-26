<?php

/*
 * Respaldos automáticos de la base de datos (comando prolech:respaldo).
 * Se programan todos los días a las 02:00 en routes/console.php.
 */
return [

    // Carpeta donde quedan los .sql.gz (fuera de public/, no se publica en la web)
    'ruta' => env('RESPALDO_RUTA', storage_path('app/respaldos')),

    // Cuántos respaldos guardar; los más antiguos se borran solos
    'conservar' => (int) env('RESPALDO_CONSERVAR', 14),

    // Ejecutable de mysqldump. En Laragon:
    // RESPALDO_MYSQLDUMP="C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe"
    'mysqldump' => env('RESPALDO_MYSQLDUMP', 'mysqldump'),

];
