(function () {
 'use strict';
 const cfg=window.wcjAccountOrdersLabelJob, root=document.getElementById('wcj-account-label-job'); if(!cfg||!root)return;
 const el=id=>document.getElementById('wcj-account-label-'+id), input=el('value');
 let state=null, parked=false, busy=false, draft=null;
 try { draft=JSON.parse(sessionStorage.getItem(cfg.storage)||'null'); if(!draft||typeof draft.value!=='string'||typeof draft.guard!=='string')draft=null; } catch(e){draft=null;}
 function remember(){try{if(state && input.value!==state.value)sessionStorage.setItem(cfg.storage,JSON.stringify({value:input.value,guard:state.guard,parked:parked}));else sessionStorage.removeItem(cfg.storage);}catch(e){/* Current draft remains visible even if browser storage is disabled. */}}
 function text(id,value){el(id).textContent=value;}
 function tell(message,error){text('notice',message);el('notice').classList.toggle('wcj-account-label-warning',!!error);}
 function valid(value){
  for(let i=0;i<value.length;i++){const c=value.charCodeAt(i);if(c>=0xd800&&c<=0xdbff){const n=value.charCodeAt(++i);if(!(n>=0xdc00&&n<=0xdfff))return false;}else if(c>=0xdc00&&c<=0xdfff)return false;}
  return value===''||(value!=='0'&&value.trim()===value&&!/ {2}/.test(value)&&new TextEncoder().encode(value).length<=64&&!/^(?:N;|[aObisdCrRE]:)/.test(value)&&!/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}<>\[\]]|&(?:[A-Za-z][A-Za-z0-9]+;|#(?:[0-9]+|[xX][0-9A-Fa-f]+);?)/u.test(value)&&/[^\s\p{Z}\p{Cf}]/u.test(value)&&!/%[a-fA-F0-9]{2}/.test(value));
 }
 function controls(){
  const changed=!!state&&input.value!==state.value;
  input.disabled=busy||!state;
  input.readOnly=!!state&&(!state.editable||parked);
  el('draft').hidden=!changed; text('sample',input.value||'Supplied default wording');text('heading',input.value||'Supplied default wording');
  text('validation',changed&&!valid(input.value)?'Use blank for the supplied default, or visible plain text up to 64 UTF-8 bytes. No markup, shortcodes, encoded entities, control characters, outer spaces or the single digit 0.':'');
  el('save').disabled=busy||parked||!state||!state.editable||!changed||!valid(input.value);
  el('undo').disabled=busy||parked||changed||!state||!state.undo;
  el('reload').disabled=busy;
 }
 function display(s,retained){
  state=s;text('saved',s.saved);text('blocker',s.reason||'');el('blocker').hidden=!s.reason;text('key',s.field);text('raw',s.raw);text('existence',s.exists?(s.leaf_exists?'An Orders label is stored.':'The label array exists without an Orders entry.'):'No label array is stored. Blank keeps it absent; a nonblank save creates only Orders.');
  el('conditions').replaceChildren();for(const line of s.conditions){const li=document.createElement('li');li.textContent=line;el('conditions').appendChild(li);}
  el('history').hidden=!s.receipt;text('receipt',s.receipt||'');text('history-status',s.history_stale?'The value or relevant context has changed. Undo is unavailable; newer settings are kept.':(s.receipt_current?'This receipt records saved configuration, not storefront output.':''));el('undo').hidden=!s.undo;
  text('next','Check the signed-in My Account navigation and Orders page heading when both switches are On. Do not change order statuses, roles or page addresses just to check this wording. Restore the previous label or use Undo last Orders label change if needed.');
  input.value=retained?retained.value:s.value;
  if(retained){parked=retained.parked===true||retained.guard!==s.guard;if(parked)tell('Your earlier draft is parked because its saved context changed or needs a deliberate reload. Copy it if needed, then discard and load current values.',true);else tell('Your unsaved Orders wording was retained.');}
  controls();
 }
 async function request(op,fields){
  const body=new URLSearchParams(Object.assign({action:'wcj_account_orders_label_job_'+op,nonce:cfg.nonces[op]},fields||{}));
  const response=await fetch(cfg.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString(),cache:'no-store'});
  let json;try{json=await response.json();}catch(e){throw new Error('The response could not be verified. Your draft is parked; load current values before another change.');}
  if(!response.ok||!json.success)throw new Error(json&&json.data&&json.data.message||'The saved outcome could not be verified.');
  return json.data;
 }
 async function load(retained){busy=true;controls();try{const s=await request('load');parked=false;tell('Saved configuration loaded.');display(s,retained);}catch(e){parked=true;tell(e.message,true);}finally{busy=false;controls();}}
 input.addEventListener('input',()=>{remember();controls();});
 el('form').addEventListener('submit',async e=>{e.preventDefault();if(el('save').disabled)return;busy=true;controls();try{const s=await request('save',{guard:state.guard,value:input.value});parked=false;draft=null;try{sessionStorage.removeItem(cfg.storage);}catch(ignore){}display(s);tell(s.receipt||'No change needed.');el('notice').focus();}catch(error){parked=true;tell(error.message,true);remember();el('notice').focus();}finally{busy=false;controls();}});
 el('undo').addEventListener('click',async()=>{if(el('undo').disabled)return;busy=true;controls();try{const s=await request('undo',{guard:state.guard});parked=false;display(s);tell(s.receipt||'Previous account-label state restored.');el('notice').focus();}catch(error){parked=true;tell(error.message,true);el('notice').focus();}finally{busy=false;controls();}});
 el('reload').addEventListener('click',()=>{draft=null;try{sessionStorage.removeItem(cfg.storage);}catch(ignore){}parked=false;load(null);});
 window.addEventListener('beforeunload',e=>{if(state&&input.value!==state.value){remember();e.preventDefault();e.returnValue='';}});
 window.addEventListener('pageshow',e=>{if(e.persisted&&state){parked=true;tell('This page was restored from browser history. Your draft is parked. Discard and load current values before saving.',true);remember();controls();}});
 load(draft);
})();
