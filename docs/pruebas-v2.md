# Verificación de integrantes · 17 de noviembre de 2026

Entorno: PHP 8.2.12, MariaDB 10.4.32, Windows, Edge headless y servidor PHP local. Base de pruebas creada aparte, con sufijo `_test`, puerto de BD 3307 y servidor web 8081. La base de la academia no se utiliza para altas, contraseñas o datos de prueba. No se alteró `config/local.php` para ejecutar las pruebas.

## Resultados

| Suite | Resultado | Evidencia |
|---|---|---|
| Integrantes HTTP + BD | 66 verificaciones satisfactorias | `evidencias/v2/members.json` |
| Regresión del portal y administración | 52 verificaciones satisfactorias | `evidencias/v2/integration.json` |
| Navegador responsive y flujos | 83 verificaciones satisfactorias | `evidencias/v2/browser-members.json` |
| Validadores anteriores actualizados | 13 verificaciones satisfactorias | Comando `php tests/unit.php` |
| Respaldo y restauración | 14 tablas con conteo y checksum coincidentes | `evidencias/v2/recovery.json` |
| Salud restaurada | Descifrado coincidente con la clave original | `evidencias/v2/recovery-health.json` |

Las capturas `ficha-360.png` y `ficha-1366.png` corresponden a datos de QA inventados. No representan participantes de una academia ni resultados deportivos reales.

## Cobertura

Registro de adultos y menores con representante; autorización requerida; correo duplicado; intento de elevar rol con POST; acceso a otra ficha; límites por rol; edición propia; XSS escapado; categoría inexistente; rangos físicos; evaluaciones repetidas; inscripción activa con referencia de verificación; autorización separada de salud; cifrado y asociación de ciphertext al jugador; revocación y eliminación activa; auditoría; alta de asistente; contraseña temporal; impedir saltarse el cambio; autorretiro de administrador; desactivación que invalida sesión; logout. Cada comprobación tiene su resultado en el JSON.

Navegador: páginas públicas, registro, listado, cuentas, configuración, auditoría y cinco secciones de ficha a 360, 768 y 1366 px; login de administrador y jugador; guardado con JavaScript activo; carga local. No se certifica WCAG completa ni rendimiento bajo concurrencia.

## Repetir localmente

Con la instancia local de MariaDB del proyecto iniciada:

```powershell
python tests\members.py
```

Este script crea una base única, importa esquema/migraciones dos veces para comprobar repetibilidad, crea credenciales aleatorias, inicia PHP en 8081, prueba los flujos y detiene ese servidor al terminar. Requiere que 8081 esté libre. Utiliza root sin contraseña únicamente en la instancia desechable local del proyecto; adaptar `tests/setup-members.php` para otro laboratorio con credenciales seguras. Nunca apuntarlo a una base real.

Para repetir navegador y regresión contra la base creada por esa prueba, iniciar en una terminal:

```powershell
$env:CANCHA_CONFIG = Join-Path $PWD 'var\members-test-config.php'
& C:\xampp\php\php.exe -S 127.0.0.1:8081 -t public
```

En otra terminal:

```powershell
$env:CANCHA_CONFIG = Join-Path $PWD 'var\members-test-config.php'
$env:TEST_URL = 'http://127.0.0.1:8081'
python tests\integration.py --php C:\xampp\php\php.exe --url http://127.0.0.1:8081
node tests\browser.mjs --members
```

`var/members-fixture.json` contiene credenciales aleatorias de ese laboratorio para el navegador; no se imprime, no se publica y no pertenece a la base de la academia. Los resultados JSON no incluyen contraseñas ni fichas clínicas. `CANCHA_CONFIG` es una variable del proceso del servidor, no un parámetro HTTP.

## Incidencias resueltas y límites

Durante la edición, el chequeo de sintaxis detectó comillas incompatibles en el INSERT de contacto. Se corrigieron antes de la regresión exitosa. El navegador requirió ejecución autorizada fuera del sandbox, como en la versión anterior. Tras la primera pasada de 64 comprobaciones se añadió la revisión obligatoria antes de activar una inscripción y se verificó una nueva autorización posterior a la revocación; la pasada final tiene 66. El esquema completo actualizado también se importó desde cero en esa pasada.

Pendiente: repetir en PHP 8.4 actualizado/MySQL8, revisión legal y operativa de la academia, prueba humana de accesibilidad y evaluación de seguridad/concurrencia. No hay validación institucional ni acreditación TRL5. La evidencia de la versión 1 permanece histórica y no se presenta como validación de las funciones nuevas.
