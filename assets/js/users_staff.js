'use strict';
(function(){
    var BASE=typeof APP_URL!=='undefined'?APP_URL:'';
    function toast(m,t){var el=document.getElementById('toast');if(!el)return;el.textContent=m;el.className='toast toast--visible toast--'+(t||'success');clearTimeout(el._t);el._t=setTimeout(function(){el.className='toast';},3500);}
    function se(id,m){var el=document.getElementById(id);if(el)el.textContent=m;}
    function ce(){['nameError','emailError','passwordError','confirmError'].forEach(function(id){se(id,'');});}
    var modal=document.getElementById('staffModal'),dmodal=document.getElementById('deleteModal'),form=document.getElementById('staffForm'),saveBtn=document.getElementById('saveStaffBtn'),tbl=document.getElementById('staffTable');
    var pendId=null,pendName=null;
    document.getElementById('addStaffBtn')&&document.getElementById('addStaffBtn').addEventListener('click',function(){form.reset();ce();document.getElementById('staffId').value='';document.getElementById('modalTitle').textContent='Add Staff Member';document.getElementById('pwRequired').hidden=false;document.getElementById('cpwRequired').hidden=false;document.getElementById('pwHint').hidden=true;modal.hidden=false;});
    function csm(){modal.hidden=true;}function cdm(){dmodal.hidden=true;}
    ['closeModal','cancelModal'].forEach(function(id){document.getElementById(id)&&document.getElementById(id).addEventListener('click',csm);});
    ['closeDeleteModal','cancelDelete'].forEach(function(id){document.getElementById(id)&&document.getElementById(id).addEventListener('click',cdm);});
    modal&&modal.addEventListener('click',function(e){if(e.target===modal||e.target.classList.contains('modal__backdrop'))csm();});
    dmodal&&dmodal.addEventListener('click',function(e){if(e.target===dmodal||e.target.classList.contains('modal__backdrop'))cdm();});
    tbl&&tbl.addEventListener('click',function(e){
        var eb=e.target.closest('.edit-staff');
        if(eb){form.reset();ce();var d=eb.dataset;document.getElementById('staffId').value=d.id;document.getElementById('staffName').value=d.name;document.getElementById('staffEmail').value=d.email;document.getElementById('staffPhone').value=d.phone;document.getElementById('staffStatus').value=d.status;document.getElementById('modalTitle').textContent='Edit Staff Member';document.getElementById('pwRequired').hidden=true;document.getElementById('cpwRequired').hidden=true;document.getElementById('pwHint').hidden=false;modal.hidden=false;return;}
        var db2=e.target.closest('.delete-staff');if(db2){pendId=db2.dataset.id;pendName=db2.dataset.name;document.getElementById('deleteStaffName').textContent=pendName;dmodal.hidden=false;}
    });
    function validate(){ce();var v=true,ie=document.getElementById('staffId').value!=='',name=document.getElementById('staffName').value.trim(),email=document.getElementById('staffEmail').value.trim(),pw=document.getElementById('staffPassword').value,cpw=document.getElementById('staffPasswordConfirm').value;
        if(!name){se('nameError','Full name is required.');v=false;}
        if(!email){se('emailError','Email is required.');v=false;}else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){se('emailError','Enter a valid email.');v=false;}
        if(!ie&&!pw){se('passwordError','Password is required.');v=false;}else if(pw&&pw.length<8){se('passwordError','Min 8 characters.');v=false;}else if(pw&&pw!==cpw){se('confirmError','Passwords do not match.');v=false;}
        return v;
    }
    form&&form.addEventListener('submit',function(e){e.preventDefault();if(!validate())return;saveBtn.disabled=true;saveBtn.textContent='Saving…';
        fetch(BASE+'/api/users/save_staff.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(new FormData(form)).toString()})
        .then(function(r){return r.json();}).then(function(d){saveBtn.disabled=false;saveBtn.textContent='Save';
            if(d.success){csm();var tbody=tbl.querySelector('tbody');var tmp=document.createElement('tbody');tmp.innerHTML=d.html;var nr=tmp.firstElementChild;if(d.is_edit){var old=document.getElementById('staff-row-'+d.id);if(old)old.replaceWith(nr);}else{var emp=tbody.querySelector('.table-empty');if(emp)emp.closest('tr').remove();tbody.prepend(nr);}toast(d.message,'success');}
            else if(d.errors){var mp={name:'nameError',email:'emailError',password:'passwordError',confirm:'confirmError'};Object.entries(d.errors).forEach(function(kv){if(mp[kv[0]])se(mp[kv[0]],kv[1]);});}
            else{toast(d.message||'Error.','error');}
        }).catch(function(){saveBtn.disabled=false;saveBtn.textContent='Save';toast('Network error.','error');});
    });
    document.getElementById('confirmDelete')&&document.getElementById('confirmDelete').addEventListener('click',function(){if(!pendId)return;this.disabled=true;this.textContent='Removing…';
        fetch(BASE+'/api/users/delete_staff.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(pendId)})
        .then(function(r){return r.json();}).then(function(d){this.disabled=false;this.textContent='Yes, Remove';if(d.success){document.getElementById('staff-row-'+pendId)&&document.getElementById('staff-row-'+pendId).remove();cdm();toast(d.message,'success');}else{toast(d.message||'Error.','error');}pendId=pendName=null;}.bind(this)).catch(function(){this.disabled=false;this.textContent='Yes, Remove';toast('Network error.','error');}.bind(this));
    });
    document.addEventListener('keydown',function(e){if(e.key==='Escape'){csm();cdm();}});
})();
