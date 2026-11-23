# Migración y recuperación de la versión 2

## Instalaciones existentes

La actualización conserva IDs, hashes, cuentas, solicitudes y relaciones anteriores. Se amplía la tabla histórica de cuentas con rol, versión de sesión y cambio obligatorio de contraseña. Los administradores anteriores conservan su rol por defecto. Las sesiones anteriores a la migración deben iniciar sesión otra vez.

1. Detener escrituras durante la migración y respaldar la base con `mysqldump --single-transaction --no-tablespaces --result-file=...` en un directorio privado.
2. Actualizar código, manteniendo `config/local.php` y `var/keys/health.key` si ya existe.
3. Ejecutar con una cuenta con permisos CREATE/ALTER/INDEX/REFERENCES, además de DML:

```powershell
& C:\php84\php.exe scripts\migrate.php
```

Si la cuenta de aplicación no tiene DDL, la herramienta acepta `--admin=usuario_migracion` y lee su contraseña por stdin, no en los argumentos. En esta instancia local aislada, root no tiene contraseña:

```powershell
'' | & C:\xampp\php\php.exe scripts\migrate.php --admin=root
```

Este comando vacío es únicamente para la instancia de desarrollo ya preparada; no aplicarlo a una instalación real. En un servidor con contraseña, utilizar una captura segura por stdin o conceder temporalmente los permisos a la cuenta de aplicación y retirarlos al finalizar.

4. Confirmar tablas y versión en `migraciones`, iniciar sesión y completar configuración.
5. Ejecutar pruebas en una copia aislada, nunca en la base institucional.

La migración puede repetirse y comprueba columnas antes de añadirlas. MySQL hace commit implícito de DDL: una copia de respaldo sigue siendo necesaria ante fallo parcial. La migración 003 modifica solo contenidos reconocibles del seed original; los deja inactivos/borradores y normaliza los textos de ejemplo. No se borran registros ni se renumeran IDs.

En este equipo se conservó `var/before-members-2026-11-17.sql` antes de modificar la estructura. Contiene datos privados de la instalación y está excluido del ZIP y de Git.

## Instalaciones nuevas

`database/schema.sql` contiene las tablas de ambas versiones. `scripts/import.php schema` crea también los registros de migración y la clave privada. `scripts/import.php initial` agrega únicamente un bloque institucional sin personas, horarios ni noticias inventadas. El antiguo `database/demo.sql` es una fixture histórica; no debe importarse en una instalación operativa.

## Recuperar salud cifrada

Respaldar **la base y la clave original**, con acceso restringido y custodias separadas. Un SQL sin la clave no permite recuperar las fichas de salud. No generar otra clave al restaurar: el migrador rechaza hacerlo si ya hay fichas cifradas.

Restaurar en una base nueva, instalar el código correspondiente, copiar la clave original a `var/keys/health.key` con ACL privadas, ajustar la configuración y comprobar el descifrado de una ficha de prueba. Aplicar las revocaciones posteriores al respaldo antes de reabrir el servicio. Nunca colocar SQL, claves o configuración en `public/` ni en GitHub.

La suite `tests/recovery.py` descubre todas las tablas y compara conteos/checksums tras restaurar. Los scripts de pruebas dejan bases desechables con sufijo `_test`; su retirada debe hacerse verificando primero los nombres. No contienen integrantes reales.
