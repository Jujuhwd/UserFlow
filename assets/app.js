(function(){
  const saved=localStorage.getItem('theme')||'light';
  document.documentElement.setAttribute('data-theme', saved);
  const icon=document.getElementById('themeIcon');
  if(icon) icon.textContent = saved === 'dark' ? '☀️' : '🌙';
})();
function toggleTheme(){
  const cur=document.documentElement.getAttribute('data-theme')||'light';
  const next=cur==='dark'?'light':'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  const icon=document.getElementById('themeIcon');
  if(icon) icon.textContent = next === 'dark' ? '☀️' : '🌙';
}
function copyText(id, btn){
  const el=document.getElementById(id);
  if(!el) return;
  const value=el.value || el.textContent || '';
  copyValue(value, btn);
}
function copyValue(value, btn){
  const done=function(){
    if(btn){ const old=btn.textContent; btn.textContent='✅'; setTimeout(()=>btn.textContent=old,1000); }
  };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(value).then(done); }
  else { const t=document.createElement('textarea'); t.value=value; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t); done(); }
}
