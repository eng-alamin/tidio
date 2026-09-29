/* Loop UI behaviour: skeleton, inbox panes, a11y helpers. Loaded after app.js. */
(function(){
  var $=function(s,r){return (r||document).querySelector(s)},$$=function(s,r){return [].slice.call((r||document).querySelectorAll(s))};

  /* toast live region (app.js appends into it) */
  var t=document.createElement('div');t.id='toasts';t.setAttribute('role','status');t.setAttribute('aria-live','polite');document.body.appendChild(t);

  /* skeleton: shown once per page load, removed when ready. Real apps: toggle .is-loading + aria-busy around data fetches. */
  var area=$('.body,.inbox');
  if(area&&!matchMedia('(prefers-reduced-motion:reduce)').matches){
    area.classList.add('is-loading');area.setAttribute('aria-busy','true');
    var done=function(){area.classList.remove('is-loading');area.removeAttribute('aria-busy')};
    addEventListener('load',function(){setTimeout(done,450)});setTimeout(done,2500);
  }

  /* banner dismiss */
  var bn=$('.banner');
  if(bn){try{if(sessionStorage.getItem('bn'))bn.hidden=true}catch(e){}
    var x=$('[data-dismiss]',bn);if(x)x.addEventListener('click',function(){bn.hidden=true;try{sessionStorage.setItem('bn',1)}catch(e){}})}

  /* header shortcuts */
  document.addEventListener('click',function(e){var b=e.target.closest('[data-toast]');if(b){var m=document.createElement('div');m.className='toast';m.innerHTML='<i class="bi bi-info-circle" aria-hidden="true"></i><span></span>';m.lastChild.textContent=b.dataset.toast;t.appendChild(m);setTimeout(function(){m.remove()},3200)}});

  /* tables: data-label for stacked mobile cards (also for rows added later) */
  function label(tb){var h=$$('th',tb).map(function(x){return x.textContent.trim()});$$('tr',tb).forEach(function(tr){$$('td',tr).forEach(function(td,i){if(h[i]&&!td.dataset.label)td.dataset.label=h[i]})})}
  $$('table').forEach(function(tb){label(tb);new MutationObserver(function(){label(tb)}).observe(tb,{childList:true,subtree:true})});

  /* inbox: list -> chat -> details on phones */
  var ib=$('.inbox');
  if(ib){
    var pane=function(p){ib.dataset.pane=p;var s=$('.'+(p==='list'?'list':p),ib);if(s)s.scrollTop=0};
    var h=$('.chat header');
    if(h){
      h.insertAdjacentHTML('afterbegin','<button class="ib back" aria-label="Back to conversations"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>');
      h.insertAdjacentHTML('beforeend','<button class="ib more" aria-label="Customer details"><i class="bi bi-person-lines-fill" aria-hidden="true"></i></button>');
      $('.back',h).addEventListener('click',function(){pane('list')});$('.more',h).addEventListener('click',function(){pane('info')});
    }
    var info=$('.info');
    if(info)info.insertAdjacentHTML('afterbegin','<button class="btn back" style="margin-bottom:12px"><i class="bi bi-chevron-left" aria-hidden="true"></i>Back to chat</button>'),$('.back',info).addEventListener('click',function(){pane('chat')});
    ib.addEventListener('click',function(e){if(e.target.closest('.conv'))pane('chat')});
  }

  /* settings chip strip: bring active item into view */
  var on=$('.snav a.on');if(on)on.scrollIntoView({inline:'center',block:'nearest'});

  /* modals: label association, focus trap, focus return */
  var opener=null;
  document.addEventListener('click',function(e){if(!e.target.closest('.ov'))opener=e.target.closest('button,a')||opener},true);
  new MutationObserver(function(list){list.forEach(function(r){
    r.addedNodes.forEach(function(n){
      if(!n.matches||!n.matches('.ov'))return;
      $$('label',n).forEach(function(l,i){var c=l.nextElementSibling;if(c&&c.matches('input,select,textarea')){c.id=c.id||'mf'+i;l.htmlFor=c.id}});
      n.addEventListener('keydown',function(e){if(e.key!=='Tab')return;var f=$$('button,input,select,textarea',n);if(!f.length)return;var a=f[0],z=f[f.length-1];
        if(e.shiftKey&&document.activeElement===a){e.preventDefault();z.focus()}else if(!e.shiftKey&&document.activeElement===z){e.preventDefault();a.focus()}});
    });
    r.removedNodes.forEach(function(n){if(n.matches&&n.matches('.ov')&&opener&&document.contains(opener))opener.focus()});
  })}).observe(document.body,{childList:true});
})();
