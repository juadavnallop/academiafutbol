param([string]$Xampp = 'C:\xampp')
$projectRoot = Split-Path $PSScriptRoot -Parent
$dataPath = Join-Path $projectRoot 'var\mysql'
if (!(Test-Path -LiteralPath (Join-Path $dataPath 'ibdata1'))) {
    throw 'Esta ayuda solo inicia la instancia local preparada durante el desarrollo. En otro equipo sigue README.md para instalar MySQL 8.'
}
$databaseProcess = $null
$mysqlAdmin = Join-Path $Xampp 'mysql\bin\mysqladmin.exe'
& $mysqlAdmin --no-defaults --host=127.0.0.1 --port=3307 --user=root ping 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
    $databaseProcess = Start-Process -FilePath (Join-Path $Xampp 'mysql\bin\mysqld.exe') -ArgumentList @('--no-defaults', "--basedir=`"$Xampp\mysql`"", "--datadir=`"$dataPath`"", '--port=3307', '--bind-address=127.0.0.1', '--console') -WindowStyle Hidden -PassThru -RedirectStandardError (Join-Path $projectRoot 'var\local-db-error.log') -RedirectStandardOutput (Join-Path $projectRoot 'var\local-db-output.log')
    Start-Sleep -Seconds 3
    if ($databaseProcess.HasExited) { throw 'No se pudo iniciar la instancia local; revisa var/local-db-error.log.' }
}
Write-Host 'Academia local: http://127.0.0.1:8080. Usa Ctrl+C para detener.'
Write-Host 'Este entorno usa las versiones de XAMPP instaladas. La aceptación requiere PHP 8.4 actualizado y MySQL 8.'
try {
    & (Join-Path $Xampp 'php\php.exe') -S 127.0.0.1:8080 -t (Join-Path $projectRoot 'public')
} finally {
    if ($null -ne $databaseProcess -and !$databaseProcess.HasExited) {
        & $mysqlAdmin --no-defaults --host=127.0.0.1 --port=3307 --user=root shutdown
    }
}
