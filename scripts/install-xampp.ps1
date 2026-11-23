param([string]$Target = 'C:\xampp\htdocs\cancha-abierta')
$ErrorActionPreference = 'Stop'
$source = Split-Path $PSScriptRoot -Parent
$destination = [IO.Path]::GetFullPath($Target)
$expected = [IO.Path]::GetFullPath('C:\xampp\htdocs\cancha-abierta')
if ($destination -ne $expected) { throw 'El instalador solo permite C:\xampp\htdocs\cancha-abierta.' }
if (Test-Path -LiteralPath $destination) { throw 'La carpeta destino ya existe; revisarla antes de instalar para no sobrescribir datos.' }
New-Item -ItemType Directory -Path $destination | Out-Null
foreach ($folder in @('app','public','scripts','database','docs')) {
    Copy-Item -LiteralPath (Join-Path $source $folder) -Destination $destination -Recurse
}
New-Item -ItemType Directory -Path (Join-Path $destination 'config'),(Join-Path $destination 'var') | Out-Null
Copy-Item -LiteralPath (Join-Path $source 'config\config.example.php') -Destination (Join-Path $destination 'config\config.example.php')
Copy-Item -LiteralPath (Join-Path $source 'README.md') -Destination (Join-Path $destination 'README.md')
$utf8 = New-Object System.Text.UTF8Encoding($false)
$entry = '<?php header("Location: /cancha-abierta/public/", true, 302); exit;'
[IO.File]::WriteAllText((Join-Path $destination 'index.php'),$entry,$utf8)
$rules = @'
Options -Indexes
Require all granted
DirectoryIndex index.php
RewriteEngine On
RewriteRule ^(?!public(?:/|$)|index\.php$).+ - [F,L]
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
'@
[IO.File]::WriteAllText((Join-Path $destination '.htaccess'),$rules,$utf8)
foreach ($folder in @('app','config','database','docs','scripts','var')) {
    [IO.File]::WriteAllText((Join-Path $destination "$folder\.htaccess"),'Require all denied',$utf8)
}
Write-Host 'Archivos preparados en C:\xampp\htdocs\cancha-abierta. Falta configurar la base con provision-xampp.php o seguir docs/instalacion-xampp.md.'
