> Documento histórico de la versión 1 (11/11/2026). Para la versión actual con integrantes, roles y salud, consultar [integrantes](integrantes.md), [migración v2](migracion-v2.md) y [pruebas v2](pruebas-v2.md). Las restricciones y funciones descritas aquí corresponden al alcance anterior.

# Matriz de requisitos y pruebas

Fuente: especificación suministrada, no un documento maestro externo. Resultados detallados y entorno en `resultados.md`; JSON saneado en `evidencias/`. “Aprobado local” significa PHP 8.2.12/MariaDB 10.4.32, **no aceptación del entorno PHP 8.4/MySQL 8**.

| ID | Módulo | Criterio de aceptación | Caso reproducible | Resultado verificable |
|---|---|---|---|---|
| RF01 | Institución | Solo contenidos publicados en Academia | CP01: crear borrador, comprobar oculto, publicar, volver al portal | Aprobado local; integration.json, CP01 y RF01 |
| RF02 | Categorías/horarios | Datos desde BD, activos y vigentes, día/hora ordenados | CP02: crear categoría y horario; desactivar cada uno; consultar | Aprobado local; CP02 y RF02; revisión de orden por consulta |
| RF03 | Publicaciones | Solo publicado con fecha no futura | CP03: borrador, publicar, fecha futura, despublicar | Aprobado local; CP03 y Publicación futura |
| RF04 | Contacto | Validación, aceptación, persistencia y confirmación sin cupo | CP04: envío ficticio, reenvío, consulta privada | Aprobado local; CP04, reenvío y CP07 |
| RF05 | Autenticación | Login válido/inválido, sesión nueva, acceso privado restringido | CP05: credenciales, cookies, visita privada y logout | Aprobado local; CP05, Cookie, Acceso privado y Cierre |
| RF06 | Contenidos | Alta, edición, publicación y desactivación persistentes | CP06: CRUD sin borrado de cada módulo; comprobar portal | Aprobado local; CP06, RF01–03, CSRF privado |
| RF07 | Solicitudes | Lista solo privada y cambios de estado | CP07: leer, cerrar, volver a consultar y enviar estado inválido | Aprobado local; CP07 y Estado inválido |
| RNF01 | Responsive | Sin scroll horizontal global a 360/768/1366 | RNF01-A: recorrer páginas públicas y tres vistas privadas | Aprobado local Edge; browser.json y capturas |
| RNF02 | Accesibilidad | Etiquetas, foco, teclado y contraste seleccionados | RNF02-A: etiquetas y salto por Tab; RNF02-B: recorrido completo y lector | Parcial: automatización y revisión visual; lector/recorrido exhaustivo pendiente |
| RNF03 | Seguridad | Validación backend, SQL parametrizado, CSRF, autorización | SEC01–06 abajo | Aprobado para escenarios implementados; auditoría independiente pendiente |
| RNF04 | Rendimiento | Mediana ≤3s para carga en entorno documentado | PERF01: 10 HTTP y 10 navegaciones por página | Aprobado local; no extrapolable a red móvil ni carga concurrente |
| RNF05 | Mantenibilidad | Capas, documentación, importación y ejecución reproducibles | MANT01: PHP lint, JS check, instalación en base nueva | Aprobado local; instalación exacta MySQL8/PHP8.4 pendiente |
| RNF06 | Privacidad/recuperación | Solicitudes restringidas y restauración consistente | REC01: dump, base nueva, seis conteos/checksums | Aprobado MariaDB; repetir MySQL8 y procedimiento institucional |

## Escenarios negativos

| Caso | Acción | Esperado | Ejecución |
|---|---|---|---|
| SEC01 | POST de contacto con vacíos, email no demo o sin aceptación | 422; errores comprensibles | integration.py |
| SEC02 | Publicar texto `<script>` y solicitud con HTML/SQL | Texto escapado; consulta no alterada | integration.py |
| SEC03 | Acceder o modificar módulos sin sesión | Redirección a login; no modificación | integration.py |
| SEC04 | POST con CSRF incorrecto en contacto y panel | 403; no operación | integration.py |
| SEC05 | Tabla arbitraria o ID inexistente | 404 | integration.py |
| SEC06 | Consultar config, app, SQL, Git desde HTTP | 403/404 | integration.py con raíz public |
| VAL01 | Horas invertidas, día 8, fecha imposible, texto largo | Error de validación | unit.php e integration.py |
| DUP01 | Repetir el mismo formulario de solicitud | Un registro; reenvío rechazado | integration.py; token UNIQUE en esquema |

## Casos manuales pendientes y procedimiento

1. **ACC01**: Tab/Shift+Tab desde inicio hasta pie; abrir menú con Enter, cerrarlo con Escape, enviar formulario inválido, seguir cada enlace del resumen, completar y revisar mensaje de éxito con lector de pantalla. Registrar foco, orden, nombre accesible y capturas. No basta el Tab inicial automatizado.
2. **ACC02**: comprobar contraste de todos los estados (hover, foco, error, disabled), zoom 200% y reflow equivalente a 320 CSS px. Se eligieron colores de alto contraste, pero no se certifica el conjunto WCAG.
3. **ENV01**: repetir instalación, CP01–07, SEC01–06 y REC01 en PHP 8.4 actualizado/MySQL 8; registrar versiones exactas. Probar Apache con VirtualHost documentado.
4. **TRL01**: ejecutar protocolo con participantes/roles reales solo cuando hayan aceptado y registrar evidencia auténtica del entorno simulado relevante.

Meta de validación: ≥90% de casos críticos satisfactorios, sin bloqueantes abiertos. Definir y congelar antes del ensayo el conjunto crítico (CP01–07, SEC01–06, DUP01, ENV01, REC01). Las comprobaciones automatizadas son subverificaciones; su 100% local **no constituye** el porcentaje final de aceptación ni TRL5.
