(function () {
 'use strict';
 const cfg=window.wcjCurrencySymbolJob, root=document.getElementById('wcj-symbol-job'); if(!cfg||!root)return;
 const el=id=>document.getElementById('wcj-symbol-'+id), input=el('value');
 let state=null, parked=false, busy=false, draft=null;
 try { draft=JSON.parse(sessionStorage.getItem(cfg.storage)||'null'); if(!draft||typeof draft.value!=='string'||typeof draft.guard!=='string')draft=null; } catch(e){draft=null;}
 function remember(){try{if(state && input.value!==state.value)sessionStorage.setItem(cfg.storage,JSON.stringify({value:input.value,guard:state.guard,parked:parked}));else sessionStorage.removeItem(cfg.storage);}catch(e){/* Current draft remains visible even if browser storage is disabled. */}}
 function text(id,value){el(id).textContent=value;}
 function tell(message,error){text('notice',message);el('notice').classList.toggle('wcj-symbol-warning',!!error);}
 function valid(value){return value.length>0&&new TextEncoder().encode(value).length<=64&&value.trim()===value&&!/[\u0000-\u001f\u007f-\u009f<>\[\]\p{Cf}]|&(?:[A-Za-z][A-Za-z0-9]+;|#(?:[0-9]+|[xX][0-9A-Fa-f]+);?)|%[0-9A-Fa-f]{2}| {2,}/u.test(value)&&/[^\s\p{Z}\p{Cf}]/u.test(value);}
 function controls(){
  const changed=!!state&&input.value!==state.value;
  input.disabled=busy||!state;
  input.readOnly=!!state&&(!state.editable||parked);
  el('draft').hidden=!changed; text('sample',input.value+' 123.45');
  text('validation',changed&&!valid(input.value)?'Use visible plain text up to 64 UTF-8 bytes. No code, encoded symbols, blank or whitespace changes.':'');
  el('save').disabled=busy||parked||!state||!state.editable||!changed||!valid(input.value);
  el('undo').disabled=busy||parked||changed||!state||!state.undo;
  el('reload').disabled=busy;
 }
 function display(s,retained){
  state=s;text('title','Change the symbol shown for '+(s.code||'your saved store currency'));text('code',s.code||'Missing or unsupported');text('branch',s.branch||'Not resolved');text('target',s.code||'the saved store currency');text('saved',s.saved);text('module',s.module_on?'On':'Off. Saving here does not activate it.');text('blocker',s.reason||'');el('blocker').hidden=!s.reason;text('key',s.field||'No supported target');text('existence',s.exists?'A native value is stored.':'The native symbol option is absent.');
  el('history').hidden=!s.receipt;text('receipt',s.receipt||'');text('history-status',s.history_stale?'The value or relevant context has changed. Undo is unavailable; newer settings are kept.':(s.receipt_current?'This receipt records saved configuration, not storefront output.':''));el('undo').hidden=!s.undo;
  text('next',s.module_on?'Check a product and your checkout display in the same currency. Confirm numeric amounts and the currency code are unchanged. Theme and integration behavior can differ.':'Currencies is off. If you want this symbol used, review activation separately in full settings before checking display.');
  input.value=retained?retained.value:s.value;
  if(retained){parked=retained.parked===true||retained.guard!==s.guard;if(parked)tell('Your earlier draft is parked because its saved context changed or needs a deliberate reload. Copy it if needed, then discard and load current values.',true);else tell('Your unsaved symbol draft was retained.');}
  controls();
 }
 async function request(op,fields){
  const body=new URLSearchParams(Object.assign({action:'wcj_currency_symbol_job_'+op,nonce:cfg.nonces[op]},fields||{}));
  const response=await fetch(cfg.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString(),cache:'no-store'});
  let json;try{json=await response.json();}catch(e){throw new Error('The response could not be verified. Your draft is parked; load current values before another change.');}
  if(!response.ok||!json.success)throw new Error(json&&json.data&&json.data.message||'The saved outcome could not be verified.');
  return json.data;
 }
 async function load(retained){busy=true;controls();try{const s=await request('load');parked=false;tell('Saved configuration loaded.');display(s,retained);}catch(e){parked=true;tell(e.message,true);}finally{busy=false;controls();}}
 input.addEventListener('input',()=>{remember();controls();});
 el('form').addEventListener('submit',async e=>{e.preventDefault();if(el('save').disabled)return;busy=true;controls();try{const s=await request('save',{guard:state.guard,value:input.value});parked=false;draft=null;try{sessionStorage.removeItem(cfg.storage);}catch(ignore){}display(s);tell(s.receipt||'No change needed.');el('notice').focus();}catch(error){parked=true;tell(error.message,true);remember();el('notice').focus();}finally{busy=false;controls();}});
 el('undo').addEventListener('click',async()=>{if(el('undo').disabled)return;busy=true;controls();try{const s=await request('undo',{guard:state.guard});parked=false;display(s);tell(s.receipt||'Previous native value restored.');el('notice').focus();}catch(error){parked=true;tell(error.message,true);el('notice').focus();}finally{busy=false;controls();}});
 el('reload').addEventListener('click',()=>{draft=null;try{sessionStorage.removeItem(cfg.storage);}catch(ignore){}parked=false;load(null);});
 window.addEventListener('beforeunload',e=>{if(state&&input.value!==state.value){remember();e.preventDefault();e.returnValue='';}});
 window.addEventListener('pageshow',e=>{if(e.persisted&&state){parked=true;tell('This page was restored from browser history. Your draft is parked. Discard and load current values before saving.',true);remember();controls();}});
 load(draft);
})();
