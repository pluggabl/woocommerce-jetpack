(function () {
 'use strict';
 const cfg=window.wcjSpecializedSellingWorkspace;if(!cfg)return;
 const states=[];
 document.querySelectorAll('.wcj-booking-label').forEach(form=>{
  const input=form.querySelector('input[name=value]'),save=form.querySelector('button[type=submit]'),restore=form.querySelector('.wcj-selling-restore'),reload=form.querySelector('.wcj-selling-reload'),status=form.querySelector('.wcj-selling-status'),saved=form.querySelector('.wcj-selling-saved'),example=form.querySelector('.wcj-selling-example');
  const s={form,input,base:null,busy:false,parked:true,dirty:false};states.push(s);
  const sample=()=>{
   if(!s.base){example.textContent='';return;}
   if(!s.dirty&&!s.base.exists){example.textContent='Date from [ __ / __ / ____ ] '+"No label is saved. This example uses the default English label; your site language or filters may change it.";return;}
   if(!s.dirty&&!s.base.editable&&s.base.value===''&&s.base.saved==='Unsupported saved label retained in full settings'){example.textContent="Example unavailable for this saved value. Use full booking settings.";return;}
   if(!s.dirty&&s.base.exists&&input.value===''){example.textContent="[ __ / __ / ____ ] (A blank label is saved; this example does not add the default.)";return;}
   example.textContent=(input.value||'(enter a label)')+' [ __ / __ / ____ ]';
  };
  const buttons=()=>{input.disabled=s.busy||!s.base||!s.base.editable;save.disabled=s.busy||s.parked||!s.base||!s.base.editable;restore.disabled=s.busy||s.parked||s.dirty||!s.base||!s.base.restore;const why=s.busy?'Wait for the current request to finish.':(s.parked||!s.base?'Reload the saved label before using Restore.':(s.dirty?'Save or discard your draft before using Restore.':(!s.base.restore?"Restore needs your latest eligible save here from the last seven days, with the label and related settings still matching. Save or discard any draft first.":'')));restore.title=why;restore.setAttribute('aria-label','Restore previous'+(why?'. '+why:''));reload.disabled=s.busy;};
  async function request(op){
   if(s.busy)return;if(op==='load'&&s.dirty&&!window.confirm('Discard this unsaved label and load the current saved value?'))return;
   const data=new URLSearchParams({action:'wcj_booking_label_'+op,nonce:cfg.nonces[op],type:cfg.type,field:form.dataset.field});
   if(op!=='load'){if(!s.base||s.parked)return;data.set('guard',s.base.guard);if(op==='save')data.set('value',input.value);else data.set('receipt',s.base.receipt_token);}
   s.busy=true;buttons();status.textContent=op==='load'?'Loading saved label...':(op==='save'?'Saving this label...':'Restoring the previous label...');
   try{
    const response=await fetch(cfg.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:data.toString()});const result=await response.json();
    if(!response.ok||!result.success||!result.data)throw new Error(result.data&&result.data.message?result.data.message:'The saved label could not be confirmed. Reload before trying again.');
    const v=result.data;if(v.type!==cfg.type||v.field!==form.dataset.field||v.key!=='wcj_product_bookings_label_date_from'||typeof v.guard!=='string'||typeof v.value!=='string'||typeof v.saved!=='string'||typeof v.exists!=='boolean')throw new Error('The saved label could not be confirmed. Your draft is kept. Reload before trying again.');
    s.base=v;s.parked=false;s.dirty=false;input.value=v.value;saved.textContent='Saved value: '+v.saved;sample();
    status.textContent=v.reason||(op==='save'?'Label saved. No product or booking was enabled.':op==='restore'?'Previous label restored. Other settings and past orders were not changed.':(v.restore?'Saved label loaded. Your latest eligible save can be restored.':'Saved label loaded.'));
   }catch(e){s.parked=true;status.textContent=e.message||'The saved label could not be confirmed. Your draft is kept. Reload before trying again.';}
   finally{s.busy=false;buttons();}
  }
  input.addEventListener('input',()=>{s.dirty=!s.base||input.value!==s.base.value;if(!s.parked){status.textContent=s.dirty?'Draft not saved yet.':(s.base&&s.base.reason?s.base.reason:'Saved label unchanged.');}sample();buttons();});
  form.addEventListener('submit',e=>{e.preventDefault();request('save');});restore.addEventListener('click',()=>request('restore'));reload.addEventListener('click',()=>request('load'));request('load');
 });
 window.addEventListener('beforeunload',e=>{if(states.some(s=>s.dirty||s.busy)){e.preventDefault();e.returnValue='';}});
})();
