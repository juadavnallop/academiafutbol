# Gestión de integrantes · versión 2

## Puesta en marcha en este equipo

La migración ya está aplicada a la base local de la academia. Las cuentas y registros previos se conservan; los contenidos iniciales de ejemplo se retiraron de publicación. El nombre inicial sigue siendo Cancha Abierta, editable desde el panel.

1. Inicia la aplicación con `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-local.ps1` si no está funcionando.
2. Abre http://127.0.0.1:8080 y pulsa **Iniciar sesión**.
3. Si aún no creaste administrador, en otra terminal ejecuta `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\create-admin.ps1 -Email "admin@example.com" -Name "Administrador"`. Elige una contraseña propia; no hay una cuenta de acceso predeterminada.
4. En **Academia**, registra nombre, contacto, responsable del tratamiento y política de datos. Abre las inscripciones cuando estos datos estén completos.
5. Crea categorías y horarios reales desde sus módulos. Activa únicamente información verificada.
6. En **Cuentas y roles**, crea asistentes con contraseña temporal; deben cambiarla al ingresar.
7. Los jugadores se inscriben desde el enlace de acceso o desde **Inscripción de jugadores**. El administrador también puede inscribirlos si cuenta con autorización previa y registra su referencia.

No se inventó el responsable legal ni el correo de la academia. Hasta que se configuren, la inscripción no recopila datos. El uso con menores y datos de salud requiere revisar la política y la operación institucional; los controles de software no sustituyen esa revisión.

## Permisos efectivos

| Función | Jugador / representante | Asistente | Administrador |
|---|---|---|---|
| Datos personales | Ver propios; editar teléfono, dirección y emergencia | Ver fichas de integrantes | Ver y editar contacto |
| Datos de identidad y representante | Declarados al inscribirse | Consulta | Nombre/correo en cuentas; otras correcciones requieren revisión controlada |
| Ficha futbolística | Consultar propia | Consultar y editar | Consultar y editar |
| Evaluaciones físicas y técnicas | Consultar historial propio | Registrar y consultar | Registrar y consultar |
| Inscripción | Consultar estado propio | Revisar, activar, rechazar o retirar con referencia de verificación | Igual y alta de jugadores |
| Ficha de salud | Autorizar, consultar, editar y revocar la propia | Sin acceso, incluso por URL o POST | Consultar y editar solo si hay autorización activa |
| Cuentas, roles y contraseñas temporales | No | No | Sí |
| Contenido público y solicitudes | Consultar portal | Consultar portal | Administrar |
| Configuración y auditoría | No | No | Sí |

El nombre histórico de la tabla `administradores` se conserva para compatibilidad: ahora almacena todas las cuentas y el campo `rol`. Un jugador tiene una relación única con `jugadores`. Los permisos se comprueban en el servidor; ocultar enlaces no es el mecanismo de protección.

## Inscripción y representantes

Se registra nombre, correo de acceso, nacimiento, teléfono, dirección y contacto de emergencia opcionales. Para menores, representante legal, vínculo y contacto son obligatorios, junto con la declaración correspondiente. El representante administra la cuenta del jugador; no se añadió un cuarto rol de acudiente ni administración de varios menores desde una sola cuenta. Cada jugador utiliza un correo de acceso único.

La inscripción comienza pendiente. El personal puede pasarla a en revisión, activa, rechazada o retirada. La activación exige una referencia a la revisión de identidad y autorización. Esta referencia no constituye verificación automática de documentos: el personal debe realizarla. El estado de inscripción no cambia el rol ni la habilitación de acceso; deshabilitar una cuenta es una acción separada del administrador.

## Seguimiento físico y futbolístico

Ficha: categoría, posición, pie dominante, dorsal y trayectoria/objetivos. Evaluaciones: fecha, estatura, peso, tiempo en 30 metros, minutos de resistencia, control, pase, regate, remate, trabajo en equipo y observaciones del protocolo. Las medidas tienen unidad y límites de entrada; los límites evitan errores de captura, no son criterios de aptitud médica.

Las valoraciones técnicas utilizan escala 1–5 descrita en pantalla. Cada evaluación conserva fecha y autor. Son registros históricos de solo adición: para corregir una medición, crear una nueva evaluación explicando la corrección. No se calcula diagnóstico, riesgo clínico ni una puntuación automática de selección.

## Salud, autorizaciones y trazabilidad

Entidad de salud, grupo sanguíneo conocido, alergias, antecedentes relevantes, medicamentos y restricciones indicadas por el profesional tratante son opcionales. El titular/representante debe autorizar expresamente el tratamiento antes de guardarlos. El administrador no puede otorgar esa autorización desde su propia cuenta.

La autorización conserva versión, otorgante, calidad, fecha, usuario que la registró y una copia de la política aceptada. El acceso y los cambios de salud se registran en auditoría sin copiar datos médicos. El contenido de la ficha se cifra con AES-256-GCM, IV aleatorio, etiqueta de autenticación y asociación al identificador del jugador; la clave se guarda en `var/keys/health.key`, fuera del DocumentRoot y de Git.

Revocar elimina la ficha clínica de la base activa y marca la autorización como revocada, conservando la evidencia del acto. Los respaldos requieren un procedimiento de retención y propagación de revocaciones al restaurar. La revocación de salud no elimina las evaluaciones deportivas ni cierra la cuenta. Debe informarse esa distinción en la política institucional.

No es una historia clínica. El cifrado en reposo no sustituye HTTPS, ACL Windows, custodia de la clave, control del personal y respaldos protegidos. Los datos personales y las evaluaciones deportivas permanecen en tablas con acceso restringido; únicamente el contenido de la ficha de salud tiene cifrado de campo.

Referencias para revisión institucional: [SIC: datos sensibles de salud](https://sedeelectronica.sic.gov.co/publicaciones/boletin-juridico/concepto/tratamiento-de-datos-sensibles-relativos-la-salud) y [Decreto 1377 de 2013, tratamiento de datos de menores](https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=53646). No se declara cumplimiento legal completo ni se sustituye la revisión del responsable de datos.

## Cuentas y recuperación de acceso

Las altas de personal y de jugadores realizadas por el administrador generan una contraseña temporal elegida por ese administrador. Se entrega por un canal privado y se obliga a cambiarla. No hay envío automático de correos. La contraseña solo se almacena como hash.

Cambiar contraseña o modificar una cuenta incrementa su versión de sesión. Las sesiones anteriores quedan invalidadas en la próxima petición; una cuenta deshabilitada pierde acceso. No se permite que un administrador se quite su propio rol o se deshabilite, ni dejar el sistema sin administrador activo. Asignar el rol jugador requiere una inscripción asociada.

## Límites que permanecen

- Sin verificación automática de correo, firma digital, archivos clínicos, pagos o matrículas financieras.
- Sin MFA ni restablecimiento por correo; el administrador verifica identidad y entrega una contraseña temporal.
- Asistentes con acceso a todos los integrantes; no hay asignación por entrenador o categoría.
- No hay edición web de nacimiento/representante tras inscripción ni borrado general de integrantes. Correcciones y solicitudes de supresión necesitan revisión controlada del responsable.
- Historial de una inscripción por jugador; no se modelaron múltiples temporadas.
- La auditoría es operativa, no inmutable frente a un administrador de base de datos.
- No se publicó en Internet ni se acreditó TRL5. PHP 8.4 actualizado/MySQL8 siguen siendo el entorno objetivo pendiente de validación; pruebas ejecutadas en PHP 8.2.12/MariaDB 10.4.32.
