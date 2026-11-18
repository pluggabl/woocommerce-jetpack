(function () {
 'use strict';
 const cfg=window.wcjDocumentWorkspace;if(!cfg)return;
 const states=[];
 document.querySelectorAll('.wcj-document-label').forEach(form=>{
  const input=form.querySelector('input[name=value]'),save=form.querySelector('button[type=submit]'),restore=form.querySelector('.wcj-document-restore'),reload=form.querySelector('.wcj-document-reload'),status=form.querySelector('.wcj-document-status'),saved=form.querySelector('.wcj-document-saved'),example=form.querySelector('.wcj-document-example');
  const s={form,input,base:null,busy:false,parked:true,dirty:false};states.push(s);
  const sample=()=>{example.textContent=input.value||'Blank wording (what appears depends on this setting)';};
  const buttons=()=>{input.disabled=s.busy||!s.base||!s.base.editable;save.disabled=s.busy||s.parked||!s.base||!s.base.editable;restore.disabled=s.busy||s.parked||s.dirty||!s.base||!s.base.restore;reload.disabled=s.busy;};
  async function request(op){
   if(s.busy)return;if(op==='load'&&s.dirty&&!window.confirm('Discard this unsaved wording and load the current value?'))return;
   const data=new URLSearchParams({action:'wcj_document_label_'+op,nonce:cfg.nonces[op],type:cfg.type,field:form.dataset.field});
   if(op!=='load'){if(!s.base||s.parked)return;data.set('guard',s.base.guard);if(op==='save')data.set('value',input.value);else data.set('receipt',s.base.receipt_token);}
   s.busy=true;buttons();status.textContent=op==='load'?'Loading current wording...':(op==='save'?'Saving this wording...':'Restoring the previous value...');
   try{
    const response=await fetch(cfg.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:data.toString()});const result=await response.json();
    if(!response.ok||!result.success||!result.data)throw new Error(result.data&&result.data.message?result.data.message:'The saved wording could not be confirmed. Reload before trying again.');
    const v=result.data;if(v.type!==cfg.type||v.field!==form.dataset.field||typeof v.guard!=='string'||typeof v.value!=='string')throw new Error('The saved wording could not be confirmed. Your draft is kept. Reload before trying again.');
    s.base=v;s.parked=false;s.dirty=false;input.value=v.value;saved.textContent='Saved value: '+v.saved;sample();
    status.textContent=v.reason||(op==='save'?'Saved. Check the wording where it appears before relying on it for customers.':op==='restore'?'Previous wording restored. Existing PDF files, document numbers and sent emails were not changed.':(v.restore?'Current wording loaded. You can restore your most recent eligible save of this field.':'Current wording loaded.'));
   }catch(e){s.parked=true;status.textContent=e.message||'The saved wording could not be confirmed. Your draft is kept. Reload before trying again.';}
   finally{s.busy=false;buttons();}
  }
  input.addEventListener('input',()=>{s.dirty=!s.base||input.value!==s.base.value;sample();buttons();});
  form.addEventListener('submit',e=>{e.preventDefault();request('save');});restore.addEventListener('click',()=>request('restore'));reload.addEventListener('click',()=>request('load'));request('load');
 });
 window.addEventListener('beforeunload',e=>{if(states.some(s=>s.dirty||s.busy)){e.preventDefault();e.returnValue='';}});
})();
