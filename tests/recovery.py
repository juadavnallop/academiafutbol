"""Respaldo/restauración real con cliente MySQL; solo bases desechables *_test.
Para credenciales, proporcionar --defaults-file con archivo privado de opciones.
No elimina ni sobrescribe bases. La restauración se crea con nombre único.
"""
import argparse
import datetime
import json
import pathlib
import re
import subprocess
import uuid
p = argparse.ArgumentParser()
p.add_argument('--mysql', required=True)
p.add_argument('--dump', required=True)
p.add_argument('--source', required=True)
p.add_argument('--port', default='3306')
p.add_argument('--user', default='root')
p.add_argument('--defaults-file')
a = p.parse_args()
if not re.fullmatch('[a-zA-Z0-9_]+_test', a.source):
    raise SystemExit('La base origen debe terminar en _test y contener solo letras, números y guiones bajos.')
root = pathlib.Path(__file__).resolve().parent.parent
options = ['--defaults-extra-file=' + str(pathlib.Path(a.defaults_file).resolve())] if a.defaults_file else ['--no-defaults']
options += ['--host=127.0.0.1', '--port=' + a.port, '--user=' + a.user, '--default-character-set=utf8mb4']
def query(sql):
    return subprocess.check_output([a.mysql, *options, '-N', '-B', '-e', sql], text=True, encoding='utf-8').strip()
target = 'restore_' + uuid.uuid4().hex[:10] + '_test'
(root/'var').mkdir(exist_ok=True)
backup = root/'var'/('backup_' + target + '.sql')
subprocess.run([a.dump, *options, '--single-transaction', '--no-tablespaces', '--result-file=' + str(backup), a.source], check=True)
query('CREATE DATABASE ' + target + ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')
with backup.open('rb') as data:
    subprocess.run([a.mysql, *options, target], stdin=data, check=True)
results = []
tables = query(f'SHOW TABLES FROM {a.source}').splitlines()
for table in tables:
    if not re.fullmatch('[a-zA-Z0-9_]+', table): raise SystemExit('Nombre de tabla no admitido')
    count = query(f'SELECT COUNT(*) FROM {a.source}.{table}')
    other_count = query(f'SELECT COUNT(*) FROM {target}.{table}')
    checksum = query(f'CHECKSUM TABLE {a.source}.{table}').split('\t')[-1]
    other_checksum = query(f'CHECKSUM TABLE {target}.{table}').split('\t')[-1]
    passed = count == other_count and checksum == other_checksum and checksum != 'NULL'
    results.append({'table':table,'rows':int(count),'passed':passed})
    print(('PASS ' if passed else 'FAIL ') + table + ' filas y checksum')
record = {'date':datetime.datetime.now(datetime.timezone.utc).isoformat(),'server':query('SELECT VERSION()'),'source':a.source,'restored_to':target,'results':results}
(root/'tests/results').mkdir(exist_ok=True)
(root/'tests/results/recovery.json').write_text(json.dumps(record, indent=2), encoding='utf-8')
if not all(r['passed'] for r in results): raise SystemExit(1)
print('Restauración verificada. Conservada en ' + target + '; respaldo privado en var/.')
