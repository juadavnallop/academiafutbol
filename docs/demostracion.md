> Documento histórico de la versión 1 (11/11/2026). Para la versión actual con integrantes, roles y salud, consultar [integrantes](integrantes.md), [migración v2](migracion-v2.md) y [pruebas v2](pruebas-v2.md). Las restricciones y funciones descritas aquí corresponden al alcance anterior.

# Guion de demostración

## Preparación

Usar una instalación aislada con datos ficticios; seguir README. Tener dos ventanas: una autenticada en el panel y otra privada para el portal. Verificar que no se exponen archivos de configuración, contraseñas ni registros personales al proyectar la pantalla.

En este equipo quedó una base limpia `academia_demo` en la instancia aislada de MariaDB del proyecto, puerto 3307. No se modificaron las bases existentes de XAMPP. La configuración privada utiliza una cuenta de aplicación dedicada. El autor semilla está **inactivo**, sin contraseña disponible; crear una cuenta propia para entrar.

La instancia desechable conserva el usuario root local sin contraseña del inicializador de MariaDB; está enlazada solo a loopback y se usa exclusivamente para desarrollo. No trasladar esa configuración a un servidor compartido. La instalación reproducible del README utiliza cuentas y contraseñas propias.

```powershell
Set-Location 'C:\Users\LOPEZ NARANJO\Desktop\Proyecto'
.\scripts\start-local-demo.ps1
# En otra terminal, mientras la instancia esté iniciada:
.\scripts\create-admin.ps1 -Email 'admin@example.com' -Name 'Administrador Demo'
```

Abrir http://127.0.0.1:8080. Esta ayuda usa XAMPP local existente; no cumple la validación de versiones objetivo. La carpeta privada `var/mysql` no se distribuye por GitHub. En otro equipo, usar la instalación MySQL 8 del README.

## Secuencia observable

| Paso | Acción | Comprobación |
|---|---|---|
| 1 | Iniciar sesión | Aparece panel con contadores obtenidos de BD |
| 2 | Crear categoría “Entrenamiento de prueba” activa | Registro visible en panel |
| 3 | Crear horario martes 16:00–17:30, “Cancha ficticia B”, vigente | Relación y horario guardados |
| 4 | Confirmar estado activo/vigente | Estado persistido tras recargar |
| 5 | Abrir Horarios en ventana privada | Misma categoría y horario visibles |
| 6 | Crear noticia con fecha actual, publicar | Noticia aparece en Actualidad |
| 7 | Enviar solicitud con “Persona Demo”, `persona@example.com`, aceptación | Confirmación sin prometer cupo |
| 8 | Consultar Solicitudes en panel | Mensaje y fecha coinciden |
| 9 | Cambiar a revisada y luego cerrada | Estado permanece al recargar |
| 10 | Cerrar sesión e intentar volver a Solicitudes | Se exige iniciar sesión |

Como casos complementarios, desactivar la categoría y observar que sus horarios desaparecen; retirar una noticia a borrador; enviar formulario sin consentimiento; intentar acceder directamente a `config/local.php`. No mostrar una consulta fallida como éxito.

## Cierre

Registrar entorno, fecha real, acciones, resultados, incidencias y archivos de evidencia. Conservar respaldos fuera de `public/`. Los datos ficticios de la sesión se retiran mediante un reinicio controlado de la base de demostración cuando dejen de ser necesarios; no mezclar con una base institucional. El guion demuestra integración funcional, no certifica por sí solo TRL5.
