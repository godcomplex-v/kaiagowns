'use strict';
(function(){
    var form=document.getElementById('loginForm');
    if(form){
        var ei=document.getElementById('email'),pi=document.getElementById('password'),ee=document.getElementById('emailError'),pe=document.getElementById('passwordError');
        ei&&ei.addEventListener('input',function(){ee.textContent='';});
        pi&&pi.addEventListener('input',function(){pe.textContent='';});
        form.addEventListener('submit',function(e){var v=true;if(!ei.value.trim()){ee.textContent='Email is required.';v=false;}else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(ei.value.trim())){ee.textContent='Enter a valid email.';v=false;}if(!pi.value){pe.textContent='Password is required.';v=false;}if(!v)e.preventDefault();});
    }
    document.querySelectorAll('.toggle-pw').forEach(function(btn){btn.addEventListener('click',function(){var t=document.getElementById(btn.dataset.target);if(!t)return;var h=t.type==='password';t.type=h?'text':'password';btn.textContent=h?'🙈':'👁';});});
})();
(function(){
    var rf=document.getElementById('registerForm');if(!rf)return;
    var ni=document.getElementById('name'),ei=document.getElementById('email'),pi=document.getElementById('password'),ci=document.getElementById('password_confirm');
    var ps=document.getElementById('pwStrength'),pf=document.getElementById('pwFill'),pl=document.getElementById('pwLabel');
    function score(pw){var s=0;if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;return s;}
    var lvls=[{c:'weak',l:'Weak',p:'25%'},{c:'weak',l:'Weak',p:'25%'},{c:'fair',l:'Fair',p:'50%'},{c:'good',l:'Good',p:'75%'},{c:'strong',l:'Strong',p:'100%'},{c:'strong',l:'Very Strong',p:'100%'}];
    pi&&pi.addEventListener('input',function(){var v=this.value,s=score(v),lv=lvls[Math.min(s,5)];if(v.length>0){ps.hidden=false;pf.style.width=lv.p;pf.className='pw-strength-fill '+lv.c;pl.textContent=lv.l;pl.className='pw-strength-label '+lv.c;}else{ps.hidden=true;}rules(v,ci?ci.value:'');});
    ci&&ci.addEventListener('input',function(){rules(pi?pi.value:'',this.value);});
    function rules(pw,conf){sr('rule-len',pw.length>=8);sr('rule-let',/[A-Za-z]/.test(pw));sr('rule-num',/[0-9]/.test(pw));sr('rule-match',pw.length>0&&pw===conf);}
    function sr(id,pass){var el=document.getElementById(id);if(el)el.className='pw-rule'+(pass?' pass':'');}
    function se(id,msg){var el=document.getElementById(id);if(el)el.textContent=msg;}
    function ce(id){se(id,'');}
    ni&&ni.addEventListener('input',function(){ce('nameError');});
    ei&&ei.addEventListener('input',function(){ce('emailError');});
    pi&&pi.addEventListener('input',function(){ce('passwordError');});
    ci&&ci.addEventListener('input',function(){ce('confirmError');});
    rf.addEventListener('submit',function(e){var v=true;
        var name=ni?ni.value.trim():'',email=ei?ei.value.trim():'',pw=pi?pi.value:'',conf=ci?ci.value:'';
        ce('nameError');ce('emailError');ce('passwordError');ce('confirmError');
        if(!name){se('nameError','Full name is required.');v=false;}else if(name.length<2){se('nameError','At least 2 characters.');v=false;}
        if(!email){se('emailError','Email is required.');v=false;}else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){se('emailError','Enter a valid email.');v=false;}
        if(!pw){se('passwordError','Password is required.');v=false;}else if(pw.length<8){se('passwordError','At least 8 characters.');v=false;}else if(!/[A-Za-z]/.test(pw)||!/[0-9]/.test(pw)){se('passwordError','Must contain letters and numbers.');v=false;}
        if(pw&&!conf){se('confirmError','Please confirm your password.');v=false;}else if(pw&&pw!==conf){se('confirmError','Passwords do not match.');v=false;}
        if(!v)e.preventDefault();
    });
})();
