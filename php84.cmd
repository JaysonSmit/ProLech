@echo off
REM Usa PHP 8.4 de Laragon (requerido por Laravel 13 / composer.lock)
set "PHP=C:\laragon\bin\php\php-8.4.25-nts-Win32-vs17-x64\php.exe"
if not exist "%PHP%" (
    echo No se encontro PHP 8.4 en Laragon.
    echo Instala php-8.4.x en C:\laragon\bin\php\
    exit /b 1
)
"%PHP%" %*
