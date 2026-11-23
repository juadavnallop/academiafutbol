# Base de datos y recursos

La aplicación conserva cuentas, inscripciones, fichas técnicas, evaluaciones, horarios, noticias y configuración en la base `cancha_abierta`. Se verificó con MariaDB de XAMPP, compatible con MySQL.

| Archivo | Uso |
|---|---|
| [instalar_xampp.sql](instalar_xampp.sql) | Instalación completa en una base nueva: 14 tablas, sin contraseñas ni cuentas predeterminadas |
| [schema.sql](schema.sql) | Estructura de tablas |
| [initial.sql](initial.sql) | Contenido institucional inicial |
| [migrations](migrations) | Actualizaciones de estructura y retiro de contenido inicial de versiones anteriores |
| [demo.sql](demo.sql) | Datos ficticios de la versión histórica; solo laboratorio, no importar en la academia |

Sigue las instrucciones de [importación en XAMPP](../docs/instalacion-xampp.md). No vuelvas a importar el esquema sobre una base que ya contiene información.

No se publican bases de jugadores, datos personales, información de salud, respaldos ni claves. No se utiliza un conjunto de datos externo. Las pruebas generan registros propios de ejemplo en un laboratorio aislado; sus scripts están en [tests](../tests).

Los recursos visuales y estilos del sitio se encuentran en [public/assets](../public/assets). La descripción del diseño de datos está en [arquitectura v2](../docs/arquitectura-v2.md).
