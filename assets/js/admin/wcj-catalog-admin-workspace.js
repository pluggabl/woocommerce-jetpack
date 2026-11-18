(() => {
 'use strict';
 const cfg = window.wcjCatalogAdminWorkspace;
 if (!cfg) return;
 let toolbarDirty = false, costDirty = false;
 async function send(kind, op, data) {
  const prefix = kind === 'toolbar' ? 'wcj_toolbar_categories_' : 'wcj_missing_cost_';
  const body = new URLSearchParams({action: prefix + op, nonce: cfg.nonces[kind][op], ...data});
  const res = await fetch(cfg.url, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body});
  let json; try { json = await res.json(); } catch (_) { throw new Error('The response was not readable. Reload saved results before another change.'); }
  if (!res.ok || !json.success) throw new Error(json.data?.message || 'The outcome could not be verified. Reload before another change.');
  return json.data;
 }
 const toolbar = document.querySelector('.wcj-toolbar-task');
 if (toolbar) {
  const input = toolbar.querySelector('[name=categories]'), save = toolbar.querySelector('[type=submit]'), restore = toolbar.querySelector('.wcj-toolbar-restore'), reload = toolbar.querySelector('.wcj-toolbar-reload'), status = toolbar.querySelector('.wcj-toolbar-status'), saved = toolbar.querySelector('.wcj-toolbar-saved');
  let base = null, busy = false, stale = true;
  const value = () => input.checked ? 'yes' : 'no';
  function paint() {
   input.disabled = busy || !base?.editable || stale;
   save.disabled = busy || !base?.editable || stale || (!toolbarDirty && base?.exists);
   restore.disabled = busy || !base?.restore || stale || toolbarDirty;
   reload.disabled = busy;
  }
  function adopt(data, message) {
   base = data; stale = false; toolbarDirty = false; input.checked = data.value === 'yes'; saved.textContent = data.saved;
   status.textContent = data.reason || data.recovery_reason || message || (data.restore ? 'Saved state loaded. Your latest eligible previous setting can be restored.' : 'Saved state loaded. Restore is unavailable unless your latest eligible task save and related settings still match.'); paint();
  }
  async function load(confirmDiscard) {
   if (busy || (confirmDiscard && toolbarDirty && !window.confirm('Discard this unsaved toolbar preference and reload?'))) return;
   busy = true; paint();
   try { adopt(await send('toolbar','load',{type:'toolbar',field:'categories'})); }
   catch(e) { stale = true; status.textContent = e.message; }
   finally { busy = false; paint(); }
  }
  input.addEventListener('change', () => { toolbarDirty = !!base && (value() !== base.value); status.textContent = toolbarDirty ? 'Draft not saved yet.' : 'Draft matches the loaded preference.'; paint(); });
  toolbar.addEventListener('submit', async e => {
   e.preventDefault(); if (save.disabled) return; busy = true; paint();
   try { adopt(await send('toolbar','save',{type:'toolbar',field:'categories',guard:base.guard,value:value()}),'Preference saved and read back.'); }
   catch(e) { stale = true; status.textContent = e.message + ' Your draft is retained.'; }
   finally { busy = false; paint(); }
  });
  restore.addEventListener('click', async () => {
   if (restore.disabled) return; busy = true; paint();
   try { adopt(await send('toolbar','restore',{type:'toolbar',field:'categories',guard:base.guard,receipt:base.receipt_token}),'Previous saved state restored, including whether the setting was not set.'); }
   catch(e) { stale = true; status.textContent = e.message; }
   finally { busy = false; paint(); }
  });
  reload.addEventListener('click', () => load(true)); load(false);
 }
 const cost = document.querySelector('.wcj-cost-task');
 if (cost) {
  const input = cost.querySelector('[name=ids]'), preview = cost.querySelector('[type=submit]'), execute = cost.querySelector('.wcj-cost-execute'), results = cost.querySelector('.wcj-cost-results'), undo = cost.querySelector('.wcj-cost-undo'), status = cost.querySelector('.wcj-cost-status'), output = cost.querySelector('.wcj-cost-output');
  let busy = false, proposal = null, job = null, uncertain = false;
  function paint() {
   input.disabled = busy; preview.disabled = busy || uncertain; results.disabled = busy;
   execute.textContent = 'Fill ' + (proposal?.eligible || 0) + ' product costs';
   execute.disabled = busy || uncertain || !proposal?.eligible || Date.now() > proposal.expires*1000;
   undo.disabled = busy || uncertain || !job?.undo;
  }
  function table(items, caption) {
   output.replaceChildren(); const t = document.createElement('table'); t.className = 'widefat striped';
   const c = document.createElement('caption'); c.textContent = caption; t.append(c);
   const head = document.createElement('thead'), tr = document.createElement('tr');
   ['ID','Product','Legacy amount','Outcome / reason'].forEach(label => { const th = document.createElement('th'); th.scope = 'col'; th.textContent = label; tr.append(th); }); head.append(tr); t.append(head);
   const body = document.createElement('tbody');
   items.forEach(item => { const row = document.createElement('tr'); [item.id,item.title || 'Details unavailable',item.amount ?? '',(item.status ? item.status + ': ' : '') + item.reason].forEach(value => { const td = document.createElement('td'); td.textContent = String(value); row.append(td); }); body.append(row); }); t.append(body); output.append(t);
  }
  input.addEventListener('input', () => { proposal = null; costDirty = true; status.textContent = 'Selection changed. Make a new preview; no costs are saved by editing these IDs.'; paint(); });
  cost.addEventListener('submit', async e => {
   e.preventDefault(); if (busy || uncertain) return; busy = true; proposal = null; paint();
   try { proposal = await send('cost','preview',{ids:input.value}); uncertain = false; costDirty = false; table(proposal.items,'Preview only. No costs or recovery records saved.'); status.textContent = proposal.eligible + ' eligible product(s). Check the reasons below. Preview expires in ten minutes.'; }
   catch(e) { status.textContent = e.message; }
   finally { busy = false; paint(); }
  });
  execute.addEventListener('click', async () => {
   if (execute.disabled || !window.confirm('Fill missing costs for ' + proposal.eligible + ' eligible product(s)? Earlier items can remain saved if another item fails. A new fill replaces recovery for your previous fill.')) return;
   busy = true; paint();
   try { job = await send('cost','execute',{token:proposal.token}); proposal = null; uncertain = false; table(job.items,'Saved task results, not a promise every selected product changed.'); status.textContent = 'Results read back. Review filled, skipped and pending items. Undo only clears unchanged rows created by this task.' + (job.undo_reason ? ' ' + job.undo_reason : ''); }
   catch(e) { uncertain = true; status.textContent = e.message + ' Reload saved results before retrying.'; }
   finally { busy = false; paint(); }
  });
  async function readResults(initial = false) {
   if (busy) return; busy = true; paint();
   try { job = await send('cost','status',{operation:''}); const pending = job.items.filter(item => item.status === 'pending').length; if (proposal && job.operation === proposal.operation && pending && Date.now() <= proposal.expires*1000) { proposal.eligible = pending; } else { proposal = null; } uncertain = false; if(job.operation) { table(job.items,'Your latest saved cost task results.'); status.textContent = 'Saved results loaded. Pending items are not filled. A still-valid preview may resume pending items.' + (job.undo_reason ? ' ' + job.undo_reason : ''); } else { output.replaceChildren(); if(job.undo_reason || !initial) status.textContent = job.undo_reason || 'No saved cost task is retained for your account.'; } }
   catch(e) { status.textContent = e.message; }
   finally { busy = false; paint(); }
  }
  results.addEventListener('click', () => readResults());
  undo.addEventListener('click', async () => {
   if (undo.disabled || !window.confirm('Clear only unchanged costs created by your latest eligible fill? Other product data will remain.')) return;
   busy = true; paint();
   try { job = await send('cost','undo',{operation:job.operation}); table(job.items,'Undo results. Retained or changed costs were not removed.'); status.textContent = 'Undo results read back. Check each product outcome.' + (job.undo_reason ? ' ' + job.undo_reason : ''); }
   catch(e) { uncertain = true; status.textContent = e.message + ' Reload saved results before another action.'; }
   finally { busy = false; paint(); }
  });
  setInterval(paint, 1000); readResults(true);
 }
 window.addEventListener('beforeunload', event => { if(toolbarDirty || costDirty) { event.preventDefault(); event.returnValue = ''; } });
})();
