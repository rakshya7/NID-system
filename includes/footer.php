</main><footer><div class="wrap">Case-study prototype · Not connected to the official NID system · <?=t('site')?></div></footer>
<script>
const D=<?=json_encode($DIST??[])?>;
document.querySelectorAll('[data-if]').forEach(w=>{const [k,v]=w.dataset.if.split('=');const u=()=>{const i=document.querySelector('[name="'+k+'"]:checked')||document.querySelector('select[name="'+k+'"]');w.hidden=!(i&&i.value===v)};document.querySelectorAll('[name="'+k+'"]').forEach(i=>i.addEventListener('change',u));u()});
document.querySelectorAll('[data-dist]').forEach(p=>p.addEventListener('change',()=>{const t=document.querySelector('[name="'+p.dataset.dist+'"]');t.innerHTML='<option value="">—</option>'+(D[p.value]||[]).map(x=>'<option>'+x+'</option>').join('')}));
</script></body></html>
