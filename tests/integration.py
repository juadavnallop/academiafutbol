r"""Pruebas HTTP reales. Ejecutar solo contra una BD de pruebas terminada en _test.
Uso: python tests/integration.py --php C:\xampp\php\php.exe --url http://127.0.0.1:8080
No imprime ni conserva la contraseña aleatoria de la cuenta de prueba.
"""
import argparse
import datetime
import http.cookiejar
import json
import pathlib
import re
import statistics
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

parser = argparse.ArgumentParser()
parser.add_argument('--php', default='php')
parser.add_argument('--url', default='http://127.0.0.1:8080')
args = parser.parse_args()
root = pathlib.Path(__file__).resolve().parent.parent
fixture = json.loads(subprocess.check_output([args.php, str(root/'tests/fixture.php')], cwd=root))
results = []
tag = uuid.uuid4().hex[:10]

class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))
    def request(self, route, data=None):
        req = urllib.request.Request(args.url + '/index.php?' + route, data=urllib.parse.urlencode(data).encode() if data is not None else None)
        try:
            response = self.opener.open(req)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.read().decode(), response.url, response.headers
    def form(self, route, data):
        _, page, _, _ = self.request(route)
        data = dict(data, csrf=hidden(page, 'csrf'))
        return self.request(route, data)

def hidden(page, name):
    found = re.search(r'name="' + name + r'" value="([^"]+)"', page)
    if not found:
        raise AssertionError('No se encontró el campo ' + name)
    return found.group(1)

def check(name, condition):
    results.append({'case': name, 'passed': bool(condition)})
    print(('PASS ' if condition else 'FAIL ') + name)
    if not condition:
        save()
        raise AssertionError(name)

def save(performance=None):
    folder = root/'tests/results'
    folder.mkdir(exist_ok=True)
    (folder/'integration.json').write_text(json.dumps({'executed_at': datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=-5))).isoformat(), 'php': fixture['php'], 'database': fixture['version'], 'base_url': args.url, 'results': results, 'performance_http_seconds': performance}, ensure_ascii=False, indent=2), encoding='utf-8')

public = Client()
admin = Client()
for route in ['inicio', 'academia', 'horarios', 'noticias', 'contacto', 'login', 'privacidad']:
    status, body, _, headers = public.request('r=' + route)
    check('Portal ' + route, status == 200 and 'ACADEMIA DE FÚTBOL' in body and 'SIMULACIÓN ACADÉMICA' not in body)
check('Cabeceras CSP y nosniff', 'frame-ancestors' in headers['Content-Security-Policy'] and headers['X-Content-Type-Options'] == 'nosniff')
for route in ['admin', 'admin/contenido&mod=categorias', 'admin/solicitudes']:
    status, _, target, _ = public.request('r=' + route)
    check('Acceso privado ' + route, status == 200 and 'r=login' in target)
status, _, target, _ = public.request('r=admin/contenido&mod=categorias', {'nombre': 'Ataque'})
check('POST sin autorización', 'r=login' in target)
check('CSRF rechazado', public.request('r=contacto', {'csrf': 'invalid'})[0] == 403)
status, body, _, _ = public.form('r=contacto', {})
check('Campos vacíos backend', status == 422 and 'completa este campo' in body and 'condiciones' in body)
_, login, _, _ = admin.request('r=login')
before = next(c.value for c in admin.cookies if c.name == 'cancha_session')
status, _, _, _ = admin.form('r=login', {'correo': fixture['email'], 'password': 'incorrecta'})
check('CP05 contraseña inválida', status == 422)
status, body, _, _ = admin.form('r=login', {'correo': fixture['email'], 'password': fixture['password']})
after = next(c.value for c in admin.cookies if c.name == 'cancha_session')
check('CP05 login válido y rotación de sesión', status == 200 and 'Tu academia, al día' in body and before != after)
for cookie in admin.cookies:
    if cookie.name == 'cancha_session':
        check('Cookie HttpOnly SameSite', 'HttpOnly' in cookie._rest and cookie._rest.get('SameSite') == 'Lax')

def record(module, data):
    status, body, _, _ = admin.form('r=admin/contenido&mod=' + module, data)
    check('CP06 crear ' + module, status == 200 and 'Cambios guardados' in body)
    ids = re.findall(r'id=(\d+)" aria-label="Editar', body)
    return max(map(int, ids))

