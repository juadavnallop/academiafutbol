'use strict';
const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#main-nav');
const mobile = window.matchMedia('(max-width: 699px)');
function syncMenu() {
  menuButton.hidden = !mobile.matches;
  menuButton.setAttribute('aria-expanded', String(!mobile.matches));
  navigation.dataset.collapsed = String(mobile.matches);
}
if (menuButton && navigation) {
  syncMenu();
  mobile.addEventListener('change', syncMenu);
  menuButton.addEventListener('click', () => {
    const expanded = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!expanded));
    navigation.dataset.collapsed = String(expanded);
  });
  navigation.addEventListener('keydown', event => {
    if (event.key === 'Escape' && mobile.matches) { syncMenu(); menuButton.focus(); }
  });
}
document.querySelectorAll('form[data-submit]').forEach(form => {
  form.addEventListener('submit', event => {
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
    if (form.dataset.sending) { event.preventDefault(); return; }
    form.dataset.sending = 'true';
    const button = form.querySelector('button[type="submit"]');
    if (button) { button.disabled = true; button.textContent = 'Procesando…'; }
  });
});
window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
document.querySelector('#errors')?.focus();
document.querySelectorAll('[data-print]').forEach(button => {
  button.addEventListener('click', () => window.print());
});
