'use strict';
(function(){
    var dz=document.getElementById('imgDropZone'),fi=document.getElementById('image'),pv=document.getElementById('imgPreview'),hint=document.getElementById('imgDropHint');
    if(!dz)return;
    function prev(file){if(!file||!file.type.startsWith('image/'))return;var r=new FileReader();r.onload=function(e){pv.src=e.target.result;pv.classList.add('img-preview--loaded');hint.textContent='Click or drop to replace';};r.readAsDataURL(file);}
    dz.addEventListener('click',function(){fi.click();});
    fi.addEventListener('change',function(){if(fi.files[0])prev(fi.files[0]);});
    dz.addEventListener('dragover',function(e){e.preventDefault();dz.classList.add('drag-over');});
    ['dragleave','dragend'].forEach(function(ev){dz.addEventListener(ev,function(){dz.classList.remove('drag-over');});});
    dz.addEventListener('drop',function(e){e.preventDefault();dz.classList.remove('drag-over');var f=e.dataTransfer.files[0];if(f){var dt=new DataTransfer();dt.items.add(f);fi.files=dt.files;prev(f);}});
    var form=document.getElementById('itemForm');
    form&&form.addEventListener('submit',function(e){var v=true;var nm=document.getElementById('name'),cat=document.getElementById('category_id');var ne=nm.closest('.form-group').querySelector('.field-error'),ce=cat.closest('.form-group').querySelector('.field-error');if(!nm.value.trim()){ne.textContent='Item name is required.';v=false;}else{ne.textContent='';}if(!cat.value){ce.textContent='Please select a category.';v=false;}else{ce.textContent='';}if(!v)e.preventDefault();});
})();
