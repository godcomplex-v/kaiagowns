'use strict';
(function(){
    var BASE=typeof APP_URL!=='undefined'?APP_URL:'';
    function toast(m,t){var el=document.getElementById('toast');if(!el)return;el.textContent=m;el.className='toast toast--visible toast--'+(t||'success');clearTimeout(el._t);el._t=setTimeout(function(){el.className='toast';},3500);}
    var tbl=document.getElementById('customerTable');if(!tbl)return;
    tbl.addEventListener('click',function(e){
        var btn=e.target.closest('.toggle-status');if(!btn)return;
        var id=btn.dataset.id,action=btn.dataset.action;btn.disabled=true;btn.textContent='…';
        fetch(BASE+'/api/users/toggle_status.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id)+'&action='+encodeURIComponent(action)})
        .then(function(r){return r.json();}).then(function(d){
            if(d.success){var row=document.getElementById('customer-row-'+id);var tmp=document.createElement('tbody');tmp.innerHTML=d.html;row.replaceWith(tmp.firstElementChild);toast('Customer status updated.','success');}
            else{toast(d.message||'Error.','error');btn.disabled=false;btn.textContent=action==='activate'?'Activate':'Deactivate';}
        }).catch(function(){toast('Network error.','error');btn.disabled=false;btn.textContent=action==='activate'?'Activate':'Deactivate';});
    });
})();
