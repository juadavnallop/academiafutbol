"""Read-only smoke checks of the actual Apache installation."""
import json, pathlib, subprocess, urllib.request, urllib.error, datetime
root=pathlib.Path(__file__).resolve().parent.parent
base='http://localhost/cancha-abierta/'
results=[]
def get(path):
    try: response=urllib.request.urlopen(base+path)
    except urllib.error.HTTPError as error: response=error
    return response.status,response.read().decode(errors='replace'),dict(response.headers)
def check(name,ok):
    results.append({'case':name,'passed':bool(ok)})
    if not ok: raise AssertionError(name)
status,page,headers=get('')
check('Root redirects to functioning public portal',status==200 and 'El talento se entrena' in page)
for route in ['inicio','academia','horarios','noticias','login','registro','privacidad']:
    status,page,headers=get('public/index.php?r='+route)
    check('Apache route '+route,status==200 and 'temporalmente no disponible' not in page)
    check('No sample identity '+route,'Academia QA' not in page and 'identidad ficticia' not in page and 'Proyecto de grado' not in page)
check('Cookie scoped to installation','path=/cancha-abierta/public/' in headers.get('Set-Cookie','') and 'HttpOnly' in headers.get('Set-Cookie',''))
for asset in ['css/styles.css','css/experience.css','js/app.js','img/chevron.svg']:
    check('Asset '+asset,get('public/assets/'+asset)[0]==200)
for path in ['config/local.php','var/keys/health.key','database/instalar_xampp.sql','app/Core/bootstrap.php','scripts/create-admin.php','docs/instalacion-xampp.md','.htaccess','README.md','public/../config/local.php','public/%2e%2e/config/local.php']:
    check('Internal path denied '+path,get(path)[0] in [400,403,404])
status,page,_=get('public/index.php?r=mi-ficha')
check('Private profile requires login',status==200 and 'id="password"' in page and 'PANORAMA DEPORTIVO' not in page)
transfer=json.loads(subprocess.check_output([r'C:\xampp\php\php.exe',str(root/'var/verify-xampp.php')]))
(root/'docs/evidencias/v3/xampp.json').write_text(json.dumps({'date':datetime.datetime.now(datetime.timezone.utc).isoformat(),'http':results,'transfer':transfer},ensure_ascii=False,indent=2),encoding='utf-8')
print(f'{len(results)} comprobaciones Apache aprobadas; {len(transfer["tables"])} tablas idénticas y clave original conservada.')
