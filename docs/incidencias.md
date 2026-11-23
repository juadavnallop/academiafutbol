> Documento histórico de la versión 1 (11/11/2026). Para la versión actual con integrantes, roles y salud, consultar [integrantes](integrantes.md), [migración v2](migracion-v2.md) y [pruebas v2](pruebas-v2.md). Las restricciones y funciones descritas aquí corresponden al alcance anterior.

# Incidencias verificadas

| ID | Hallazgo real | Acción | Estado |
|---|---|---|---|
| INC01 | No había archivos previos en el espacio de trabajo | Proyecto creado sin sobrescribir trabajo anterior | Cerrada |
| INC02 | XAMPP disponible: PHP 8.2.12 y MariaDB 10.4.32; no MySQL8 | Se aisló una instancia local en var/mysql; destino y limitación documentados | Abierta para aceptación de entorno |
| INC03 | Primer intento de integration.py falló por escape `\x` en docstring de ruta Windows | Convertir docstring a cadena raw | Corregida; suite posterior pasó |
| INC04 | PHP intentaba guardar sesiones en C:/xampp/tmp sin permisos; los POST recibían 403 | Sesiones privadas en var/sessions y fallo explícito si no se inicia sesión | Corregida; 52 verificaciones HTTP pasaron |
| INC05 | Edge no mantuvo CDP dentro del entorno restringido | Ejecución autorizada fuera del sandbox para pruebas locales de navegador | Resuelta; 36 verificaciones pasaron |
| INC06 | La inspección de contraseña usaba trim y alteraba espacios extremos | Lectura exacta de contraseña en login; codificación UTF-8 en PowerShell de alta | Corregida; login probado en navegador |
| INC07 | Consultas CIM de hardware/sistema no autorizadas en el entorno | No inventar CPU/RAM; limitar caracterización a Windows y versiones verificadas | Limitación de medición documentada |

No se observaron fallos bloqueantes en las suites locales completadas. INC02 impide declarar cumplida la validación del entorno objetivo. Pruebas no ejecutadas (Apache, accesibilidad exhaustiva, TRL5) son pendientes, no aprobaciones implícitas.
