"""End-to-end de integrantes en instancia XAMPP local aislada (puerto BD 3307).
No modifica config/local.php ni la base de la academia. Python sin dependencias.
"""
import datetime, http.cookiejar, json, os, pathlib, re, secrets, subprocess, time, urllib.request, urllib.parse, urllib.error
ROOT=pathlib.Path(__file__).resolve().parent.parent
PHP=os.environ.get('PHP_BIN',r'C:\xampp\php\php.exe')
fixture=json.loads(subprocess.check_output([PHP,str(ROOT/'tests/setup-members.php'),'members_'+secrets.token_hex(5)+'_test'],cwd=ROOT))
env=dict(os.environ,CANCHA_CONFIG=fixture['config'])
base='http://127.0.0.1:8081'
results=[]
log=(ROOT/'var/members-server.log').open('w',encoding='utf-8')
server=subprocess.Popen([PHP,'-S','127.0.0.1:8081','-t','public'],cwd=ROOT,env=env,stdout=log,stderr=log)
class Client:
    def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(self,route,data=None):
        req=urllib.request.Request(base+'/index.php?r='+route,data=urllib.parse.urlencode(data).encode() if data is not None else None)
        try: res=self.opener.open(req)
        except urllib.error.HTTPError as error: res=error
        return res.status,res.read().decode(),res.url
    def form(self,route,data):
        page=self.request(route)[1]
        return self.request(route,dict(data,csrf=hidden(page,'csrf')))
def hidden(page,name):
    match=re.search('name="'+name+'" value="([^"]+)"',page)
    if not match: raise AssertionError('Falta campo '+name)
    return match.group(1)
def check(name,ok):
    results.append({'case':name,'passed':bool(ok)})
    print(('PASS ' if ok else 'FAIL ')+name)
    if not ok: raise AssertionError(name)
