// Sin dependencias npm: Chromium DevTools Protocol mediante WebSocket de Node 22+.
// Solo para una instalación local y una base cuyo nombre termine en _test.
import {spawn, execFileSync} from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
const root = process.cwd();
const base = process.env.TEST_URL || 'http://127.0.0.1:8080';
const edge = process.env.EDGE_BIN || 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
const php = process.env.PHP_BIN || 'C:/xampp/php/php.exe';
const publicOnly = process.argv.includes('--public-only');
const members = process.argv.includes('--members');
const memberFixture = members ? JSON.parse(fs.readFileSync('var/members-fixture.json','utf8')) : null;
const admin = publicOnly ? null : JSON.parse(execFileSync(php, ['tests/fixture.php'], {encoding:'utf8'}));
const results = [];
const browser = spawn(edge, ['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--remote-debugging-port=9223',`--user-data-dir=${path.join(root,'var/edge-test')}`,'about:blank'], {stdio:'ignore', windowsHide:true});
let ws;
try {
  let pages;
  for (let i=0;i<80;i++) {
    try { pages = await (await fetch('http://127.0.0.1:9223/json')).json(); if (pages.length) break; } catch {}
    await new Promise(resolve=>setTimeout(resolve,250));
  }
  if (!pages?.length) throw new Error('No se pudo iniciar Edge para pruebas.');
  ws = new WebSocket(pages.find(p=>p.type==='page').webSocketDebuggerUrl);
  await new Promise((resolve,reject)=>{ws.onopen=resolve; ws.onerror=reject;});
  let id=0; const pending=new Map();
  ws.onmessage = event => { const message=JSON.parse(event.data); if(pending.has(message.id)){ const {resolve,reject}=pending.get(message.id); pending.delete(message.id); message.error ? reject(new Error(JSON.stringify(message.error))) : resolve(message.result); } };
  function call(method,params={}) {return new Promise((resolve,reject)=>{const key=++id; pending.set(key,{resolve,reject}); ws.send(JSON.stringify({id:key,method,params}));});}
  async function evaluate(expression){const r=await call('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true}); if(r.exceptionDetails)throw new Error(JSON.stringify(r.exceptionDetails));return r.result.value;}
  async function navigate(route){await call('Page.navigate',{url:base+'/index.php?r='+route});for(let i=0;i<100;i++){await new Promise(r=>setTimeout(r,50));if(await evaluate(`document.readyState === 'complete' && location.href.includes(${JSON.stringify('r='+route)})`))return;}throw new Error('Navigation timeout '+route);}
  function check(name,pass,detail){results.push({case:name,passed:!!pass,detail});console.log((pass?'PASS ':'FAIL ')+name);if(!pass)throw new Error(name);}
  await call('Page.enable');
  for (const width of [360,768,1366]) {
    await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
    for(const route of ['inicio','academia','horarios','noticias','contacto','login',...(members?['registro']:[])]) {
      await navigate(route);
      const metrics=await evaluate(`({width:innerWidth,scroll:document.documentElement.scrollWidth,main:!!document.querySelector('h1'),unlabeled:[...document.querySelectorAll('input:not([type=hidden]),textarea,select')].filter(e=>!e.labels?.length).length})`);
      check(`${width}px ${route}: sin desbordamiento y campos etiquetados`,metrics.scroll<=metrics.width && metrics.main && metrics.unlabeled===0,metrics);
      if(route==='inicio') {const shot=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(`tests/results/inicio-${width}.png`,Buffer.from(shot.data,'base64'));}
    }
  }
  await call('Emulation.setDeviceMetricsOverride',{width:360,height:800,deviceScaleFactor:1,mobile:false});
  await navigate('inicio');
  await call('Input.dispatchKeyEvent',{type:'keyDown',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});
  await call('Input.dispatchKeyEvent',{type:'keyUp',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});
  check('Teclado: enlace de salto enfocable',await evaluate(`document.activeElement.classList.contains('skip')`));
  await evaluate(`document.querySelector('.menu-toggle').click()`);
  check('Menú móvil se expande',await evaluate(`document.querySelector('.menu-toggle').getAttribute('aria-expanded')==='true' && getComputedStyle(document.querySelector('#main-nav')).display!=='none'`));
  await navigate('contacto');
  check('Validación nativa campos vacíos',await evaluate(`!document.querySelector('form[data-submit]').checkValidity()`));
  if (!publicOnly) {
  await navigate('login');
  await evaluate(`document.querySelector('#correo').value=${JSON.stringify(admin.email)};document.querySelector('#password').value=${JSON.stringify(admin.password)};document.querySelector('form[data-submit]').requestSubmit();`);
  for(let i=0;i<60;i++){await new Promise(r=>setTimeout(r,100));if(await evaluate(`location.search==='?r=admin'`))break;}
  check('Login en navegador',await evaluate(`location.search==='?r=admin'`));
  for(const width of [360,768,1366]) {
    await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
    for(const route of ['admin','admin/contenido&mod=horarios','admin/solicitudes',...(members?['equipo','admin/cuentas','admin/configuracion','admin/auditoria',...['resumen','personal','deportiva','evaluaciones','inscripcion','salud'].map(tab=>'equipo/ficha&id='+memberFixture.jugador.player_id+'&tab='+tab)]:[])]) {
      await navigate(route);
      check(`${width}px ${route}: sin desbordamiento global`,await evaluate(`document.documentElement.scrollWidth<=document.documentElement.clientWidth && !!document.querySelector('h1') && !document.body.innerText.includes('temporalmente no disponible')`));
      if(members && route.includes('tab=resumen')){const shot=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});fs.writeFileSync(`tests/results/ficha-${width}.png`,Buffer.from(shot.data,'base64'));}
    }
  }
  if(members) {
    await evaluate(`document.querySelector('form[action*="r=logout"]').requestSubmit()`);
    await new Promise(r=>setTimeout(r,200));
    await navigate('login');
    await evaluate(`document.querySelector('#correo').value=${JSON.stringify(memberFixture.jugador.email)};document.querySelector('#password').value=${JSON.stringify(memberFixture.jugador.password)};document.querySelector('form[data-submit]').requestSubmit();`);
    for(let i=0;i<60;i++){await new Promise(r=>setTimeout(r,100));if(await evaluate(`location.search.includes('r=mi-ficha')`))break;}
    check('Jugador accede a su ficha en navegador',await evaluate(`location.search.includes('r=mi-ficha')`));
    for(const width of [360,768,1366]) {
      await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false});
      for(const tab of ['resumen','personal','deportiva','evaluaciones','inscripcion','salud']) {
        await navigate('mi-ficha&tab='+tab);
        check(`Jugador ${width}px ${tab}`,await evaluate(`document.documentElement.scrollWidth<=document.documentElement.clientWidth && document.querySelector('h1').innerText==='Jugador QA'`));
      }
    }
    await navigate('mi-ficha&tab=resumen');
    await call('Emulation.setEmulatedMedia',{media:'print'});
    check('Impresión conserva perfil deportivo y excluye salud',await evaluate(`getComputedStyle(document.querySelector('.athlete-card')).display!=='none' && !document.body.innerText.includes('CLINICAL_MARKER')`));
    await call('Emulation.setEmulatedMedia',{media:''});
    await navigate('mi-ficha&tab=personal');
    await evaluate(`document.querySelector('#telefono').value='3012223344';document.querySelector('form[data-submit]').requestSubmit();`);
    await new Promise(r=>setTimeout(r,200));
    check('Jugador guarda datos con JavaScript activo',await evaluate(`document.body.innerText.includes('Información guardada correctamente') && document.querySelector('#telefono').value==='3012223344'`));
  }
  }
  const performance = {};
  for(const route of ['inicio','academia','horarios','noticias','contacto']) {
    const samples=[];
    for(let i=0;i<10;i++) {
      await navigate(route);
      samples.push(await evaluate(`performance.getEntriesByType('navigation')[0].loadEventEnd / 1000`));
    }
    const sorted=[...samples].sort((a,b)=>a-b);
    const median=(sorted[4]+sorted[5])/2;
    performance[route]={samples,median};
    check(`Carga de navegador <= 3s ${route}`,median>0 && median<=3);
  }
  fs.writeFileSync(`tests/results/${members?'browser-members':publicOnly ? 'browser-public' : 'browser'}.json`,JSON.stringify({date:new Date().toISOString(),browser:await call('Browser.getVersion'),results,performance_seconds:performance},null,2));
  console.log(`${results.length} comprobaciones aprobadas.`);
  await call('Browser.close');
} finally {
  if(ws?.readyState===WebSocket.OPEN)ws.close();
  browser.kill();
}
