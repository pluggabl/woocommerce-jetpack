/* Direct wording edits only. A reload never silently rebases a parked draft. */
(function () {
 'use strict';
 const {__}=wp.i18n;
 const root=document.getElementById('wcj-order-notes'); if(!root||!window.wcjOrderNotes)return;
 const $=id=>document.getElementById('wcj-on-'+id), fields=['label','placeholder','description'];
 let current=null,busy=false,stale=false,uncertain=false;
 const draft=()=>Object.fromEntries(fields.map(k=>[k,$(k).value]));
 const dirty=()=>current&&fields.some(k=>$(k).value!==current.draft[k]);
 const plain=v=>new TextEncoder().encode(v).length<=500&&!/[\x00-\x1f\x7f<>\[\]]/u.test(v)&&(v===''||/[^\s\p{Z}\p{Cf}]/u.test(v));
 const message=(s,error=false)=>{$('message').textContent=s;$('message').dataset.error=String(error);};
 const display=s=>!s.exists?__('Incoming wording (option absent)', 'woocommerce-jetpack'):s.raw===''?__('Incoming wording (empty override)', 'woocommerce-jetpack'):s.raw;
 function render(){
  const changed=!!dirty(),invalid=fields.some(k=>!plain($(k).value)),locked=current&&current.locked;
  $('fields').disabled=busy||!current||!!locked;
  $('save').disabled=busy||!current||!!locked||stale||uncertain||invalid||!changed;
  $('save').hidden=!!current&&!changed;
  $('reload').disabled=busy;$('discard').hidden=!changed&&!stale&&!uncertain;$('discard').disabled=busy;
  $('action-reason').textContent=!current?__('Load current settings before editing.', 'woocommerce-jetpack'):locked||((stale||uncertain)?__('Your draft is protected. Copy it if needed, then discard it and load current values.', 'woocommerce-jetpack'):invalid?__('Use visible plain single-line text, up to 500 bytes, or leave the whole value blank.', 'woocommerce-jetpack'):changed?__('Unsaved wording. No requiredness, visibility or activation change.', 'woocommerce-jetpack'):__('No unsaved changes.', 'woocommerce-jetpack'));
  $('sample-label').textContent=$('label').value||__('Incoming label (sample)', 'woocommerce-jetpack');$('sample-input').placeholder=$('placeholder').value||__('Incoming placeholder (sample)', 'woocommerce-jetpack');$('sample-description').textContent=$('description').value||__('Incoming description, if any (sample)', 'woocommerce-jetpack');
  $('raw-list').replaceChildren(); if(current)fields.forEach(k=>{const row=document.createElement('li'),r=current.raw[k];row.textContent='wcj_checkout_fields_order_comments_'+k+': '+(r.exists?__('Stored value', 'woocommerce-jetpack'):__('Option absent', 'woocommerce-jetpack'))+'; '+r.raw+(r.truncated?' ('+__('display truncated; native value unchanged', 'woocommerce-jetpack')+')':'');$('raw-list').append(row);});
  const r=current&&current.record;$('receipt').hidden=!r||changed;
  if(r){
   $('record-status').textContent=(r.status==='undone'?__('Previous native wording was restored.', 'woocommerce-jetpack'):__('Your last wording change was saved and read back. This is not a live checkout result.', 'woocommerce-jetpack'))+' '+configurationStatus(current);
   $('change-list').replaceChildren();fields.forEach(k=>{const a=r.before[k],b=r.after[k];if(a.exists!==b.exists||a.raw!==b.raw){const li=document.createElement('li');li.textContent=({label:__('Label', 'woocommerce-jetpack'),placeholder:__('Placeholder', 'woocommerce-jetpack'),description:__('Description', 'woocommerce-jetpack')})[k]+': '+display(a)+' → '+display(b);$('change-list').append(li);}});
   $('undo').disabled=busy||changed||stale||uncertain||!!locked||!current.undo;$('undo').hidden=r.status!=='applied';
   $('undo-reason').textContent=r.status==='undone'?__('Restored exact native absence or stored values.', 'woocommerce-jetpack'):current.undo?__('Undo restores only these three matching wording options.', 'woocommerce-jetpack'):__('Undo is unavailable: your saved change no longer matches the relevant current settings.', 'woocommerce-jetpack');
   if(!current.undo&&r.status==='applied')$('changes').open=true;
  }
 }
 function configurationStatus(c){
  const module=c.module==='no'?__('Not active: the Core Fields module is off, so Booster is not applying this saved wording. Saving does not turn it on. Review Full Core Fields settings if you intend to use it.', 'woocommerce-jetpack'):c.module==='yes'?__('Core Fields module on.', 'woocommerce-jetpack'):__('Activation is uncertain: Core Fields has a nonstandard module state. Check Full Core Fields settings before relying on this wording.', 'woocommerce-jetpack');
  const field=c.field==='no'?__('Order notes is disabled in Core Fields settings. Wording alone does not recreate the field.', 'woocommerce-jetpack'):c.field==='yes'?__('Order notes is enabled in Core Fields settings; other rules can still affect its presence at checkout.', 'woocommerce-jetpack'):c.field==='default'?__('Order notes uses the incoming WooCommerce field setting; this does not prove it is present at checkout.', 'woocommerce-jetpack'):__('Order notes has a nonstandard field setting. Check Full Core Fields settings before relying on its presence.', 'woocommerce-jetpack');
  const conditions=c.conditions==='unknown'?__('Category conditions use a nonstandard value. Check full settings and a real shopper cart.', 'woocommerce-jetpack'):c.conditional?__('Conditional: category rules determine whether Order notes appears for a cart. Saving wording does not change those rules or prove that shoppers see it.', 'woocommerce-jetpack'):'';
  return [module,field,conditions].filter(Boolean).join(' ');
 }
 function contextText(c){
  $('next-check').textContent=c.module!=='yes'?__('Review the module switch in full settings first if you intend to use this wording. This save does not activate it. Check your actual checkout only after resolving the intended configuration.', 'woocommerce-jetpack'):c.field==='no'?__('Order notes is configured disabled. Review that rule in full settings if you intend to show it; wording alone does not restore the field.', 'woocommerce-jetpack'):c.kind==='blocks'?__('Read the guide and check your Checkout Block integration before relying on these Core wording options. A saved override is not verified Blocks output.', 'woocommerce-jetpack'):c.kind!=='classic'?__('Identify the actual checkout context and review full settings first. No supported shopper field has been established by this configuration view.', 'woocommerce-jetpack'):c.conditions==='unknown'?__('Review the nonstandard category condition in full settings, then check an eligible test cart. Do not assume Order notes is currently visible.', 'woocommerce-jetpack'):c.conditional?__('Use a test cart that meets your category rules. Confirm Order notes appears with the intended wording and an answer survives checkout validation.', 'woocommerce-jetpack'):__('Open your actual Classic checkout with a test cart. Confirm Order notes is present, the wording fits and an answer survives checkout validation. Saved configuration alone is not shopper-output proof.', 'woocommerce-jetpack');
  $('context').textContent=configurationStatus(c);
  $('scope').textContent=c.kind==='classic'?__('Configured page: Classic shortcode. Theme and other rules can still alter the actual field.', 'woocommerce-jetpack'):c.kind==='blocks'?__('Configured page: Checkout Block. These native Core wording options are not verified as Blocks wording controls. Save is configuration only; use the guide before relying on it.', 'woocommerce-jetpack'):__('Checkout context is unknown or mixed. The stored wording is not proof of a supported shopper field.', 'woocommerce-jetpack');
 }
 async function request(op,extra={}){
  const body=new URLSearchParams({action:'wcj_order_notes_'+op,op,nonce:wcjOrderNotes.nonces[op],...extra});
  const response=await fetch(wcjOrderNotes.ajax,{method:'POST',credentials:'same-origin',body});let result;
  try{result=await response.json();}catch(e){throw {kind:'uncertain',message:__('The response could not be read. Check current values before another save.', 'woocommerce-jetpack')};}
  if(!response.ok||!result.success)throw result.data||{kind:'uncertain',message:__('The request could not be verified.', 'woocommerce-jetpack')};return result.data;
 }
 function accept(c){current=c;fields.forEach(k=>{$(k).value=c.draft[k];});stale=false;uncertain=false;contextText(c);}
 async function load(discard=false,quiet=false){
  if(busy)return;busy=true;render();
  try{const next=await request('load');
   if(current&&(dirty()||stale||uncertain)&&!discard){
    if(next.guard!==current.guard){stale=true;contextText(next);message(__('Settings changed elsewhere. Your unsaved wording was kept. Discard it to load current values.', 'woocommerce-jetpack'),true);}else if(!quiet)message(__('Saved settings checked. Your draft is still unsaved.', 'woocommerce-jetpack'));
   }else{accept(next);if(!quiet)message(discard?__('Current settings loaded. The previous draft was discarded.', 'woocommerce-jetpack'):__('Current configuration loaded.', 'woocommerce-jetpack'));}
  }catch(e){message(e.message||__('Current settings could not be loaded.', 'woocommerce-jetpack'),true);if(e.kind!=='validation')uncertain=true;}
  finally{busy=false;render();}
 }
 async function mutate(op){
  if(busy||!current||stale||uncertain||current.locked)return;
  if(op==='save'&&(!dirty()||fields.some(k=>!plain($(k).value))))return;
  if(op==='undo'&&(!current.undo||dirty()))return;
  busy=true;render();
  try{const next=await request(op,{guard:current.guard,...(op==='save'?{draft:JSON.stringify(draft())}:{})});accept(next);message(op==='undo'?__('Previous native wording restored and read back.', 'woocommerce-jetpack'):__('Wording saved and read back. No activation or answer changes.', 'woocommerce-jetpack'));}
  catch(e){message(e.message||__('The result could not be verified.', 'woocommerce-jetpack'),true);if(e.kind==='conflict')stale=true;else if(e.kind!=='validation')uncertain=true;}
  finally{busy=false;render();}
 }
 $('form').addEventListener('submit',e=>{e.preventDefault();mutate('save');});fields.forEach(k=>$(k).addEventListener('input',render));
 $('reload').addEventListener('click',()=>load(false));$('discard').addEventListener('click',()=>load(true));$('undo').addEventListener('click',()=>mutate('undo'));
 window.addEventListener('focus',()=>{if(current)load(false,true);});window.addEventListener('beforeunload',e=>{if(dirty()){e.preventDefault();e.returnValue='';}});
 render();load();
}());