def db(player): return json.loads(subprocess.check_output([PHP,'tests/members-db.php',str(player)],cwd=ROOT,env=env))
try:
    public=Client();admin=Client();assistant=Client()
    for _ in range(50):
        try:
            if public.request('inicio')[0]==200: break
        except OSError: pass
        time.sleep(.1)
    for route in ['inicio','academia','horarios','noticias','login','privacidad']:
        status,body,_=public.request(route)
        check('Portal limpio '+route,status==200 and not re.search('SIMULACIÓN|identidad ficticia|Proyecto de grado|ACADEMIA DE FÚTBOL · DEMO',body,re.I))
    check('Registro cerrado antes de configuración','no están habilitadas' in public.request('registro')[1])
    status,body,_=admin.form('login',{'correo':fixture['administrador']['email'],'password':fixture['administrador']['password']})
    check('Administrador existente puede ingresar',status==200 and 'Tu academia, al día' in body)
    settings={'nombre':'Academia QA','ciudad':'','correo':'privacidad@example.com','telefono':'','responsable':'Responsable QA','politica':'Política de pruebas controladas: inscripción y seguimiento deportivo. Conservación durante el ensayo; solicitudes de derechos al correo indicado.','inscripciones_abiertas':'1'}
    check('Configurar academia',admin.form('admin/configuracion',settings)[0]==200)
    check('Marca configurable','Academia QA' in public.request('inicio')[1])
    check('CSRF en registro',public.request('registro',{'csrf':'incorrecto'})[0]==403)
    check('Registro vacío rechazado',public.form('registro',{})[0]==422)
    password=secrets.token_hex(10)
    data={'nombre':'Jugador QA','correo':'jugador@example.com','fecha_nacimiento':'2000-01-01','telefono':'3001234567','direccion':'','emergencia_nombre':'','emergencia_telefono':'','tutor_nombre':'','tutor_parentesco':'','tutor_contacto':'','password':password,'password_confirmation':password,'consentimiento':'1','rol':'administrador'}
    check('Sin autorización de datos rechazado',public.form('registro',dict(data,consentimiento=''))[0]==422)
    status,body,target=public.form('registro',data)
    check('Inscripción y acceso propio',status==200 and 'mi-ficha' in target and 'Jugador QA' in body)
    player=int(re.search(r'FICHA DEL JUGADOR · #(\d+)',body).group(1))
    check('Rol elevado por POST ignorado',db(player)['role']=='jugador')
    for route in ['equipo','equipo/ficha&id='+str(player),'admin','admin/cuentas','admin/configuracion','admin/auditoria']:
        check('Jugador sin permiso '+route,public.request(route)[0]==403)
    check('IDOR lectura propia',public.request('mi-ficha&id='+str(player+1))[0]==403)
    check('Jugador no puede cambiar inscripción',public.form('mi-ficha&tab=inscripcion',{'estado':'activa'})[0]==403)
    check('Jugador no puede modificar evaluación',public.form('mi-ficha&tab=evaluaciones',{'fecha':'2026-01-01','control':'5'})[0]==403)
    personal={'telefono':'3117654321','direccion':'Calle de prueba <script>alert(1)</script>','emergencia_nombre':'Contacto QA','emergencia_telefono':'3107654321'}
    status,body,_=public.form('mi-ficha',personal)
    check('Datos personales guardados y XSS escapado',status==200 and '&lt;script&gt;' in body and '<script>alert' not in body)
    second=Client()
    minor=dict(data,nombre='Jugador menor QA',correo='menor@example.com',fecha_nacimiento='2014-02-01')
    check('Menor sin representante rechazado',second.form('registro',minor)[0]==422)
    minor.update(tutor_nombre='Representante QA',tutor_parentesco='Madre',tutor_contacto='3009876543',representante='1')
    status,body,_=second.form('registro',minor)
    check('Inscripción con representante',status==200 and 'Representante QA' in second.request('mi-ficha&tab=personal')[1])
    second_id=int(re.search(r'FICHA DEL JUGADOR · #(\d+)',body).group(1))
    check('No leer salud de otro jugador',second.request('mi-ficha&id='+str(player)+'&tab=salud')[0]==403)
    duplicate=Client()
    check('Correo duplicado controlado',duplicate.form('registro',data)[0]==422)
    assistant.form('login',{'correo':fixture['asistente']['email'],'password':fixture['asistente']['password']})
    check('Asistente ve integrantes','Jugador QA' in assistant.request('equipo')[1])
    check('Búsqueda funciona','Jugador QA' not in assistant.request('equipo&q=NOCOINCIDE')[1])
    for route in ['admin','admin/cuentas','admin/configuracion','admin/auditoria','equipo/ficha&id='+str(player)+'&tab=salud']:
        check('Asistente sin permiso '+route,assistant.request(route)[0]==403)
    profile='equipo/ficha&id='+str(player)
    check('Asistente no modifica datos personales',assistant.form(profile,personal)[0]==403)
    check('Asignación futbolística',assistant.form(profile+'&tab=deportiva',{'posicion':'delantero','pie':'derecho','dorsal':'9','experiencia':'Objetivo: mejorar el pase'})[0]==200)
    check('Dorsal inválido',assistant.form(profile+'&tab=deportiva',{'dorsal':'200'})[0]==422)
    check('Categoría inexistente',assistant.form(profile+'&tab=deportiva',{'categoria_id':'999999'})[0]==422)
    _,form,_=assistant.request(profile+'&tab=evaluaciones')
    evaluation={'csrf':hidden(form,'csrf'),'token':hidden(form,'token'),'fecha':'2026-01-01','altura_cm':'174','peso_kg':'68.5','velocidad_30m':'5.2','resistencia_min':'12','control':'3','pase':'4','regate':'3','remate':'4','trabajo_equipo':'5','observaciones':'Protocolo de prueba'}
    check('Peso fuera de rango rechazado',assistant.request(profile+'&tab=evaluaciones',dict(evaluation,peso_kg='999'))[0]==422)
    check('Evaluación guardada',assistant.request(profile+'&tab=evaluaciones',evaluation)[0]==200)
    check('Doble envío evaluación rechazado',assistant.request(profile+'&tab=evaluaciones',evaluation)[0]==422 and db(player)['evaluations']==1)
    check('Jugador consulta historial','Protocolo de prueba' in public.request('mi-ficha&tab=evaluaciones')[1])
    check('Activación sin verificación rechazada',assistant.form(profile+'&tab=inscripcion',{'estado':'activa'})[0]==422)
    check('Actualizar inscripción',assistant.form(profile+'&tab=inscripcion',{'estado':'activa','observaciones':'Autorización verificada en ensayo','verificacion':'Expediente QA-001'})[0]==200 and 'Inscripción activa' in public.request('mi-ficha')[1])
    clinical={'eps':'CLINICAL_MARKER','alergias':'Dato de ensayo','grupo_sanguineo':'O+'}
    check('Salud requiere autorización explícita',public.form('mi-ficha&tab=salud',clinical)[0]==422)
    check('Admin no inventa autorización de salud',admin.form(profile+'&tab=salud',clinical)[0]==403)
    check('Titular autoriza salud',public.form('mi-ficha&tab=salud',dict(clinical,consentimiento_salud='1'))[0]==200)
    state=db(player)
    check('Salud cifrada y vinculada al jugador',state['cipher_exists'] and state['cipher_plain_absent'] and state['aad_protected'] and state['health_consents']==1)
    check('Admin autorizado consulta salud','CLINICAL_MARKER' in admin.request(profile+'&tab=salud')[1])
    check('Salud no aparece en ficha deportiva','CLINICAL_MARKER' not in assistant.request(profile+'&tab=deportiva')[1])
    check('CSRF en ficha de salud',public.request('mi-ficha&tab=salud',dict(clinical,csrf='invalid'))[0]==403)
    check('Revocación elimina ficha',public.form('mi-ficha&tab=salud',{'accion':'revocar'})[0]==200 and not db(player)['cipher_exists'] and db(player)['health_consents']==0)
    check('Admin pierde acceso de escritura tras revocación',admin.form(profile+'&tab=salud',clinical)[0]==403)
    check('Nueva autorización tras revocación',public.form('mi-ficha&tab=salud',dict(clinical,consentimiento_salud='1'))[0]==200 and db(player)['health_consents']==1)
    check('Auditoría sin contenido médico','salud consultada' in admin.request('admin/auditoria')[1] and 'CLINICAL_MARKER' not in admin.request('admin/auditoria')[1])
    temp=secrets.token_hex(10)
    check('Crear cuenta de asistente',admin.form('admin/cuentas',{'nombre':'Asistente temporal','correo':'temporal@example.com','rol':'asistente','activo':'1','password':temp,'password_confirmation':temp})[0]==200)
    staff=Client();status,body,target=staff.form('login',{'correo':'temporal@example.com','password':temp})
    check('Contraseña temporal obliga a cambiar','r=cuenta' in target and 'reemplaza tu contraseña' in body)
    check('No saltar cambio obligatorio','r=cuenta' in staff.request('equipo')[2])
    fresh=secrets.token_hex(10)
    check('Cambio contraseña válido',staff.form('cuenta',{'actual':temp,'password':fresh,'password_confirmation':fresh})[0]==200 and 'r=equipo' in staff.request('equipo')[2])
    check('Asistente no crea cuenta privilegiada',staff.form('admin/cuentas',{'nombre':'Ataque','rol':'administrador'})[0]==403)
    self_data={'nombre':'Administrador QA','correo':fixture['administrador']['email'],'rol':'asistente','activo':'0'}
    check('Admin no se desactiva a sí mismo',admin.form('admin/cuentas&id='+str(fixture['administrador']['id']),self_data)[0]==422)
    assistant_id=fixture['asistente']['id']
    check('Deshabilitar asistente',admin.form('admin/cuentas&id='+str(assistant_id),{'nombre':'Asistente QA','correo':fixture['asistente']['email'],'rol':'asistente','activo':'0'})[0]==200)
    check('Sesión revocada inmediatamente','r=login' in assistant.request('equipo')[2])
    experience=json.loads(subprocess.check_output([PHP,'tests/experience-fixture.php',str(player),str(fixture['administrador']['id'])],cwd=ROOT,env=env))
    page=public.request('horarios')[1]
    check('Horarios activos y categorías activas',all(x in page for x in ['CANCHA_UNO','CANCHA_DOS']) and 'OCULTO_' not in page)
    page=public.request('horarios&categoria='+str(experience['first']))[1]
    check('Filtro categoría','CANCHA_UNO' in page and 'CANCHA_DOS' not in page)
    page=public.request('horarios&dia=3')[1]
    check('Filtro día','CANCHA_DOS' in page and 'CANCHA_UNO' not in page)
    page=public.request('horarios&dia=3&categoria='+str(experience['first']))[1]
    check('Filtros combinados sin resultados','CANCHA_UNO' not in page and 'CANCHA_DOS' not in page)
    page=public.request('noticias&tipo=actividad')[1]
    check('Filtro de publicaciones','Actividad visible QA' in page and 'Noticia visible QA' not in page)
    status,page,_=public.request('publicacion&id='+str(experience['articles'][0]))
    check('Artículo completo y escape HTML',status==200 and '&lt;script&gt;' in page and '<script>alert' not in page)
    for article in experience['articles'][2:]:
        check('Publicación privada o futura devuelve 404 '+str(article),public.request('publicacion&id='+str(article))[0]==404)
    page=public.request('mi-ficha')[1]
    check('Inicio privado muestra resumen técnico','PANORAMA DEPORTIVO' in page and 'Seguimiento: precisión' in page)
    check('Resumen con dos evaluaciones y diferencia','chart-point' in page and '+0,50' in page and 'CANCHA_UNO' in page)
    check('Salud excluida de resumen','CLINICAL_MARKER' not in page)
    check('Selector de evolución', 'Velocidad' in public.request('mi-ficha&metric=velocidad_30m')[1])
    check('Jugador no modifica resumen',public.form('mi-ficha&tab=resumen',{'peso_kg':'90'})[0]==403)
    public.form('logout',{})
    check('Jugador cierra sesión','r=login' in public.request('mi-ficha')[2])
    # Credenciales efímeras, solo para la prueba de navegador; nunca se copian a docs.
    fixture['jugador']={'email':data['correo'],'password':password,'player_id':player}
    fixture['asistente_activo']={'email':'temporal@example.com','password':fresh}
    (ROOT/'var/members-fixture.json').write_text(json.dumps(fixture),encoding='utf-8')
finally:
    server.terminate();server.wait(timeout=10);log.close()
    folder=ROOT/'tests/results';folder.mkdir(exist_ok=True)
    (folder/'members.json').write_text(json.dumps({'date':datetime.datetime.now(datetime.timezone.utc).isoformat(),'php':fixture['php'],'database':fixture['version'],'results':results},ensure_ascii=False,indent=2),encoding='utf-8')
print(str(len(results))+' comprobaciones de integrantes aprobadas.')
