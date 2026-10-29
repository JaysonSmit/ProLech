# Registra en el Programador de tareas de Windows el scheduler de Laravel (cada minuto),
# para que el respaldo diario y las demás tareas de routes/console.php corran solas en esta PC.
#   Instalar:  powershell -ExecutionPolicy Bypass -File programar-tareas.ps1
#   Quitar:    powershell -ExecutionPolicy Bypass -File programar-tareas.ps1 -Quitar
# La PC debe estar encendida y MySQL (Laragon) corriendo a la hora de cada tarea.
param([switch]$Quitar)

$nombre = 'ProLech - tareas programadas'

if ($Quitar) {
    Unregister-ScheduledTask -TaskName $nombre -Confirm:$false
    Write-Host "Tarea '$nombre' eliminada."
    return
}

$php = (Get-Command php -ErrorAction Stop).Source
$proyecto = $PSScriptRoot

$accion = New-ScheduledTaskAction -Execute $php -Argument 'artisan schedule:run' -WorkingDirectory $proyecto
$disparador = New-ScheduledTaskTrigger -Once -At (Get-Date).Date `
    -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 3650)
$opciones = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
    -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Hours 2)

Register-ScheduledTask -TaskName $nombre -Action $accion -Trigger $disparador -Settings $opciones `
    -Description 'Respaldo diario de la base de ProLech, limpieza y avisos de stock (php artisan schedule:run)' -Force | Out-Null

Write-Host "Tarea '$nombre' registrada: corre 'php artisan schedule:run' cada minuto en $proyecto"
Write-Host "Ver las tareas de Laravel: php artisan schedule:list"
