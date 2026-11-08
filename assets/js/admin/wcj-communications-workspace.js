(function () {
 'use strict';
 const cfg=window.wcjCommunicationsWorkspace;if(!cfg)return;
 const states=[];
 document.querySelectorAll('.wcj-communication-subject').forEach(form=>{
  const input=form.querySelector('input[name=value]'),save=form.querySelector('button[type=submit]'),restore=form.querySelector('.wcj-communications-restore'),reload=form.querySelector('.wcj-communications-reload'),status=form.querySelector('.wcj-communications-status'),saved=form.querySelector('.wcj-communications-saved'),example=form.querySelector('.wcj-communications-example');
  const s={form,input,base:null,busy:false,parked:true,dirty:false};states.push(s);
  const sample=()=>{example.textContent='Example Store · '+(input.value||'(enter a subject)');};
  const buttons=()=>{input.disabled=s.busy||!s.base||!s.base.editable;save.disabled=s.busy||s.parked||!s.base||!s.base.editable;restore.disabled=s.busy||s.parked||s.dirty||!s.base||!s.base.restore;reload.disabled=s.busy;};
  async function request(op){
   if(s.busy)return;if(op==='load'&&s.dirty&&!window.confirm('Discard this unsaved subject and load the current saved value?'))return;
   const data=new URLSearchParams({action:'wcj_communication_subject_'+op,nonce:cfg.nonces[op],type:cfg.type,field:form.dataset.field});
   if(op!=='load'){if(!s.base||s.parked)return;data.set('guard',s.base.guard);if(op==='save')data.set('value',input.value);else data.set('receipt',s.base.receipt_token);}
   s.busy=true;buttons();status.textContent=op==='load'?'Loading saved subject...':(op==='save'?'Saving this subject...':'Restoring the previous subject...');
   try{
    const response=await fetch(cfg.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:data.toString()});const result=await response.json();
    if(!response.ok||!result.success||!result.data)throw new Error(result.data&&result.data.message?result.data.message:'The saved subject could not be confirmed. Reload before trying again.');
    const v=result.data;if(v.type!==cfg.type||v.field!==form.dataset.field||v.key!=='wcj_ca_email_template_subject_1'||typeof v.guard!=='string'||typeof v.value!=='string'||typeof v.saved!=='string')throw new Error('The saved subject could not be confirmed. Your draft is kept. Reload before trying again.');
    s.base=v;s.parked=false;s.dirty=false;input.value=v.value;saved.textContent='Saved value: '+v.saved;sample();
    status.textContent=v.reason||(op==='save'?'Subject saved. No message was sent by this task.':op==='restore'?'Previous subject restored. Messages already attempted were not changed.':(v.restore?'Saved subject loaded. Your latest eligible save can be restored.':'Saved subject loaded.'));
   }catch(e){s.parked=true;status.textContent=e.message||'The saved subject could not be confirmed. Your draft is kept. Reload before trying again.';}
   finally{s.busy=false;buttons();}
  }
  input.addEventListener('input',()=>{s.dirty=!s.base||input.value!==s.base.value;sample();buttons();});
  form.addEventListener('submit',e=>{e.preventDefault();request('save');});restore.addEventListener('click',()=>request('restore'));reload.addEventListener('click',()=>request('load'));request('load');
 });
 window.addEventListener('beforeunload',e=>{if(states.some(s=>s.dirty||s.busy)){e.preventDefault();e.returnValue='';}});
})();
