# Protocolo de preparación para TRL5

**Estado: preparado para validación; nivel no acreditado.** Las pruebas locales de desarrollo no demuestran por sí solas validación en un entorno relevante. No se realizaron sesiones con una academia real ni se recabaron avales universitarios.

## Propósito y condiciones

Evaluar la integración del portal, administración, autenticación y base de datos dentro de un escenario simulado que represente la actualización de horarios y la comunicación con adultos responsables de una academia de fútbol.

Antes del ensayo: identificar responsable autorizado del estudio, escenario representativo, dispositivos disponibles y roles de prueba (administrador, adulto ficticio y observador). No registrar participantes ni consentimientos como realizados antes de que existan. La identidad demo y los datos inventados deben permanecer visibles.

Usar PHP 8.4 actualizado y MySQL 8, registrar sistema operativo, CPU/RAM, versiones, configuración del servidor web, red y resolución. Congelar versión del código mediante hash o commit real creado para el ensayo, sin inventar historial. Instalar desde README en base vacía; preparar respaldo y comprobar restauración antes de modificar el escenario.

## Ejecución

Seguir los diez pasos de `demostracion.md`, registrar resultado esperado y observado de cada paso, capturas sin secretos y registros de persistencia. Cada participante utiliza datos ficticios. Incorporar fallos deliberados: formulario incompleto, consentimiento ausente, fin de horario anterior al inicio, borrador, categoría inactiva, acceso privado sin sesión y token CSRF inválido.

Medir diez cargas de Inicio, Academia, Horarios, Actualidad y Contacto, especificando caché, red y carga concurrente; calcular mediana y comparar con ≤3 segundos. Revisar 360/768/1366 píxeles, teclado, foco y etiquetas; complementar con lector de pantalla y contraste.

## Registro a completar durante el ensayo

| Campo | Valor pendiente |
|---|---|
| Fecha y responsable real | Por completar |
| Versión exacta y hash del código | Por completar |
| Entorno y relación con la operación de una academia | Por justificar |
| Roles/participantes autorizados | Por completar, sin inventar nombres |
| Casos ejecutados y denominador crítico definido | Por completar |
| Casos satisfactorios / fallidos / no ejecutados | Por completar |
| Incidencias bloqueantes y correcciones | Por completar |
| Evidencias, ubicación y responsable de custodia | Por completar |
| Conclusión y limitaciones | Por evaluar |

## Criterio de salida

Al menos 90% de los casos críticos previamente definidos satisfactorios, cero defectos bloqueantes, base restaurable, restricciones de privacidad verificadas y justificación del entorno relevante. No excluir del denominador casos fallidos o no ejecutados para mejorar la cifra. La decisión de madurez corresponde a la evaluación académica sustentada en evidencia real; este documento no la anticipa.

## Diferencias de estado

- **Implementado:** todos los módulos RF01–RF07 y controles descritos en arquitectura.
- **Probado:** suites y entorno concretos de `resultados.md`.
- **Pendiente:** entorno objetivo MySQL8/PHP8.4, ensayo relevante, revisión humana de accesibilidad y aceptación académica.
- **Evidencia:** JSON y capturas locales, scripts reproducibles; no hay actas de evaluación ni resultados de usuarios reales.
- **Limitaciones:** prueba local sin concurrencia, PHP instalado desactualizado, MariaDB no equivalente a aceptación MySQL8, alcance acotado y sin tratamiento real de datos.
