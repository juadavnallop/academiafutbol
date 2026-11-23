# Instalación en XAMPP

## Este computador

Carpeta: `C:\xampp\htdocs\cancha-abierta`.

Abre **http://localhost/cancha-abierta/** con Apache y MySQL encendidos en XAMPP. La aplicación usa `cancha_abierta` en el puerto 3306 y un usuario propio con permisos de lectura/escritura. No necesita el servidor de desarrollo ni el laboratorio 3307.

La configuración privada está en `config/local.php`. Apache bloquea config, database, app, scripts, docs y var. La entrada redirige a `public/`.

### Primer administrador

No hay contraseña ni cuenta predeterminada. En PowerShell ejecuta lo siguiente, sustituyendo el correo por el tuyo:

```powershell
cd C:\xampp\htdocs\cancha-abierta
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\create-admin.ps1 -Email "tu-correo@dominio.com" -Name "Administrador"
```

La terminal pide una contraseña de al menos 12 caracteres sin mostrarla. Luego inicia sesión. El comando no modifica cuentas existentes.

En **Academia**, completa nombre, contacto, responsable y política de datos; después habilita inscripciones. En **Información institucional**, publica la presentación de la academia. Registra categorías, horarios y noticias desde el panel. Las pantallas se llenan con esa información, sin registros inventados.

## SQL para importar en otra instalación limpia

Archivo: **`database/instalar_xampp.sql`**. Crea `cancha_abierta`, sus 14 tablas y contenido institucional inicial. No incluye claves ni usuarios de acceso. **No lo importes otra vez si ya existe la base instalada.**

1. En otro XAMPP, copia el proyecto con `scripts/install-xampp.ps1`; prepara reglas de Apache y se niega a sobrescribir la carpeta existente.
2. Abre `http://localhost/phpmyadmin`, selecciona **Importar**, elige `instalar_xampp.sql` y ejecuta. La base debe estar vacía o no existir.
3. En la pestaña SQL, crea un usuario con contraseña propia:

```sql
CREATE USER 'cancha_web'@'127.0.0.1' IDENTIFIED BY 'REEMPLAZAR_POR_UNA_CLAVE_PROPIA';
GRANT SELECT, INSERT, UPDATE, DELETE ON cancha_abierta.* TO 'cancha_web'@'127.0.0.1';
```

4. Copia `config/config.example.php` a `config/local.php` y define `db_name=cancha_abierta`, `db_port=3306`, `db_user=cancha_web`, la contraseña elegida y `base_path=/cancha-abierta/public`.
5. En la carpeta instalada ejecuta `C:\xampp\php\php.exe scripts\migrate.php --admin=root`. Introduce la contraseña del administrador MySQL y Enter; si no tiene contraseña, solo Enter. Prepara la clave privada de cifrado y comprueba migraciones.
6. Crea tu administrador con el comando anterior y abre la web.

Como alternativa a importar manualmente, `scripts/provision-xampp.php` crea una base nueva y configura automáticamente una cuenta privada de aplicación. Se niega a modificar una base `cancha_abierta` existente.

## Fichas técnicas

El jugador entra a su **Resumen técnico**: categoría, posición, pie dominante, dorsal, última medición, diferencias con la anterior, habilidades 1–5, evolución de hasta 12 evaluaciones y próximas sesiones. Puede imprimir o guardar PDF mediante el navegador. Los valores sin evaluar aparecen pendientes, no como cero.

Administrador y asistente abren **Integrantes → Abrir ficha** para asignar categoría y registrar evaluaciones fechadas con su autor. Los registros anteriores se conservan. El resumen impreso excluye salud. El asistente no accede a salud; el administrador necesita autorización activa del titular. El jugador solo accede a su propia ficha.

## Respaldo y operación

Respalda la base y conserva separadamente `var/keys/health.key`, necesaria para recuperar salud cifrada. Conserva la configuración privada. No borres `var` como caché ni compartas estos archivos en un ZIP público.

Verificado localmente en PHP 8.2.12 y MariaDB 10.4.32 del XAMPP de este equipo. Publicar en Internet requiere alojamiento con HTTPS y versiones mantenidas. Esta entrega no incluye dominio ni publicación externa.
