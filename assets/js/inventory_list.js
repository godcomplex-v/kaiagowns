'use strict';
(function(){
var BASE=typeof APP_URL!=='undefined'?APP_URL:'';
function toast(m,t){var el=document.getElementById('toast');if(!el)return;el.textContent=m;el.className='toast toast--visible toast--'+(t||'success');clearTimeout(el._t);el._t=setTimeout(function(){el.className='toast';},3500);}
function post(url,data){return fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data).toString()}).then(function(r){return r.json();});}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
var qs=new URLSearchParams(window.location.search);
if(qs.get('added'))toast('Item added to inventory.','success');
if(qs.get('edited'))toast('Item updated.','success');
var cm=document.getElementById('catModal'),cf=document.getElementById('catForm'),cid=document.getElementById('catId'),cn=document.getElementById('catName'),cerr=document.getElementById('catError'),scb=document.getElementById('saveCatBtn'),cl=document.getElementById('catList'),ecid=null;
document.getElementById('manageCatsBtn')&&document.getElementById('manageCatsBtn').addEventListener('click',function(){cm.hidden=false;});
document.getElementById('closeCatModal')&&document.getElementById('closeCatModal').addEventListener('click',function(){cm.hidden=true;});
cm&&cm.addEventListener('click',function(e){if(e.target===cm||e.target.classList.contains('modal__backdrop'))cm.hidden=true;});
function rcf(){cf.reset();cid.value='';cerr.textContent='';scb.textContent='Add';document.getElementById('cancelCatEdit').hidden=true;ecid=null;}
document.getElementById('cancelCatEdit')&&document.getElementById('cancelCatEdit').addEventListener('click',rcf);
cf&&cf.addEventListener('submit',function(e){e.preventDefault();cerr.textContent='';var nm=cn.value.trim();if(!nm){cerr.textContent='Category name is required.';return;}scb.disabled=true;scb.textContent='…';
    post(BASE+'/api/inventory/save_category.php',{id:cid.value||'0',name:nm}).then(function(d){scb.disabled=false;scb.textContent=ecid?'Save':'Add';
        if(d.success){if(d.action==='created'){var emp=document.getElementById('catEmpty');if(emp)emp.remove();cl.appendChild(buildCat(d.id,d.name));}else{var li=document.getElementById('cat-item-'+d.id);if(li){li.querySelector('span').textContent=d.name;li.querySelector('.edit-cat').dataset.name=d.name;}}rcf();toast('Category saved.','success');}
        else if(d.errors&&d.errors.name){cerr.textContent=d.errors.name;}else{cerr.textContent=d.message||'Error.';}
    }).catch(function(){scb.disabled=false;scb.textContent=ecid?'Save':'Add';cerr.textContent='Network error.';});
});
function buildCat(id,name){var li=document.createElement('li');li.className='cat-item';li.id='cat-item-'+id;li.innerHTML='<span>'+esc(name)+'</span><div class="cat-actions"><button class="btn btn--sm btn--ghost edit-cat" data-id="'+id+'" data-name="'+esc(name)+'">Edit</button><button class="btn btn--sm btn--danger delete-cat" data-id="'+id+'" data-name="'+esc(name)+'">Delete</button></div>';return li;}
cl&&cl.addEventListener('click',function(e){
    var eb=e.target.closest('.edit-cat');if(eb){ecid=eb.dataset.id;cid.value=ecid;cn.value=eb.dataset.name;scb.textContent='Save';document.getElementById('cancelCatEdit').hidden=false;cn.focus();return;}
    var db=e.target.closest('.delete-cat');if(db&&confirm('Delete category "'+db.dataset.name+'"?')){post(BASE+'/api/inventory/delete_category.php',{id:db.dataset.id}).then(function(d){if(d.success){document.getElementById('cat-item-'+db.dataset.id)&&document.getElementById('cat-item-'+db.dataset.id).remove();if(!cl.querySelector('.cat-item')){var li=document.createElement('li');li.className='cat-empty';li.id='catEmpty';li.textContent='No categories yet.';cl.appendChild(li);}toast('Category deleted.','success');}else{toast(d.message||'Error.','error');}});}
});
var sm=document.getElementById('stockModal'),sf=document.getElementById('stockForm'),siid=document.getElementById('stockItemId'),sh=document.getElementById('stockHistory'),ssb=document.getElementById('saveStockBtn');
function openStock(id,name,stock){document.getElementById('stockItemName').textContent=name;document.getElementById('stockCurrentVal').textContent=stock;siid.value=id;sf.reset();document.getElementById('stockChangeError').textContent='';document.getElementById('stockReasonError').textContent='';sh.innerHTML='<p class="history-loading">Loading…</p>';sm.hidden=false;loadHist(id);}
function closeStock(){sm.hidden=true;}
document.getElementById('closeStockModal')&&document.getElementById('closeStockModal').addEventListener('click',closeStock);
document.getElementById('cancelStock')&&document.getElementById('cancelStock').addEventListener('click',closeStock);
sm&&sm.addEventListener('click',function(e){if(e.target===sm||e.target.classList.contains('modal__backdrop'))closeStock();});
function loadHist(id){fetch(BASE+'/api/inventory/get_history.php?item_id='+encodeURIComponent(id)).then(function(r){return r.json();}).then(function(d){if(!d.success||!d.history.length){sh.innerHTML='<p class="history-loading">No history yet.</p>';return;}sh.innerHTML=d.history.map(function(h){var s=h.change>0?'+':'',c=h.change>0?'positive':'negative',dt=new Date(h.created_at).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'});return '<div class="history-row"><span class="history-change '+c+'">'+s+h.change+'</span><div class="history-meta"><span class="history-reason">'+esc(h.reason)+'</span><span class="history-staff">'+esc(h.staff_name||'System')+'</span></div><span class="history-date">'+dt+'</span></div>';}).join('');}).catch(function(){sh.innerHTML='<p class="history-loading">Failed.</p>';});}
sf&&sf.addEventListener('submit',function(e){e.preventDefault();var ce=document.getElementById('stockChange'),re=document.getElementById('stockReason');var v=true;document.getElementById('stockChangeError').textContent='';document.getElementById('stockReasonError').textContent='';if(!ce.value||ce.value==='0'){document.getElementById('stockChangeError').textContent='Enter a non-zero adjustment.';v=false;}if(!re.value.trim()){document.getElementById('stockReasonError').textContent='Reason is required.';v=false;}if(!v)return;ssb.disabled=true;ssb.textContent='…';
    post(BASE+'/api/inventory/adjust_stock.php',{item_id:siid.value,change:ce.value,reason:re.value.trim()}).then(function(d){ssb.disabled=false;ssb.textContent='Apply';if(d.success){var row=document.querySelector('[data-id="'+siid.value+'"].adjust-stock');if(row){row.dataset.stock=d.new_stock;var b=row.closest('tr').querySelector('.stock-badge');if(b){b.textContent=d.new_stock;b.classList.toggle('stock-badge--zero',d.new_stock===0);}}document.getElementById('stockCurrentVal').textContent=d.new_stock;sf.reset();loadHist(siid.value);toast('Stock adjusted.','success');}else if(d.errors){if(d.errors.change)document.getElementById('stockChangeError').textContent=d.errors.change;if(d.errors.reason)document.getElementById('stockReasonError').textContent=d.errors.reason;}else{toast(d.message||'Error.','error');}}).catch(function(){ssb.disabled=false;ssb.textContent='Apply';toast('Network error.','error');});
});
var dim=document.getElementById('deleteItemModal');var pdid=null;
function closeDIM(){dim.hidden=true;pdid=null;}
document.getElementById('closeDeleteItemModal')&&document.getElementById('closeDeleteItemModal').addEventListener('click',closeDIM);
document.getElementById('cancelDeleteItem')&&document.getElementById('cancelDeleteItem').addEventListener('click',closeDIM);
dim&&dim.addEventListener('click',function(e){if(e.target===dim||e.target.classList.contains('modal__backdrop'))closeDIM();});
document.getElementById('confirmDeleteItem')&&document.getElementById('confirmDeleteItem').addEventListener('click',function(){if(!pdid)return;this.disabled=true;this.textContent='…';
    post(BASE+'/api/inventory/delete_item.php',{id:pdid}).then(function(d){this.disabled=false;this.textContent='Yes, Remove';if(d.success){document.getElementById('item-row-'+pdid)&&document.getElementById('item-row-'+pdid).remove();closeDIM();toast('Item removed.','success');}else{toast(d.message||'Error.','error');}}.bind(this)).catch(function(){this.disabled=false;this.textContent='Yes, Remove';toast('Network error.','error');}.bind(this));
});
var it=document.getElementById('invTable');it&&it.addEventListener('click',function(e){var sb=e.target.closest('.adjust-stock');if(sb){openStock(sb.dataset.id,sb.dataset.name,sb.dataset.stock);return;}var db=e.target.closest('.delete-item');if(db){pdid=db.dataset.id;document.getElementById('deleteItemName').textContent=db.dataset.name;dim.hidden=false;}});
})();