inst = {'titulo': 'Institución ' + tag, 'contenido': 'Texto institucional ficticio', 'estado': 'borrador'}
inst_id = record('institucion', inst)
check('RF01 borrador oculto', inst['titulo'] not in public.request('r=academia')[1])
inst['estado'] = 'publicado'
admin.form(f'r=admin/contenido&mod=institucion&id={inst_id}', inst)
check('CP01 institución publicada', inst['titulo'] in public.request('r=academia')[1])
category = {'nombre': 'Categoría ' + tag, 'descripcion': 'Prueba de integración', 'activo': '1'}
category_id = record('categorias', category)
slot = {'categoria_id': str(category_id), 'dia': '2', 'hora_inicio': '16:00', 'hora_fin': '17:00', 'lugar': 'Cancha ficticia ' + tag, 'vigente': '1'}
check('Rechaza horario invertido', admin.form('r=admin/contenido&mod=horarios', dict(slot, hora_fin='15:00'))[0] == 422)
slot_id = record('horarios', slot)
check('CP02 horario visible', slot['lugar'] in public.request('r=horarios')[1])
admin.form(f'r=admin/contenido&mod=horarios&id={slot_id}', dict(slot, vigente='0'))
check('RF02 horario no vigente oculto', slot['lugar'] not in public.request('r=horarios')[1])
admin.form(f'r=admin/contenido&mod=horarios&id={slot_id}', slot)
admin.form(f'r=admin/contenido&mod=categorias&id={category_id}', dict(category, activo='0'))
check('RF02 categoría inactiva oculta horarios', slot['lugar'] not in public.request('r=horarios')[1] and category['nombre'] not in public.request('r=horarios')[1])
admin.form(f'r=admin/contenido&mod=categorias&id={category_id}', category)
news = {'titulo': 'Publicación ' + tag, 'contenido': '<script>alert("demo")</script>', 'tipo': 'noticia', 'fecha': '2026-01-01', 'estado': 'borrador'}
news_id = record('publicaciones', news)
check('CP03 borrador oculto', news['titulo'] not in public.request('r=noticias')[1])
news['estado'] = 'publicado'
admin.form(f'r=admin/contenido&mod=publicaciones&id={news_id}', news)
body = public.request('r=noticias')[1]
check('CP03 publicación visible y XSS escapado', news['titulo'] in body and '&lt;script&gt;' in body and news['contenido'] not in body)
admin.form(f'r=admin/contenido&mod=publicaciones&id={news_id}', dict(news, fecha='2099-01-01'))
check('Publicación futura oculta', news['titulo'] not in public.request('r=noticias')[1])
admin.form(f'r=admin/contenido&mod=publicaciones&id={news_id}', dict(news, estado='borrador'))
check('Despublicación', news['titulo'] not in public.request('r=noticias')[1])
check('CSRF privado', admin.request('r=admin/contenido&mod=categorias', dict(category, csrf='incorrecto'))[0] == 403)
check('Módulo no permitido', admin.request('r=admin/contenido&mod=administradores')[0] == 404)
check('Registro inexistente', admin.request('r=admin/contenido&mod=categorias&id=99999999')[0] == 404)
_, form, _, _ = public.request('r=contacto')
contact = {'csrf': hidden(form, 'csrf'), 'token_envio': hidden(form, 'token_envio'), 'nombre_adulto': 'Persona ' + tag, 'contacto': 'persona@example.com', 'tipo': 'preinscripcion', 'mensaje': "Ficticio <img src=x onerror=alert(1)> ' OR 1=1 --", 'consentimiento': '1'}
check('Rechaza correo inválido', public.request('r=contacto', dict(contact, contacto='correo-invalido'))[0] == 422)
check('Rechaza falta de consentimiento', public.request('r=contacto', dict(contact, consentimiento=''))[0] == 422)
status, body, _, _ = public.request('r=contacto', contact)
check('CP04 recepción', status == 200 and 'Solicitud recibida' in body)
check('Reenvío duplicado bloqueado', public.request('r=contacto', contact)[0] == 422)
status, body, _, _ = admin.request('r=admin/solicitudes')
check('CP07 lectura privada y escape', contact['nombre_adulto'] in body and '&lt;img' in body and contact['mensaje'] not in body)
request_id = re.search(r'#(\d+) · ' + contact['nombre_adulto'], body).group(1)
status, body, _, _ = admin.form('r=admin/solicitudes', {'id': request_id, 'estado': 'cerrada'})
check('CP07 cambio de estado', status == 200 and 'Estado de la solicitud actualizado' in body and 'cerrada' in body)
check('Estado inválido', admin.form('r=admin/solicitudes', {'id': request_id, 'estado': 'otro'})[0] == 422)
check('Logout GET no modifica sesión', admin.request('r=logout')[0] == 405 and 'Tu academia, al día' in admin.request('r=admin')[1])
admin.form('r=logout', {})
check('Cierre de sesión', 'r=login' in admin.request('r=admin')[2])
for path in ['/app/Core/bootstrap.php', '/config/local.php', '/database/schema.sql', '/.git/config']:
    try:
        public.opener.open(args.url + path)
        blocked = False
    except urllib.error.HTTPError as error:
        blocked = error.code in (403, 404)
    check('Archivos internos inaccesibles ' + path, blocked)
performance = {}
for route in ['inicio', 'academia', 'horarios', 'noticias', 'contacto']:
    times = []
    for _ in range(10):
        start = time.perf_counter()
        status = public.request('r=' + route)[0]
        times.append(time.perf_counter() - start)
        assert status == 200
    performance[route] = {'samples': 10, 'median': round(statistics.median(times), 6), 'max': round(max(times), 6)}
    check('Mediana HTTP <= 3s ' + route, statistics.median(times) <= 3)
save(performance)
print(f'{len(results)} comprobaciones aprobadas. Evidencia: tests/results/integration.json')
