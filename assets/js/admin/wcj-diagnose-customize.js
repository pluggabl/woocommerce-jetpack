(function () {
 'use strict';
 const card = document.querySelector('.wcj-history-task');
 if (!card) return;
 const form = card.querySelector('form');
 const input = form.querySelector('[name="id"]');
 const button = form.querySelector('button');
 const status = card.querySelector('.wcj-history-status');
 const output = card.querySelector('.wcj-history-output');
 let generation = 0;

 function node(tag, text) {
  const element = document.createElement(tag);
  element.textContent = text;
  return element;
 }
 function display(value, empty) {
  return (value.text || empty) + (value.shortened ? ' [shortened to 200 characters]' : '');
 }
 function render(result) {
  output.replaceChildren();
  output.appendChild(node('h3', display(result.title, '(No saved title)')));
  output.appendChild(node('p', (result.type === 'variation' ? 'Variation' : 'Product') + ' ID ' + result.id));
  output.appendChild(node('h4', 'Current saved slug'));
  output.appendChild(node('p', display(result.current, '(Saved empty slug)')));
  if (!result.count) {
   output.appendChild(node('p', 'No old addresses are saved for this product.'));
  } else {
   const heading = result.overflow ? 'Showing ' + result.shown + '; more than 100 saved entries.' :
    'Showing ' + result.shown + ' of ' + result.count + ' saved entries.';
   output.appendChild(node('h4', heading));
   const list = document.createElement('ol');
   result.entries.forEach(function (entry) {
    list.appendChild(node('li', display(entry, '') + (entry.same_as_current ? ' (Same as current slug)' : '')));
   });
   output.appendChild(list);
  }
  output.appendChild(node('p', 'Read at ' + result.read_at + '. Saved records do not prove a browser redirect works or a page loads.'));
  const editor = node('a', result.type === 'variation' ? 'Edit parent product (new tab)' : 'Edit product (new tab)');
  // The server supplies only the local, capability-checked editor destination, never a saved slug URL.
  editor.href = result.editor_url;
  editor.target = '_blank';
  editor.rel = 'noopener noreferrer';
  output.appendChild(editor);
  output.focus();
 }
 input.addEventListener('input', function () {
  generation += 1;
  output.replaceChildren();
  button.disabled = false;
  status.textContent = 'ID changed. Show saved history again for this ID; previous results are cleared.';
 });
 form.addEventListener('submit', async function (event) {
  event.preventDefault();
  const serial = ++generation;
  const id = input.value;
  output.replaceChildren();
  if (!/^[1-9][0-9]{0,18}$/.test(id)) {
   status.textContent = 'Enter one whole positive product or variation ID.';
   button.disabled = false;
   return;
  }
  button.disabled = true;
  status.textContent = 'Reading saved records for this ID...';
  const data = new URLSearchParams({ action: 'wcj_product_slug_history', nonce: wcjDiagnoseCustomize.nonce, id: id });
  try {
   const response = await fetch(wcjDiagnoseCustomize.ajax, { method: 'POST', credentials: 'same-origin', body: data });
   const result = await response.json();
   if (serial !== generation || input.value !== id) return;
   if (!response.ok || !result.success) {
    status.textContent = result.data && typeof result.data.message === 'string' ? result.data.message : 'Saved history could not be read. Reload the page and try again.';
    return;
   }
   render(result.data);
   status.textContent = 'Saved records read. This check made no changes to the product or history.';
  } catch (error) {
   if (serial === generation && input.value === id) {
    output.replaceChildren();
    status.textContent = 'The result could not be read. Try Show saved history again. No change action was requested.';
   }
  } finally {
   if (serial === generation) button.disabled = false;
  }
 });
}());
