> Documento histórico de la versión 1 (11/11/2026). Para la versión actual con integrantes, roles y salud, consultar [integrantes](integrantes.md), [migración v2](migracion-v2.md) y [pruebas v2](pruebas-v2.md). Las restricciones y funciones descritas aquí corresponden al alcance anterior.

# Resultados realmente ejecutados

Fecha de construcción: 11 de noviembre de 2026, contexto America/Bogota. Las marcas exactas de cada herramienta se conservan en los JSON; no se fabricaron actas, usuarios institucionales ni validaciones externas.

## Entorno observado

- Windows, ejecutables XAMPP en `C:/xampp`.
- PHP CLI 8.2.12, PDO MySQL y mbstring disponibles.
- MariaDB 10.4.32, instancia nueva aislada en `var/mysql`, enlace `127.0.0.1:3307`.
- PHP development server `127.0.0.1:8080`, DocumentRoot `public`.
- Node 24.14.0, Python 3.13, Edge headless 157.0.4341.42 según CDP.
- Datos ficticios, un cliente secuencial, red loopback, sin carga concurrente. No se obtuvieron CPU/RAM por restricciones de consulta; no se extrapolan las métricas a otros equipos.

## Resultados

| Suite | Ejecución | Resultado | Evidencia |
|---|---|---|---|
| Sintaxis PHP | `php -l` sobre código | Sin errores en archivos revisados | `evidencias/php-lint.txt`; repetir comando README |
| Sintaxis JS | `node --check public/assets/js/app.js` | Sin errores | Comando reproducible |
| Validación unitaria | `php tests/unit.php` | 13/13 | `evidencias/unit.txt` |
| Integración HTTP/BD | `python tests/integration.py --php C:\xampp\php\php.exe` | 52/52 | `evidencias/integration.json` |
| Navegador | `node tests/browser.mjs` | 36/36 | `evidencias/browser.json` |
| Respaldo/restauración | `python tests/recovery.py ... --source academia_test --port 3307` | 6/6 tablas con conteo y checksum iguales | `evidencias/recovery.json` |

Las cifras corresponden a comprobaciones de suites, no a 107 requisitos diferentes ni a un porcentaje de madurez tecnológica. La suite HTTP comprueba los siete CP principales, pero MySQL8/PHP8.4 y los pendientes humanos deben evaluarse antes de aceptar el prototipo en su entorno objetivo.

Capturas de Inicio en `evidencias/inicio-360.png`, `inicio-768.png`, `inicio-1366.png`. Las capturas de la demostración limpia se generan con `node tests/browser.mjs --public-only`; esa opción omite la cuenta y las vistas privadas. La evidencia completa del panel está en la ejecución predeterminada y su JSON.

## Medición de rendimiento

Diez muestras secuenciales por página. HTTP mide respuesta descargada con urllib; navegador mide `PerformanceNavigationTiming.loadEventEnd`, incluyendo los recursos requeridos para load. Recursos locales, caché normal sin emulación de red, navegador ya iniciado, 1366×900 al medir la suite completa. No representa primera visita en móvil, Lighthouse, LCP ni una prueba de estrés.

| Página | Mediana HTTP (s) | Mediana navegador (s) |
|---|---:|---:|
| Inicio | 0.006877 | 0.018950 |
| Academia | 0.006696 | 0.018550 |
| Horarios | 0.007468 | 0.018500 |
| Actualidad | 0.007450 | 0.018150 |
| Contacto | 0.004108 | 0.016200 |

La meta ≤3s se cumplió en este ensayo local. Las muestras de navegador y versión exacta están en `browser.json`; la métrica HTTP conserva mediana y máximo en `integration.json`. Repetir en el entorno relevante.

## Recuperación

Se ejecutó dump transaccional, creación de base destino nueva, importación y comprobación de las seis tablas. El nombre del destino y conteos se guardan en `recovery.json`. El SQL de respaldo permanece fuera de `public` y está ignorado por Git: contiene hashes de cuentas de prueba y no se distribuye como evidencia pública.

Para respaldar MySQL8 manualmente, usando credenciales solicitadas interactivamente y una carpeta privada existente:

```powershell
$dumpExe = 'C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe'
& $dumpExe -h 127.0.0.1 -P 3306 -u root -p --single-transaction --no-tablespaces --result-file='C:\respaldos-privados\academia-demo.sql' academia_demo
```

Para restaurar, abrir el cliente MySQL con `-u root -p`, crear **otra base vacía**, seleccionar esa base con `USE`, y ejecutar `SOURCE C:/respaldos-privados/academia-demo.sql;`. Comparar filas y `CHECKSUM TABLE` para cada tabla, y después repetir CP01–CP07 apuntando una instalación aislada a esa restauración. No restaurar sobre la base en uso ni ubicar el SQL en htdocs. Proteger el respaldo con ACL y eliminarlo según el cierre del ensayo.

## Pendientes explícitos

- Repetición de pruebas en PHP 8.4 actualizado y MySQL8; Apache de XAMPP y HTTPS no ejecutados.
- Accesibilidad exhaustiva con teclado/lector, contraste de todos los estados y zoom; solo revisión parcial y automatización seleccionada realizadas.
- Concurrencia, bloqueo distribuido de abuso, recuperación ante caída durante escritura y evaluación independiente de seguridad.
- Ensayo con entorno relevante justificado y evaluación académica TRL5.

No se ha alcanzado ni acreditado TRL5 con estas pruebas.
