-- Solo datos ficticios. Importar una vez, después de crear el administrador por CLI.
START TRANSACTION;
INSERT INTO contenidos_institucionales (titulo, contenido, estado) VALUES
('Un espacio para aprender en equipo', 'Cancha Abierta es una identidad ficticia creada para demostrar la gestión de una academia de fútbol en Ibagué, Tolima. Este portal permite consultar información y ensayar la comunicación entre una academia y adultos responsables.', 'publicado'),
('Formación deportiva', 'En este escenario simulado, la formación se organiza en iniciación, desarrollo técnico y práctica colectiva. Se priorizan el juego limpio, el aprendizaje progresivo y la colaboración.', 'publicado'),
('Servicios y actividades', 'La demostración incluye consulta de entrenamientos por categoría, publicación de actividades y recepción de solicitudes ficticias. No ofrece servicios deportivos reales ni asigna cupos.', 'publicado'),
('Información y contacto', 'Ubicación de referencia: Ibagué, Tolima. No existe una sede física asociada a este prototipo. Canal de contacto: formulario demostrativo del portal; no se envían correos ni se atienden solicitudes reales.', 'publicado');
INSERT INTO categorias (nombre, descripcion, activo) VALUES
('Iniciación · demo', 'Primer acercamiento al balón y al juego compartido. Categoría ficticia para pruebas.', 1),
('Formación · demo', 'Ejercicios técnicos y toma de decisiones en equipo. Categoría ficticia para pruebas.', 1),
('Proyección · demo', 'Práctica colectiva y comprensión del juego. Categoría ficticia para pruebas.', 1);
INSERT INTO horarios (categoria_id,dia,hora_inicio,hora_fin,lugar,vigente)
SELECT id, 2, '16:00', '17:30', 'Cancha simulada A · sin sede real', 1 FROM categorias WHERE nombre='Iniciación · demo';
INSERT INTO horarios (categoria_id,dia,hora_inicio,hora_fin,lugar,vigente)
SELECT id, 4, '16:00', '17:30', 'Cancha simulada A · sin sede real', 1 FROM categorias WHERE nombre='Formación · demo';
INSERT INTO publicaciones (titulo,contenido,tipo,fecha,estado,administrador_id)
SELECT 'Una agenda compartida para el equipo', 'Contenido ficticio. Los horarios de esta demostración se consultan en un solo lugar y pueden actualizarse desde el panel administrativo.', 'noticia', CURRENT_DATE, 'publicado', id FROM administradores ORDER BY id LIMIT 1;
INSERT INTO publicaciones (titulo,contenido,tipo,fecha,estado,administrador_id)
SELECT 'Encuentro de juego limpio · simulación', 'Actividad ficticia para validar la publicación de contenidos. No corresponde a un evento convocado ni requiere asistencia.', 'actividad', CURRENT_DATE, 'publicado', id FROM administradores ORDER BY id LIMIT 1;
COMMIT;
