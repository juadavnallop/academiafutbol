# Cancha Abierta · gestión de una academia de fútbol

Portal institucional y área privada para jugadores, asistentes y administradores. PHP, PDO, MySQL, HTML, CSS y JavaScript vanilla. Nombre, contacto y política configurables desde el panel.

## Abrir en este equipo

Enciende Apache y MySQL en XAMPP y visita **http://localhost/cancha-abierta/**.

Instalación: `C:\xampp\htdocs\cancha-abierta`. Base: `cancha_abierta`, puerto 3306. No necesita iniciar el laboratorio ni importar de nuevo la base.

SQL completo para otra instalación limpia: [database/instalar_xampp.sql](database/instalar_xampp.sql). [Guía XAMPP e importación](docs/instalacion-xampp.md).

No hay contraseña predeterminada. Crea tu primer administrador desde PowerShell, sustituyendo el correo:

```powershell
cd C:\xampp\htdocs\cancha-abierta
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\create-admin.ps1 -Email "tu-correo@dominio.com" -Name "Administrador"
```

La terminal solicita tu contraseña sin mostrarla. Luego inicia sesión y configura la academia.

## Portal y resumen técnico

Diseño adaptable a celular y escritorio; horarios por categoría y día; noticias con página completa; información institucional editable. Al entrar, el jugador ve perfil futbolístico, mediciones recientes, evolución, habilidades, observaciones y próximas sesiones. Puede imprimir el resumen o guardarlo como PDF. Las evaluaciones conservan fecha y autor.

## Configurar y usar

1. **Iniciar sesión → Academia:** nombre, ciudad, correo, teléfono, responsable y política de datos. Habilita las inscripciones cuando estos datos sean correctos. El contacto y la inscripción no recopilan datos hasta completar la configuración.
2. **Cuentas y roles:** crea asistentes/administradores; entrega la contraseña temporal por canal privado. Deberán cambiarla al entrar.
3. **Categorías y Horarios:** registra la oferta de la academia y activa únicamente información confirmada.
4. **Inscripción de jugadores:** alta del jugador y, para menores, datos/declaración del representante. También el administrador puede registrar jugadores con una referencia de autorización previa.
5. **Integrantes → Abrir ficha:** datos personales, ficha futbolística, historial de evaluaciones, inscripción y salud según permisos.

La información de salud es opcional, cifrada y requiere autorización separada. El asistente no la recibe. El jugador solo accede a su propia ficha. Consulta el [manual completo de integrantes](docs/integrantes.md).

| Función | Jugador / representante | Asistente | Administrador |
|---|---|---|---|
| Datos de contacto | Editar propios | Consultar | Editar |
| Ficha futbolística e historial físico/técnico | Consultar propios | Registrar/gestionar | Registrar/gestionar |
| Inscripción | Consultar estado | Revisar/activar con referencia | Igual y alta de jugadores |
| Salud | Autorizar, editar, consultar y revocar propia | Sin acceso | Consultar/editar con autorización activa |
| Cuentas, roles, configuración, contenido y auditoría | No | No | Sí |

## Instalación nueva

Sigue la [guía XAMPP](docs/instalacion-xampp.md). No requiere Composer ni npm para ejecutarse. Verificado en PHP 8.2.12 y MariaDB 10.4.32 de XAMPP. Esta entrega es local; publicar en Internet requiere HTTPS y un servidor con versiones mantenidas.

## Actualizaciones y respaldo

En una instalación anterior: respaldar primero y ejecutar `php scripts/migrate.php` con permisos de migración. No volver a importar el esquema sobre datos existentes. Ver [migración v2](docs/migracion-v2.md).

Respaldar la base **y la clave original `var/keys/health.key`**, con custodia separada. Sin esa clave no se recuperan las fichas de salud. La clave, los respaldos, las sesiones y `config/local.php` están excluidos de Git y del ZIP. No borrar `var/` como si fuera una caché.

## Pruebas y evidencia

```powershell
& C:\xampp\php\php.exe tests\unit.php
python tests\members.py
```

La segunda orden crea una base de laboratorio aparte en la instancia local 3307 y usa 8081 temporalmente. No usa la base de integrantes. Consulta [pruebas v3](docs/pruebas-v3.md) para navegador, regresión y restauración. Los datos de las pruebas son inventados y no aparecen en la academia.

## Estructura

```text
app/Controllers   Portal, autenticación, contenidos, integrantes y cuentas
app/Services      Sesión/roles, validación, configuración, cifrado, migraciones
app/Models        Persistencia PDO con consultas preparadas
app/Core          Conexión centralizada, configuración y helpers
app/Views         HTML y formularios con escape de salida
config            Ejemplo público y configuración privada ignorada
database          Esquema, datos iniciales y migraciones
public            Única raíz web; CSS, JS y escudo original
scripts           Inicio local, importación, migración y alta de administrador
tests             Suites unitarias, HTTP, navegador y recuperación
docs              Manuales, diseño, resultados y evidencia histórica
var               Clave, sesiones, respaldos y laboratorio privados
```

## Límites y operación

Sin pagos, archivos clínicos, MFA, verificación automática de correo ni firma digital. Un correo único por jugador; el representante administra la cuenta del menor. Las evaluaciones son históricas de solo adición. Correcciones de nacimiento/representante, supresión integral y retención de respaldos requieren un procedimiento controlado de la academia. No hay diagnóstico médico ni control automatizado de aptitud deportiva.

El alcance cambió respecto a la versión académica inicial. Los documentos y evidencias de v1 se conservan como históricos; el estado vigente es este README y la guía de instalación XAMPP. No se acredita TRL5, cumplimiento legal completo ni seguridad absoluta. Antes de usar datos reales, el responsable debe completar la política, verificar autorizaciones, proteger infraestructura y validar el entorno actualizado.

## GitHub

Fuente sin dependencias complejas y con `.gitignore` para archivos privados. El ZIP contiene código, documentación y evidencia saneada; no bases, claves ni credenciales. No se creó repositorio remoto ni historial ficticio. Revisar los archivos preparados antes de publicar.
