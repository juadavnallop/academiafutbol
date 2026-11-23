# Desarrollo CDIO y organización individual

Registro de construcción de esta entrega, 11 de noviembre de 2026. No representa sprints históricos, reuniones realizadas, participantes adicionales ni commits previos. La aplicación se desarrolló en una sesión de trabajo asistida, organizada lógicamente en los cuatro incrementos solicitados.

| Incremento | CDIO | Trabajo realizado | Evidencia |
|---|---|---|---|
| 1 | Concebir / Diseñar / Implementar | Inspección de carpeta vacía, análisis de RF/RNF, capas, esquema, identidad ficticia, portal y horarios | Archivos app, public y SQL; rutas públicas HTTP |
| 2 | Implementar | Autenticación, alta CLI, panel, contenidos, categorías y horarios | CP05/CP06, protección de rutas |
| 3 | Implementar | Publicaciones, solicitudes ficticias, idempotencia y gestión privada | CP03/CP04/CP07 |
| 4 | Operar en entorno de desarrollo | Integración, corrección de sesiones, navegador, respaldo/restauración y documentación | JSON, capturas y registro de incidencias |

La adaptación de Scrum consiste en backlog trazable a RF/RNF, incrementos revisables y definición explícita de terminado. No se simularon ceremonias ni equipos. Cada módulo se considera implementado cuando su ruta, formulario y persistencia existen; probado cuando hay una ejecución registrada; aceptado únicamente tras revisión en el entorno objetivo y cierre de incidencias.

Backlog pendiente: ENV01 (PHP8.4/MySQL8 y Apache), ACC01/ACC02 (accesibilidad humana), TRL01 (validación relevante). Los cambios posteriores deben registrar motivo, archivos afectados, prueba ejecutada y resultado, sin sobrescribir evidencia histórica con afirmaciones no verificadas.
