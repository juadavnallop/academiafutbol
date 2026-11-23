> Documento histórico de la versión 1 (11/11/2026). Para la versión actual con integrantes, roles y salud, consultar [integrantes](integrantes.md), [migración v2](migracion-v2.md) y [pruebas v2](pruebas-v2.md). Las restricciones y funciones descritas aquí corresponden al alcance anterior.

# Manual básico

## Acceso

1. Instalar siguiendo README y crear cuenta mediante `scripts/create-admin.ps1`.
2. Abrir el portal y elegir **Acceso administrativo** en el pie.
3. Ingresar correo y contraseña. Cinco fallos acumulados bloquean temporalmente la cuenta. Una sesión inactiva vence a los 30 minutos; conserva externamente un borrador antes de pausas largas.

## Información institucional

En **Información institucional**, completar título, contenido y estado. Guardar pide confirmación en el navegador. Un borrador solo aparece en el panel. Un contenido publicado aparece en Academia; el primero por ID se usa también en la presentación de Inicio. Crear apartados de formación, servicios, información general o contacto; editar el correspondiente mediante su enlace **Editar**.

## Categorías y horarios

Crear categoría con nombre único, descripción y estado. En Horarios, seleccionar categoría, día, hora inicial, hora final, lugar y vigencia. La final debe ser posterior a la inicial; los turnos que cruzan medianoche no están contemplados. Un horario solo aparece si su categoría está activa y su vigencia está habilitada. El estado inactivo de una categoría es reversible, no borra sus horarios.

## Actualidad

Crear título, texto, tipo, fecha y estado. **Publicado** con fecha de hoy o pasada aparece en Actualidad; una fecha futura programa su visibilidad para ese día en Colombia. Para retirar un contenido, editar y cambiar a **Borrador**. El texto se trata como texto plano: etiquetas HTML se muestran literalmente y no se ejecutan.

## Solicitudes

La lista privada muestra adulto ficticio, contacto, tipo, fecha, mensaje y aceptación registrada. Cambiar entre pendiente, revisada y cerrada y pulsar **Actualizar estado**. Esta acción no envía correo ni asigna cupo. Usar los enlaces de paginación al acumular más de 20 solicitudes.

## Salida y recuperación

Usar **Cerrar sesión**; la operación usa POST protegido. La aplicación no ofrece restablecimiento por correo. Un responsable con acceso a la instalación puede crear otra cuenta por CLI y desactivar la anterior mediante una intervención de BD autorizada. No editar hashes manualmente ni compartir la cuenta entre participantes durante validación.

Si aparece “Servicio temporalmente no disponible”, registrar la referencia, comprobar servicio MySQL y `config/local.php`, y revisar logs privados del servidor. Si un formulario venció, recargar y volver a introducir los cambios. Si indica duplicidad, editar el registro existente o elegir otro nombre/horario.
