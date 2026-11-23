# Verificación de portal y fichas técnicas

Fecha: 18 de noviembre de 2026. PHP 8.2.12 / MariaDB 10.4.32 de XAMPP; Edge Chromium. Pruebas aisladas en puerto 3307; la aplicación instalada usa 3306.

- 79 comprobaciones HTTP de integrantes, permisos, inscripción, cifrado, autorización/revocación de salud, filtros de horarios, noticias y resumen técnico.
- 52 comprobaciones de regresión: administración de contenido, estados, contacto, seguridad y tiempos de respuesta.
- 90 comprobaciones en navegador: 360, 768 y 1366 px; navegación, formularios, fichas por rol, menú móvil y carga.
- 13 pruebas unitarias de validación y escape.
- Importación completa del SQL en una base temporal: 14 tablas y sin cuentas predeterminadas.

Capturas y resultados saneados en `docs/evidencias/v3`. Los nombres QA son datos de pruebas aislados; no se instalan en la academia. La salud no se incluye en capturas. La evidencia de v2 conserva las pruebas de restauración de base y clave.

El resumen usa mediciones reales registradas: valores pendientes no son cero; fecha y autor identifican evaluaciones; la evolución muestra hasta 12 registros consecutivos y permite consultar su tabla. Las próximas sesiones solo incluyen horarios vigentes de la categoría activa del jugador.

La validación final de Apache y de la transferencia de datos se guarda en `docs/evidencias/v3/xampp.json`.
