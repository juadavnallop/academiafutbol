param(
    [string]$Php = 'C:\xampp\php\php.exe',
    [Parameter(Mandatory=$true)][string]$Email,
    [Parameter(Mandatory=$true)][string]$Name
)
$secret = Read-Host 'Contraseña nueva (mínimo 12 caracteres)' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
$previousEncoding = $OutputEncoding
try {
    $OutputEncoding = New-Object System.Text.UTF8Encoding($false)
    $passwordText = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    $passwordText | & $Php "$PSScriptRoot\create-admin.php" $Email $Name
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el administrador.' }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    $passwordText = $null
    $secret.Dispose()
    $OutputEncoding = $previousEncoding
}
