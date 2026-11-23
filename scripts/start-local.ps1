param([string]$Xampp = 'C:\xampp')
# Alias estable para instalaciones existentes; conserva el iniciador anterior.
& "$PSScriptRoot\start-local-demo.ps1" -Xampp $Xampp
