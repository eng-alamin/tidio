/* =========================================================
   Loop Super Admin — shared behaviour
   ========================================================= */

/* ---------- Theme ---------- */
function applyTheme(theme){
  document.documentElement.setAttribute('data-theme', theme);
  const icon = document.getElementById('themeIcon');
  if(icon) icon.className = theme === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
  const btn = document.getElementById('themeToggleBtn');
  if(btn) btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
  localStorage.setItem('loop-theme', theme);
}
(function initTheme(){
  const saved = localStorage.getItem('loop-theme') || 'dark';
  document.documentElement.setAttribute('data-theme', saved);
})();

/* ---------- Toasts ---------- */
function toast(msg, type){
  type = type || 'ok';
  const wrap = document.getElementById('toastWrap');
  if(!wrap) return;
  const icon = type === 'ok' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill';
  const el = document.createElement('div');
  el.className = 'toast-item glass ' + type;
  el.setAttribute('role', type === 'error' ? 'alert' : 'status');
  el.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
  el.innerHTML = `<i class="bi ${icon} ti-icon"></i><span>${msg}</span><button class="ti-close" aria-label="Dismiss notification"><i class="bi bi-x"></i></button>`;
  el.querySelector('.ti-close').addEventListener('click', ()=> removeToast(el));
  wrap.appendChild(el);
  const timer = setTimeout(()=> removeToast(el), 4000);
  el.dataset.timer = timer;
}
function removeToast(el){
  clearTimeout(el.dataset.timer);
  el.style.transition = '.2s';
  el.style.opacity = '0';
  el.style.transform = 'translateY(6px)';
  setTimeout(()=> el.remove(), 200);
}

/* ---------- Desktop collapse (icon rail) ---------- */
function wireSidebarLabels(){
  // Wrap bare text nodes inside nav items/brand so they can be hidden when collapsed,
  // and add a native title tooltip for the icon-only state.
  document.querySelectorAll('.sidebar .nav-item, .sidebar .brand').forEach(el=>{
    Array.from(el.childNodes).forEach(node=>{
      if(node.nodeType === 3 && node.textContent.trim()){
        const span = document.createElement('span');
        span.className = 'nav-label';
        span.textContent = node.textContent;
        el.replaceChild(span, node);
        if(el.classList.contains('nav-item') && !el.title) el.title = span.textContent.trim();
      }
    });
  });
}
function toggleSidebarCollapse(force){
  const shell = document.querySelector('.app-shell');
  if(!shell) return;
  const isCollapsed = typeof force === 'boolean' ? force : !shell.classList.contains('sidebar-collapsed');
  shell.classList.toggle('sidebar-collapsed', isCollapsed);
  localStorage.setItem('loop-sidebar-collapsed', isCollapsed ? '1' : '0');
  const hamburger = document.getElementById('hamburgerBtn');
  if(hamburger) hamburger.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
}

/* ---------- Mobile drawer + desktop collapse (shared trigger) ---------- */
function wireMobileNav(){
  const hamburger = document.getElementById('hamburgerBtn');
  const sidebar = document.querySelector('.sidebar');
  const scrim = document.getElementById('sidebarScrim');
  if(!hamburger || !sidebar || !scrim) return;

  wireSidebarLabels();
  if(localStorage.getItem('loop-sidebar-collapsed') === '1') toggleSidebarCollapse(true);

  const isMobile = ()=> window.matchMedia('(max-width:900px)').matches;
  function open(){ sidebar.classList.add('open'); scrim.classList.add('open'); hamburger.setAttribute('aria-expanded','true'); }
  function close(){ sidebar.classList.remove('open'); scrim.classList.remove('open'); hamburger.setAttribute('aria-expanded','false'); }
  hamburger.addEventListener('click', ()=>{
    if(isMobile()) sidebar.classList.contains('open') ? close() : open();
    else toggleSidebarCollapse();
  });
  scrim.addEventListener('click', close);
  sidebar.querySelectorAll('a').forEach(a=> a.addEventListener('click', close));
  document.addEventListener('keydown', e=>{ if(e.key === 'Escape') close(); });
}

/* ---------- Dropdowns ---------- */
function closeAllDropdowns(){
  document.querySelectorAll('.dropdown-panel.open').forEach(p=>{
    p.classList.remove('open');
    const btn = document.querySelector(`[aria-controls="${p.id}"]`);
    if(btn) btn.setAttribute('aria-expanded', 'false');
  });
}
function wireDropdown(btnId, panelId){
  const btn = document.getElementById(btnId);
  const panel = document.getElementById(panelId);
  if(!btn || !panel) return;
  btn.setAttribute('aria-haspopup', 'true');
  btn.setAttribute('aria-expanded', 'false');
  btn.setAttribute('aria-controls', panelId);
  btn.addEventListener('click', e=>{
    e.stopPropagation();
    const willOpen = !panel.classList.contains('open');
    closeAllDropdowns();
    panel.classList.toggle('open', willOpen);
    btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if(willOpen){ const first = panel.querySelector('.dd-item'); if(first) first.focus(); }
  });
  panel.addEventListener('click', e=> e.stopPropagation());
}

/* ---------- Modal (with focus trap + return focus) ---------- */
let lastFocusedEl = null;
function openModal(html){
  const overlay = document.getElementById('modalOverlay');
  const box = document.getElementById('modalBox');
  if(!overlay || !box) return;
  lastFocusedEl = document.activeElement;
  box.innerHTML = '<button class="icon-btn modal-close" onclick="closeModal()" aria-label="Close dialog"><i class="bi bi-x-lg"></i></button>' + html;
  overlay.classList.add('open');
  overlay.setAttribute('role','dialog');
  overlay.setAttribute('aria-modal','true');
  document.body.style.overflow = 'hidden';
  setTimeout(()=>{
    const focusable = box.querySelector('input, select, textarea, button:not(.modal-close)');
    (focusable || box.querySelector('.modal-close')).focus();
  }, 50);
}
function closeModal(){
  const overlay = document.getElementById('modalOverlay');
  if(!overlay) return;
  overlay.classList.remove('open');
  document.body.style.overflow = '';
  if(lastFocusedEl) lastFocusedEl.focus();
}
document.addEventListener('keydown', e=>{
  if(e.key === 'Escape'){ closeModal(); closeAllDropdowns(); }
  if(e.key === 'Tab'){
    const overlay = document.getElementById('modalOverlay');
    if(overlay && overlay.classList.contains('open')){
      const box = document.getElementById('modalBox');
      const items = box.querySelectorAll('button, input, select, textarea, a[href]');
      if(!items.length) return;
      const first = items[0], last = items[items.length-1];
      if(e.shiftKey && document.activeElement === first){ e.preventDefault(); last.focus(); }
      else if(!e.shiftKey && document.activeElement === last){ e.preventDefault(); first.focus(); }
    }
  }
});

/* ---------- Inline validation helpers ---------- */
function markInvalid(inputEl, errorEl, message){
  inputEl.classList.add('is-invalid');
  inputEl.setAttribute('aria-invalid', 'true');
  if(errorEl){ errorEl.textContent = message; errorEl.classList.add('show'); }
}
function clearInvalid(inputEl, errorEl){
  inputEl.classList.remove('is-invalid');
  inputEl.removeAttribute('aria-invalid');
  if(errorEl) errorEl.classList.remove('show');
}
function isValidEmail(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

/* =========================================================
   Mock data (mutable — CRUD operates on these)
   ========================================================= */
let tenants = [
  {id:'t1', name:'Northwind Retail', initials:'NR', owner:'Alice Meyer', email:'alice@northwind.io', plan:'Pro', status:'active', agents:12, mrr:'$249', joined:'2026-08-02'},
  {id:'t2', name:'Bluepeak Studio', initials:'BP', owner:'Sam Rowe', email:'sam@bluepeak.io', plan:'Growth', status:'trial', agents:4, mrr:'$0', joined:'2026-09-10'},
  {id:'t3', name:'Vertex Freight', initials:'VF', owner:'Dana Cole', email:'dana@vertexfreight.com', plan:'Enterprise', status:'active', agents:38, mrr:'$1,299', joined:'2026-05-18'},
  {id:'t4', name:'Harbor & Co', initials:'HC', owner:'Priya Nair', email:'priya@harborco.com', plan:'Starter', status:'past', agents:2, mrr:'$29', joined:'2026-09-01'},
  {id:'t5', name:'Orbit Fitness', initials:'OF', owner:'Leo Park', email:'leo@orbitfitness.com', plan:'Growth', status:'active', agents:9, mrr:'$249', joined:'2026-07-22'},
  {id:'t6', name:'Cedarline Legal', initials:'CL', owner:'Maya Chen', email:'maya@cedarlinelegal.com', plan:'Pro', status:'suspended', agents:6, mrr:'$0', joined:'2026-04-14'},
];
let users = [
  {id:'u1', name:'Alice Meyer', initials:'AM', email:'alice@northwind.io', tenant:'Northwind Retail', role:'Owner', status:'active', last:'2 min ago'},
  {id:'u2', name:'Sam Rowe', initials:'SR', email:'sam@bluepeak.io', tenant:'Bluepeak Studio', role:'Admin', status:'active', last:'1 hr ago'},
  {id:'u3', name:'Dana Cole', initials:'DC', email:'dana@vertexfreight.com', tenant:'Vertex Freight', role:'Owner', status:'active', last:'Just now'},
  {id:'u4', name:'Priya Nair', initials:'PN', email:'priya@harborco.com', tenant:'Harbor & Co', role:'Agent', status:'suspended', last:'3 days ago'},
  {id:'u5', name:'Leo Park', initials:'LP', email:'leo@orbitfitness.com', tenant:'Orbit Fitness', role:'Admin', status:'active', last:'5 hr ago'},
];
let subs = [
  {id:'s1', name:'Northwind Retail', plan:'Pro', cycle:'Monthly', renewal:'2026-10-02', status:'active'},
  {id:'s2', name:'Bluepeak Studio', plan:'Growth', cycle:'Trial', renewal:'2026-09-30', status:'trial'},
  {id:'s3', name:'Vertex Freight', plan:'Enterprise', cycle:'Annual', renewal:'2027-05-18', status:'active'},
  {id:'s4', name:'Harbor & Co', plan:'Starter', cycle:'Monthly', renewal:'2026-09-28', status:'past'},
];
let invoices = [
  {id:'INV-2091', tenant:'Vertex Freight', amount:'$1,299.00', date:'2026-09-18', status:'active'},
  {id:'INV-2090', tenant:'Northwind Retail', amount:'$249.00', date:'2026-09-15', status:'active'},
  {id:'INV-2089', tenant:'Harbor & Co', amount:'$29.00', date:'2026-09-01', status:'past'},
  {id:'INV-2088', tenant:'Orbit Fitness', amount:'$249.00', date:'2026-08-22', status:'active'},
];
const statusMap = {active:'status-active', trial:'status-trial', past:'status-past', suspended:'status-suspended'};
const statusLabel = {active:'Active', trial:'Trial', past:'Past due', suspended:'Suspended'};
let idCounter = 100;
const uid = () => 'id' + (idCounter++);

/* =========================================================
   Row renderers
   ========================================================= */
function tenantRow(t){
  return `<tr data-id="${t.id}">
    <td data-label="Tenant" class="cell-main-td"><div class="cell-main"><div class="cell-avatar" aria-hidden="true">${t.initials}</div><div><div class="cell-title">${t.name}</div><div class="cell-sub">${t.email}</div></div></div></td>
    <td data-label="Owner">${t.owner}</td>
    <td data-label="Plan"><span class="plan-tag">${t.plan}</span></td>
    <td data-label="Status">
      <button class="status-toggle" onclick="toggleTenantStatus('${t.id}')" aria-pressed="${t.status==='active'}" aria-label="Toggle status for ${t.name}, currently ${statusLabel[t.status]}">
        <span class="switch ${t.status==='active' ? 'on':''}"></span><span class="status-pill ${statusMap[t.status]}">${statusLabel[t.status]}</span>
      </button>
    </td>
    <td data-label="Agents">${t.agents}</td>
    <td data-label="MRR">${t.mrr}</td>
    <td data-label="Joined">${t.joined}</td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openEditTenantModal('${t.id}')" aria-label="Edit ${t.name}"><i class="bi bi-pencil"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteTenant('${t.id}')" aria-label="Delete ${t.name}"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function recentTenantRow(t){
  return `<tr data-id="${t.id}">
    <td data-label="Tenant" class="cell-main-td"><div class="cell-main"><div class="cell-avatar" aria-hidden="true">${t.initials}</div><div class="cell-title">${t.name}</div></div></td>
    <td data-label="Plan"><span class="plan-tag">${t.plan}</span></td>
    <td data-label="Status"><span class="status-pill ${statusMap[t.status]}">${statusLabel[t.status]}</span></td>
    <td data-label="Users">${t.agents}</td>
    <td data-label="Joined">${t.joined}</td>
    <td data-label="Actions"><a class="icon-action" href="tenants.html" aria-label="View ${t.name} in tenants"><i class="bi bi-arrow-up-right"></i></a></td>
  </tr>`;
}
function userRow(u){
  return `<tr data-id="${u.id}">
    <td data-label="User" class="cell-main-td"><div class="cell-main"><div class="cell-avatar" aria-hidden="true">${u.initials}</div><div><div class="cell-title">${u.name}</div><div class="cell-sub">${u.tenant}</div></div></div></td>
    <td data-label="Tenant">${u.tenant}</td>
    <td data-label="Role">${u.role}</td>
    <td data-label="Status">
      <button class="status-toggle" onclick="toggleUserStatus('${u.id}')" aria-pressed="${u.status==='active'}" aria-label="Toggle status for ${u.name}, currently ${u.status==='active'?'Active':'Suspended'}">
        <span class="switch ${u.status==='active' ? 'on':''}"></span><span class="status-pill ${u.status==='active'?'status-active':'status-suspended'}">${u.status==='active'?'Active':'Suspended'}</span>
      </button>
    </td>
    <td data-label="Last active">${u.last}</td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openEditUserModal('${u.id}')" aria-label="Edit ${u.name}"><i class="bi bi-pencil"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteUser('${u.id}')" aria-label="Delete ${u.name}"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function subRow(s){
  return `<tr data-id="${s.id}">
    <td data-label="Tenant" class="cell-main-td">${s.name}</td>
    <td data-label="Plan"><span class="plan-tag">${s.plan}</span></td>
    <td data-label="Cycle">${s.cycle}</td>
    <td data-label="Renewal">${s.renewal}</td>
    <td data-label="Status"><span class="status-pill ${statusMap[s.status]}">${statusLabel[s.status]}</span></td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openEditSubModal('${s.id}')" aria-label="Edit ${s.name} subscription"><i class="bi bi-pencil"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteSub('${s.id}')" aria-label="Cancel ${s.name} subscription"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function invoiceRow(i){
  return `<tr data-id="${i.id}">
    <td data-label="Invoice" class="cell-main-td">${i.id}</td>
    <td data-label="Tenant">${i.tenant}</td>
    <td data-label="Amount">${i.amount}</td>
    <td data-label="Date">${i.date}</td>
    <td data-label="Status"><span class="status-pill ${i.status==='active'?'status-active':'status-past'}">${i.status==='active'?'Paid':'Failed'}</span></td>
    <td data-label="Actions"><button class="icon-action" onclick="openRecordModal('invoice','${i.id}')" aria-label="View invoice ${i.id}"><i class="bi bi-eye"></i></button></td>
  </tr>`;
}
function logRow(l){
  return `<div class="d-flex gap-3 align-items-start" style="padding:12px 0; border-bottom:1px solid var(--border-soft);">
    <div class="stat-icon" style="width:34px;height:34px;font-size:.85rem;background:var(--cobalt-soft); color:#7EA1F5; margin-bottom:0;" aria-hidden="true"><i class="bi ${l.icon}"></i></div>
    <div><div style="font-size:.87rem;">${l.text}</div><div class="cell-sub">${l.time}</div></div>
  </div>`;
}

/* =========================================================
   Empty state
   ========================================================= */
function emptyStateHtml(icon, title, desc, actionLabel, actionFn){
  return `<div class="state-block">
    <div class="state-icon" aria-hidden="true"><i class="bi ${icon}"></i></div>
    <h6>${title}</h6>
    <p>${desc}</p>
    ${actionLabel ? `<button class="btn btn-cobalt btn-sm" onclick="${actionFn}">${actionLabel}</button>` : ''}
  </div>`;
}

/* =========================================================
   Skeleton renderers
   ========================================================= */
function skeletonRows(colCount, rows){
  rows = rows || 4;
  let out = '';
  for(let r=0;r<rows;r++){
    out += '<tr>';
    for(let c=0;c<colCount;c++){
      out += `<td><div class="skeleton sk-text" style="width:${c===0?'80%':'60%'}"></div></td>`;
    }
    out += '</tr>';
  }
  return out;
}
function skeletonLogs(n){
  let out = '';
  for(let i=0;i<n;i++){
    out += `<div class="d-flex gap-3 align-items-start" style="padding:12px 0;">
      <div class="skeleton sk-avatar" aria-hidden="true"></div>
      <div style="flex:1;"><div class="skeleton sk-text" style="width:70%"></div><div class="skeleton sk-text" style="width:40%; height:9px;"></div></div>
    </div>`;
  }
  return out;
}

/* =========================================================
   Render + filter for each collection
   ========================================================= */
function renderTenants(filterText){
  const body = document.getElementById('tenantsBody');
  if(!body) return;
  let list = tenants;
  if(filterText){
    const q = filterText.toLowerCase();
    list = tenants.filter(t=> t.name.toLowerCase().includes(q) || t.owner.toLowerCase().includes(q));
  }
  if(!list.length){
    body.innerHTML = `<tr><td colspan="8" style="padding:0;">${emptyStateHtml('bi-buildings','No tenants found','Try a different search term, or add a brand new tenant to get started.','Add tenant','openNewTenantModal()')}</td></tr>`;
    return;
  }
  body.innerHTML = list.map(tenantRow).join('');
}
function renderUsers(filterText){
  const body = document.getElementById('usersBody');
  if(!body) return;
  let list = users;
  if(filterText){
    const q = filterText.toLowerCase();
    list = users.filter(u=> u.name.toLowerCase().includes(q) || u.tenant.toLowerCase().includes(q));
  }
  if(!list.length){
    body.innerHTML = `<tr><td colspan="6" style="padding:0;">${emptyStateHtml('bi-people','No users found','Try a different search, or invite someone new to the platform.','Invite user','openInviteModal()')}</td></tr>`;
    return;
  }
  body.innerHTML = list.map(userRow).join('');
}
function renderSubs(){
  const body = document.getElementById('subsBody');
  if(!body) return;
  if(!subs.length){
    body.innerHTML = `<tr><td colspan="6" style="padding:0;">${emptyStateHtml('bi-credit-card','No subscriptions yet','Active subscriptions will show up here once a tenant subscribes.')}</td></tr>`;
    return;
  }
  body.innerHTML = subs.map(subRow).join('');
}
function renderBilling(){
  const body = document.getElementById('billingBody');
  if(!body) return;
  if(!invoices.length){
    body.innerHTML = `<tr><td colspan="6" style="padding:0;">${emptyStateHtml('bi-receipt','No invoices yet','Invoices will appear here as tenants are billed.')}</td></tr>`;
    return;
  }
  body.innerHTML = invoices.map(invoiceRow).join('');
}

/* =========================================================
   CRUD: Tenants
   ========================================================= */
function toggleTenantStatus(id){
  const t = tenants.find(x=>x.id===id);
  if(!t) return;
  t.status = t.status === 'active' ? 'suspended' : 'active';
  renderTenants(document.getElementById('tenantSearch') ? document.getElementById('tenantSearch').value : '');
  toast(`${t.name} is now ${statusLabel[t.status]}`, t.status === 'active' ? 'ok' : 'warn');
}
function tenantFormHtml(t){
  t = t || {name:'', owner:'', email:'', plan:'Growth'};
  return `
    <div class="form-field">
      <label class="form-label" for="f-tname">Company name</label>
      <input class="form-control" id="f-tname" value="${t.name}" placeholder="e.g. Acme Corp" required aria-describedby="err-tname">
      <div class="field-error" id="err-tname" role="alert"><i class="bi bi-exclamation-circle"></i><span>Company name is required.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-towner">Owner name</label>
      <input class="form-control" id="f-towner" value="${t.owner}" placeholder="e.g. Jordan Lee">
    </div>
    <div class="form-field">
      <label class="form-label" for="f-temail">Owner email</label>
      <input class="form-control" id="f-temail" type="email" value="${t.email||''}" placeholder="owner@company.com" required aria-describedby="err-temail">
      <div class="field-error" id="err-temail" role="alert"><i class="bi bi-exclamation-circle"></i><span>Enter a valid email address.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-tplan">Plan</label>
      <select class="form-select" id="f-tplan">
        ${['Starter','Growth','Pro','Enterprise'].map(p=>`<option ${p===t.plan?'selected':''}>${p}</option>`).join('')}
      </select>
    </div>`;
}
function openNewTenantModal(){
  closeAllDropdowns();
  openModal(`
    <h4>Create new tenant</h4>
    <div class="modal-sub">Spin up a new workspace on Loop.</div>
    <form id="tenantForm" novalidate>
      ${tenantFormHtml()}
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-cobalt">Create tenant</button>
      </div>
    </form>
  `);
  wireTenantForm(null);
}
function openEditTenantModal(id){
  closeAllDropdowns();
  const t = tenants.find(x=>x.id===id);
  if(!t) return;
  openModal(`
    <h4>Edit tenant</h4>
    <div class="modal-sub">Update ${t.name}'s workspace details.</div>
    <form id="tenantForm" novalidate>
      ${tenantFormHtml(t)}
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-cobalt">Save changes</button>
      </div>
    </form>
  `);
  wireTenantForm(id);
}
function wireTenantForm(editId){
  const form = document.getElementById('tenantForm');
  form.addEventListener('submit', e=>{
    e.preventDefault();
    const nameEl = document.getElementById('f-tname'), emailEl = document.getElementById('f-temail');
    let ok = true;
    if(!nameEl.value.trim()){ markInvalid(nameEl, document.getElementById('err-tname'), 'Company name is required.'); ok = false; } else clearInvalid(nameEl, document.getElementById('err-tname'));
    if(!isValidEmail(emailEl.value.trim())){ markInvalid(emailEl, document.getElementById('err-temail'), 'Enter a valid email address.'); ok = false; } else clearInvalid(emailEl, document.getElementById('err-temail'));
    if(!ok){ toast('Please fix the errors in the form', 'error'); return; }
    const name = nameEl.value.trim();
    const owner = document.getElementById('f-towner').value.trim();
    const email = emailEl.value.trim();
    const plan = document.getElementById('f-tplan').value;
    const initials = name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase() || 'NA';
    if(editId){
      const t = tenants.find(x=>x.id===editId);
      Object.assign(t, {name, owner, email, plan, initials});
      toast('Tenant updated', 'ok');
    } else {
      tenants.unshift({id:uid(), name, owner, email, plan, initials, status:'trial', agents:1, mrr:'$0', joined:new Date().toISOString().slice(0,10)});
      toast('Tenant created', 'ok');
    }
    closeModal();
    renderTenants();
    const recent = document.getElementById('recentTenantsBody');
    if(recent) recent.innerHTML = tenants.slice(0,4).map(recentTenantRow).join('');
  });
}
function confirmDeleteTenant(id){
  const t = tenants.find(x=>x.id===id);
  if(!t) return;
  openModal(`
    <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Delete ${t.name}?</h4>
    <div class="modal-sub">This removes the tenant's workspace and all associated data. This can't be undone.</div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-danger" onclick="doDeleteTenant('${id}')">Delete tenant</button>
    </div>
  `);
}
function doDeleteTenant(id){
  const t = tenants.find(x=>x.id===id);
  tenants = tenants.filter(x=>x.id!==id);
  closeModal();
  renderTenants(document.getElementById('tenantSearch') ? document.getElementById('tenantSearch').value : '');
  const recent = document.getElementById('recentTenantsBody');
  if(recent) recent.innerHTML = tenants.slice(0,4).map(recentTenantRow).join('');
  toast(t ? `${t.name} deleted` : 'Tenant deleted', 'warn');
}

/* =========================================================
   CRUD: Users
   ========================================================= */
function toggleUserStatus(id){
  const u = users.find(x=>x.id===id);
  if(!u) return;
  u.status = u.status === 'active' ? 'suspended' : 'active';
  renderUsers();
  toast(`${u.name} is now ${u.status==='active'?'Active':'Suspended'}`, u.status === 'active' ? 'ok' : 'warn');
}
function userFormHtml(u){
  u = u || {name:'', email:'', tenant:'Northwind Retail', role:'Admin'};
  return `
    <div class="form-field">
      <label class="form-label" for="f-uname">Full name</label>
      <input class="form-control" id="f-uname" value="${u.name}" placeholder="e.g. Jordan Lee" required aria-describedby="err-uname">
      <div class="field-error" id="err-uname" role="alert"><i class="bi bi-exclamation-circle"></i><span>Name is required.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-uemail">Email address</label>
      <input class="form-control" id="f-uemail" type="email" value="${u.email}" placeholder="name@company.com" required aria-describedby="err-uemail">
      <div class="field-error" id="err-uemail" role="alert"><i class="bi bi-exclamation-circle"></i><span>Enter a valid email address.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-utenant">Tenant</label>
      <select class="form-select" id="f-utenant">
        ${tenants.map(t=>`<option ${t.name===u.tenant?'selected':''}>${t.name}</option>`).join('')}
      </select>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-urole">Role</label>
      <select class="form-select" id="f-urole">
        ${['Owner','Admin','Agent'].map(r=>`<option ${r===u.role?'selected':''}>${r}</option>`).join('')}
      </select>
    </div>`;
}
function openInviteModal(){
  closeAllDropdowns();
  openModal(`
    <h4>Invite a user</h4>
    <div class="modal-sub">Send an invite to join a tenant workspace.</div>
    <form id="userForm" novalidate>
      ${userFormHtml()}
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-cobalt">Send invite</button>
      </div>
    </form>
  `);
  wireUserForm(null);
}
function openEditUserModal(id){
  closeAllDropdowns();
  const u = users.find(x=>x.id===id);
  if(!u) return;
  openModal(`
    <h4>Edit user</h4>
    <div class="modal-sub">Update ${u.name}'s account.</div>
    <form id="userForm" novalidate>
      ${userFormHtml(u)}
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-cobalt">Save changes</button>
      </div>
    </form>
  `);
  wireUserForm(id);
}
function wireUserForm(editId){
  const form = document.getElementById('userForm');
  form.addEventListener('submit', e=>{
    e.preventDefault();
    const nameEl = document.getElementById('f-uname'), emailEl = document.getElementById('f-uemail');
    let ok = true;
    if(!nameEl.value.trim()){ markInvalid(nameEl, document.getElementById('err-uname'), 'Name is required.'); ok = false; } else clearInvalid(nameEl, document.getElementById('err-uname'));
    if(!isValidEmail(emailEl.value.trim())){ markInvalid(emailEl, document.getElementById('err-uemail'), 'Enter a valid email address.'); ok = false; } else clearInvalid(emailEl, document.getElementById('err-uemail'));
    if(!ok){ toast('Please fix the errors in the form', 'error'); return; }
    const name = nameEl.value.trim();
    const email = emailEl.value.trim();
    const tenant = document.getElementById('f-utenant').value;
    const role = document.getElementById('f-urole').value;
    const initials = name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase() || 'NA';
    if(editId){
      const u = users.find(x=>x.id===editId);
      Object.assign(u, {name, email, tenant, role, initials});
      toast('User updated', 'ok');
    } else {
      users.unshift({id:uid(), name, email, tenant, role, initials, status:'active', last:'Just now'});
      toast('Invite sent', 'ok');
    }
    closeModal();
    renderUsers();
  });
}
function confirmDeleteUser(id){
  const u = users.find(x=>x.id===id);
  if(!u) return;
  openModal(`
    <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Remove ${u.name}?</h4>
    <div class="modal-sub">They'll lose access to ${u.tenant} immediately. This can't be undone.</div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
      <button class="btn btn-danger" onclick="doDeleteUser('${id}')">Remove user</button>
    </div>
  `);
}
function doDeleteUser(id){
  const u = users.find(x=>x.id===id);
  users = users.filter(x=>x.id!==id);
  closeModal();
  renderUsers();
  toast(u ? `${u.name} removed` : 'User removed', 'warn');
}

/* =========================================================
   CRUD: Subscriptions (edit plan/cycle, cancel)
   ========================================================= */
function subFormHtml(s){
  s = s || {plan:'Growth', cycle:'Monthly'};
  return `
    <div class="form-field">
      <label class="form-label" for="f-splan">Plan</label>
      <select class="form-select" id="f-splan">
        ${['Starter','Growth','Pro','Enterprise'].map(p=>`<option ${p===s.plan?'selected':''}>${p}</option>`).join('')}
      </select>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-scycle">Billing cycle</label>
      <select class="form-select" id="f-scycle">
        ${['Monthly','Annual','Trial'].map(c=>`<option ${c===s.cycle?'selected':''}>${c}</option>`).join('')}
      </select>
    </div>`;
}
function openEditSubModal(id){
  const s = subs.find(x=>x.id===id);
  if(!s) return;
  openModal(`
    <h4>Manage subscription</h4>
    <div class="modal-sub">${s.name}</div>
    <form id="subForm">
      ${subFormHtml(s)}
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-cobalt">Save changes</button>
      </div>
    </form>
  `);
  document.getElementById('subForm').addEventListener('submit', e=>{
    e.preventDefault();
    s.plan = document.getElementById('f-splan').value;
    s.cycle = document.getElementById('f-scycle').value;
    closeModal();
    renderSubs();
    toast('Subscription updated', 'ok');
  });
}
function confirmDeleteSub(id){
  const s = subs.find(x=>x.id===id);
  if(!s) return;
  openModal(`
    <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Cancel ${s.name}'s subscription?</h4>
    <div class="modal-sub">Their plan will end at the close of the current billing cycle.</div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeModal()">Keep subscription</button>
      <button class="btn btn-danger" onclick="doDeleteSub('${id}')">Cancel subscription</button>
    </div>
  `);
}
function doDeleteSub(id){
  const s = subs.find(x=>x.id===id);
  subs = subs.filter(x=>x.id!==id);
  closeModal();
  renderSubs();
  toast(s ? `${s.name}'s subscription cancelled` : 'Subscription cancelled', 'warn');
}

/* =========================================================
   Mock data: Feature flags, Coupons, Staff
   ========================================================= */
let flags = [
  {id:'fl1', name:'lyro-copilot-v2', desc:'New Lyro AI copilot response engine', scope:'Beta cohort', rollout:15, on:true},
  {id:'fl2', name:'flows-branching', desc:'Conditional branching nodes in Flows builder', scope:'All tenants', rollout:100, on:true},
  {id:'fl3', name:'csat-followup-sms', desc:'Send CSAT follow-up via SMS', scope:'Pro & Enterprise', rollout:100, on:false},
  {id:'fl4', name:'billing-annual-nudge', desc:'Show annual-plan savings banner in-app', scope:'All tenants', rollout:50, on:true},
  {id:'fl5', name:'workspace-data-export-v2', desc:'New async export pipeline for large tenants', scope:'Internal QA', rollout:0, on:false},
];
let coupons = [
  {id:'cp1', code:'WELCOME20', discount:'20% off first invoice', applies:'All plans', redemptions:318, expires:'No expiry', status:'active'},
  {id:'cp2', code:'ANNUAL2026', discount:'2 months free', applies:'Annual billing', redemptions:92, expires:'2026-12-31', status:'active'},
  {id:'cp3', code:'WINBACK10', discount:'$10 off, 3 months', applies:'Canceled tenants', redemptions:14, expires:'2026-10-15', status:'trial'},
  {id:'cp4', code:'LAUNCH50', discount:'50% off first month', applies:'Starter, Growth', redemptions:640, expires:'Ended 2026-06-01', status:'suspended'},
];
let staff = [
  {id:'sa1', name:'Sabina Karim', initials:'SK', email:'sabina@loop.chat', role:'super_admin', status:'active', last:'Just now'},
  {id:'sa2', name:'Rafiq Hasan', initials:'RH', email:'rafiq@loop.chat', role:'support_staff', status:'active', last:'2h ago'},
  {id:'sa3', name:'Anika Islam', initials:'AI', email:'anika@loop.chat', role:'support_staff', status:'active', last:'1d ago'},
  {id:'sa4', name:'Mahin Rahman', initials:'MR', email:'mahin@loop.chat', role:'billing_admin', status:'active', last:'3d ago'},
];
const roleLabel = {super_admin:'Super Admin', support_staff:'Support Staff', billing_admin:'Billing Admin'};
const roleClass = {super_admin:'role-super', support_staff:'role-support', billing_admin:'role-billing'};

/* ---------- Row renderers ---------- */
function flagRow(f){
  return `<tr data-id="${f.id}">
    <td data-label="Flag" class="cell-main-td"><code style="font-size:.82rem;">${f.name}</code></td>
    <td data-label="Description">${f.desc}</td>
    <td data-label="Scope">${f.scope}</td>
    <td data-label="Rollout">${f.rollout}%</td>
    <td data-label="Status"><button class="toggle-switch ${f.on?'on':''}" onclick="toggleFlag('${f.id}')" role="switch" aria-checked="${f.on}" aria-label="Toggle ${f.name}"></button></td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openEditFlagModal('${f.id}')" aria-label="Edit ${f.name}"><i class="bi bi-pencil"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteFlag('${f.id}')" aria-label="Delete ${f.name}"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function couponRow(c){
  return `<tr data-id="${c.id}">
    <td data-label="Code" class="cell-main-td"><code style="font-size:.82rem;">${c.code}</code></td>
    <td data-label="Discount">${c.discount}</td>
    <td data-label="Applies to">${c.applies}</td>
    <td data-label="Redemptions">${c.redemptions}</td>
    <td data-label="Expires">${c.expires}</td>
    <td data-label="Status"><span class="status-pill ${statusMap[c.status]}">${statusLabel[c.status]}</span></td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openEditCouponModal('${c.id}')" aria-label="Edit ${c.code}"><i class="bi bi-pencil"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteCoupon('${c.id}')" aria-label="Delete ${c.code}"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function staffRow(s){
  return `<tr data-id="${s.id}">
    <td data-label="Name" class="cell-main-td"><div class="cell-main"><div class="cell-avatar" aria-hidden="true">${s.initials}</div><div class="cell-title">${s.name}</div></div></td>
    <td data-label="Email">${s.email}</td>
    <td data-label="Role"><span class="role-pill ${roleClass[s.role]}">${roleLabel[s.role]}</span></td>
    <td data-label="Last active">${s.last}</td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openEditStaffModal('${s.id}')" aria-label="Edit ${s.name}"><i class="bi bi-pencil"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteStaff('${s.id}')" aria-label="Remove ${s.name}"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}

/* ---------- Render ---------- */
function renderFlags(){
  const body = document.getElementById('flagsBody');
  if(!body) return;
  if(!flags.length){ body.innerHTML = `<tr><td colspan="6" style="padding:0;">${emptyStateHtml('bi-toggles','No feature flags yet','Create a flag to roll out a feature gradually.','New flag','openNewFlagModal()')}</td></tr>`; return; }
  body.innerHTML = flags.map(flagRow).join('');
}
function renderCoupons(filterText){
  const body = document.getElementById('couponsBody');
  if(!body) return;
  let list = coupons;
  if(filterText){
    const q = filterText.toLowerCase();
    list = coupons.filter(c=> c.code.toLowerCase().includes(q) || c.discount.toLowerCase().includes(q));
  }
  if(!list.length){ body.innerHTML = `<tr><td colspan="7" style="padding:0;">${emptyStateHtml('bi-ticket-perforated','No coupons found','Try a different search, or create a new coupon.','New coupon','openNewCouponModal()')}</td></tr>`; return; }
  body.innerHTML = list.map(couponRow).join('');
}
function renderStaff(){
  const body = document.getElementById('staffBody');
  if(!body) return;
  if(!staff.length){ body.innerHTML = `<tr><td colspan="5" style="padding:0;">${emptyStateHtml('bi-shield-lock','No staff accounts yet','Invite a teammate to help run the platform.','Invite admin','openInviteStaffModal()')}</td></tr>`; return; }
  body.innerHTML = staff.map(staffRow).join('');
}

/* ---------- CRUD: Feature flags ---------- */
function toggleFlag(id){
  const f = flags.find(x=>x.id===id);
  if(!f) return;
  f.on = !f.on;
  renderFlags();
  toast(`${f.name} is now ${f.on?'on':'off'}`, f.on?'ok':'warn');
}
function flagFormHtml(f){
  f = f || {name:'', desc:'', scope:'All tenants', rollout:100};
  return `
    <div class="form-field">
      <label class="form-label" for="f-fname">Flag key</label>
      <input class="form-control" id="f-fname" value="${f.name}" placeholder="e.g. new-inbox-layout" required aria-describedby="err-fname">
      <div class="field-error" id="err-fname" role="alert"><i class="bi bi-exclamation-circle"></i><span>Flag key is required.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-fdesc">Description</label>
      <input class="form-control" id="f-fdesc" value="${f.desc}" placeholder="What does this flag control?">
    </div>
    <div class="form-field">
      <label class="form-label" for="f-fscope">Scope</label>
      <select class="form-select" id="f-fscope">
        ${['All tenants','Beta cohort','Internal QA','Pro & Enterprise'].map(s=>`<option ${s===f.scope?'selected':''}>${s}</option>`).join('')}
      </select>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-frollout">Rollout %</label>
      <input class="form-control" id="f-frollout" type="number" min="0" max="100" value="${f.rollout}">
    </div>`;
}
function openNewFlagModal(){
  closeAllDropdowns();
  openModal(`<h4>New feature flag</h4><div class="modal-sub">Ship a feature behind a flag.</div>
    <form id="flagForm" novalidate>${flagFormHtml()}
      <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-cobalt">Create flag</button></div>
    </form>`);
  wireFlagForm(null);
}
function openEditFlagModal(id){
  closeAllDropdowns();
  const f = flags.find(x=>x.id===id);
  if(!f) return;
  openModal(`<h4>Edit flag</h4><div class="modal-sub">${f.name}</div>
    <form id="flagForm" novalidate>${flagFormHtml(f)}
      <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-cobalt">Save changes</button></div>
    </form>`);
  wireFlagForm(id);
}
function wireFlagForm(editId){
  document.getElementById('flagForm').addEventListener('submit', e=>{
    e.preventDefault();
    const nameEl = document.getElementById('f-fname');
    if(!nameEl.value.trim()){ markInvalid(nameEl, document.getElementById('err-fname'), 'Flag key is required.'); toast('Please fix the errors in the form','error'); return; }
    clearInvalid(nameEl, document.getElementById('err-fname'));
    const name = nameEl.value.trim();
    const desc = document.getElementById('f-fdesc').value.trim();
    const scope = document.getElementById('f-fscope').value;
    const rollout = Number(document.getElementById('f-frollout').value) || 0;
    if(editId){
      Object.assign(flags.find(x=>x.id===editId), {name, desc, scope, rollout});
      toast('Flag updated', 'ok');
    } else {
      flags.unshift({id:uid(), name, desc, scope, rollout, on:false});
      toast('Flag created', 'ok');
    }
    closeModal();
    renderFlags();
  });
}
function confirmDeleteFlag(id){
  const f = flags.find(x=>x.id===id);
  if(!f) return;
  openModal(`<div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Delete ${f.name}?</h4><div class="modal-sub">Any code still checking this flag will fall back to off.</div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="doDeleteFlag('${id}')">Delete flag</button></div>`);
}
function doDeleteFlag(id){
  const f = flags.find(x=>x.id===id);
  flags = flags.filter(x=>x.id!==id);
  closeModal(); renderFlags();
  toast(f ? `${f.name} deleted` : 'Flag deleted', 'warn');
}

/* ---------- CRUD: Coupons ---------- */
function couponFormHtml(c){
  c = c || {code:'', discount:'', applies:'All plans', expires:''};
  return `
    <div class="form-field">
      <label class="form-label" for="f-ccode">Code</label>
      <input class="form-control" id="f-ccode" value="${c.code}" placeholder="e.g. SUMMER25" required aria-describedby="err-ccode" style="text-transform:uppercase;">
      <div class="field-error" id="err-ccode" role="alert"><i class="bi bi-exclamation-circle"></i><span>Coupon code is required.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-cdiscount">Discount</label>
      <input class="form-control" id="f-cdiscount" value="${c.discount}" placeholder="e.g. 20% off first invoice" required aria-describedby="err-cdiscount">
      <div class="field-error" id="err-cdiscount" role="alert"><i class="bi bi-exclamation-circle"></i><span>Describe the discount.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-capplies">Applies to</label>
      <input class="form-control" id="f-capplies" value="${c.applies}" placeholder="e.g. All plans">
    </div>
    <div class="form-field">
      <label class="form-label" for="f-cexpires">Expires</label>
      <input class="form-control" id="f-cexpires" value="${c.expires}" placeholder="e.g. 2026-12-31 or No expiry">
    </div>`;
}
function openNewCouponModal(){
  closeAllDropdowns();
  openModal(`<h4>New coupon</h4><div class="modal-sub">Create a discount code tenants can redeem.</div>
    <form id="couponForm" novalidate>${couponFormHtml()}
      <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-cobalt">Create coupon</button></div>
    </form>`);
  wireCouponForm(null);
}
function openEditCouponModal(id){
  closeAllDropdowns();
  const c = coupons.find(x=>x.id===id);
  if(!c) return;
  openModal(`<h4>Edit coupon</h4><div class="modal-sub">${c.code}</div>
    <form id="couponForm" novalidate>${couponFormHtml(c)}
      <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-cobalt">Save changes</button></div>
    </form>`);
  wireCouponForm(id);
}
function wireCouponForm(editId){
  document.getElementById('couponForm').addEventListener('submit', e=>{
    e.preventDefault();
    const codeEl = document.getElementById('f-ccode'), discEl = document.getElementById('f-cdiscount');
    let ok = true;
    if(!codeEl.value.trim()){ markInvalid(codeEl, document.getElementById('err-ccode'), 'Coupon code is required.'); ok = false; } else clearInvalid(codeEl, document.getElementById('err-ccode'));
    if(!discEl.value.trim()){ markInvalid(discEl, document.getElementById('err-cdiscount'), 'Describe the discount.'); ok = false; } else clearInvalid(discEl, document.getElementById('err-cdiscount'));
    if(!ok){ toast('Please fix the errors in the form', 'error'); return; }
    const code = codeEl.value.trim().toUpperCase();
    const discount = discEl.value.trim();
    const applies = document.getElementById('f-capplies').value.trim() || 'All plans';
    const expires = document.getElementById('f-cexpires').value.trim() || 'No expiry';
    if(editId){
      Object.assign(coupons.find(x=>x.id===editId), {code, discount, applies, expires});
      toast('Coupon updated', 'ok');
    } else {
      coupons.unshift({id:uid(), code, discount, applies, expires, redemptions:0, status:'active'});
      toast('Coupon created', 'ok');
    }
    closeModal();
    renderCoupons();
  });
}
function confirmDeleteCoupon(id){
  const c = coupons.find(x=>x.id===id);
  if(!c) return;
  openModal(`<div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Delete ${c.code}?</h4><div class="modal-sub">Tenants will no longer be able to redeem this code.</div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="doDeleteCoupon('${id}')">Delete coupon</button></div>`);
}
function doDeleteCoupon(id){
  const c = coupons.find(x=>x.id===id);
  coupons = coupons.filter(x=>x.id!==id);
  closeModal(); renderCoupons();
  toast(c ? `${c.code} deleted` : 'Coupon deleted', 'warn');
}

/* ---------- CRUD: Staff (roles & permissions) ---------- */
function staffFormHtml(s){
  s = s || {name:'', email:'', role:'support_staff'};
  return `
    <div class="form-field">
      <label class="form-label" for="f-sname">Full name</label>
      <input class="form-control" id="f-sname" value="${s.name}" placeholder="e.g. Jordan Lee" required aria-describedby="err-sname">
      <div class="field-error" id="err-sname" role="alert"><i class="bi bi-exclamation-circle"></i><span>Name is required.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-semail">Work email</label>
      <input class="form-control" id="f-semail" type="email" value="${s.email}" placeholder="name@loop.chat" required aria-describedby="err-semail">
      <div class="field-error" id="err-semail" role="alert"><i class="bi bi-exclamation-circle"></i><span>Enter a valid email address.</span></div>
    </div>
    <div class="form-field">
      <label class="form-label" for="f-srole">Role</label>
      <select class="form-select" id="f-srole">
        ${Object.keys(roleLabel).map(r=>`<option value="${r}" ${r===s.role?'selected':''}>${roleLabel[r]}</option>`).join('')}
      </select>
    </div>`;
}
function openInviteStaffModal(){
  closeAllDropdowns();
  openModal(`<h4>Invite admin</h4><div class="modal-sub">Grant platform staff access to Control Room.</div>
    <form id="staffForm" novalidate>${staffFormHtml()}
      <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-cobalt">Send invite</button></div>
    </form>`);
  wireStaffForm(null);
}
function openEditStaffModal(id){
  closeAllDropdowns();
  const s = staff.find(x=>x.id===id);
  if(!s) return;
  openModal(`<h4>Edit admin</h4><div class="modal-sub">${s.name}</div>
    <form id="staffForm" novalidate>${staffFormHtml(s)}
      <div class="modal-actions"><button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button><button type="submit" class="btn btn-cobalt">Save changes</button></div>
    </form>`);
  wireStaffForm(id);
}
function wireStaffForm(editId){
  document.getElementById('staffForm').addEventListener('submit', e=>{
    e.preventDefault();
    const nameEl = document.getElementById('f-sname'), emailEl = document.getElementById('f-semail');
    let ok = true;
    if(!nameEl.value.trim()){ markInvalid(nameEl, document.getElementById('err-sname'), 'Name is required.'); ok = false; } else clearInvalid(nameEl, document.getElementById('err-sname'));
    if(!isValidEmail(emailEl.value.trim())){ markInvalid(emailEl, document.getElementById('err-semail'), 'Enter a valid email address.'); ok = false; } else clearInvalid(emailEl, document.getElementById('err-semail'));
    if(!ok){ toast('Please fix the errors in the form', 'error'); return; }
    const name = nameEl.value.trim();
    const email = emailEl.value.trim();
    const role = document.getElementById('f-srole').value;
    const initials = name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase() || 'NA';
    if(editId){
      Object.assign(staff.find(x=>x.id===editId), {name, email, role, initials});
      toast('Admin updated', 'ok');
    } else {
      staff.unshift({id:uid(), name, email, role, initials, status:'active', last:'Just invited'});
      toast('Invite sent', 'ok');
    }
    closeModal();
    renderStaff();
  });
}
function confirmDeleteStaff(id){
  const s = staff.find(x=>x.id===id);
  if(!s) return;
  openModal(`<div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Remove ${s.name}?</h4><div class="modal-sub">They'll immediately lose access to the super admin panel.</div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="doDeleteStaff('${id}')">Remove admin</button></div>`);
}
function doDeleteStaff(id){
  const s = staff.find(x=>x.id===id);
  staff = staff.filter(x=>x.id!==id);
  closeModal(); renderStaff();
  toast(s ? `${s.name} removed` : 'Admin removed', 'warn');
}

/* =========================================================
   Mock data: Onboarding, API keys, Webhooks, Integrations,
   Branding, Support tickets, Data export/GDPR, Notifications
   ========================================================= */
let onboardingTenants = [
  {tenant:'Nova Retail', steps:2, total:6, last:'2 hours ago', status:'progress'},
  {tenant:'Bluepeak Studio', steps:5, total:6, last:'Yesterday', status:'progress'},
  {tenant:'DhakaKart', steps:1, total:6, last:'6 days ago', status:'stalled'},
  {tenant:'Orbit Fitness', steps:6, total:6, last:'Sep 2, 2026', status:'complete'},
];
let apiKeys = [
  {id:'ak1', name:'Server-side integration', key:'sk_live_••••••7Kd2', scope:'Full access', lastUsed:'3 hours ago'},
  {id:'ak2', name:'Analytics export job', key:'sk_live_••••••9Qm1', scope:'Read only', lastUsed:'1 day ago'},
];
let webhooks = [
  {id:'wh1', url:'https://hooks.acme.com/loop/billing', events:'invoice.paid, invoice.failed', lastDelivery:'12 min ago', status:'active'},
  {id:'wh2', url:'https://hooks.harborco.com/loop', events:'tenant.suspended', lastDelivery:'2 days ago', status:'past'},
];
let integrations = [
  {id:'in1', name:'Slack', icon:'bi-slack', desc:'Get platform alerts in a Slack channel.', connected:true},
  {id:'in2', name:'Zapier', icon:'bi-lightning-charge-fill', desc:'Connect Loop to 5,000+ apps.', connected:true},
  {id:'in3', name:'HubSpot', icon:'bi-diagram-3-fill', desc:'Sync leads and contacts.', connected:false},
  {id:'in4', name:'Stripe', icon:'bi-credit-card-2-back-fill', desc:'Payment processing for billing.', connected:true},
  {id:'in5', name:'Mailchimp', icon:'bi-envelope-fill', desc:'Sync newsletter subscribers.', connected:false},
  {id:'in6', name:'Shopify', icon:'bi-bag-fill', desc:'Pull order data into conversations.', connected:false},
];
let brandingTenants = [
  {tenant:'Vertex Freight', domain:'support.vertexfreight.com', logo:'Custom', ssl:'Active', status:'active'},
  {tenant:'Harbor & Co', domain:'help.harborco.com', logo:'Custom', ssl:'Pending', status:'trial'},
];
let tickets = [
  {id:'tk1', subject:'Cannot connect Stripe account', tenant:'Northwind Retail', priority:'High', status:'open', updated:'20 min ago'},
  {id:'tk2', subject:'Question about annual billing', tenant:'BrightBean Coffee', priority:'Low', status:'pending', updated:'3 hours ago'},
  {id:'tk3', subject:'Chat widget not loading on mobile', tenant:'Pixel Studio', priority:'High', status:'open', updated:'1 day ago'},
  {id:'tk4', subject:'Requesting data export', tenant:'Harbor & Co', priority:'Medium', status:'closed', updated:'4 days ago'},
];
let exportRequests = [
  {id:'ex1', tenant:'Cedarline Legal', type:'Full data export', by:'Maya Chen', requested:'Sep 22, 2026', status:'progress'},
  {id:'ex2', tenant:'QuickFix Auto', type:'Account deletion', by:'Support staff', requested:'Sep 18, 2026', status:'pending'},
  {id:'ex3', tenant:'Orbit Fitness', type:'Full data export', by:'Leo Park', requested:'Sep 5, 2026', status:'complete'},
];
let notifCenter = [
  {id:'nc1', icon:'bi-credit-card-fill', title:'Payment failed — Harbor & Co', desc:'Invoice INV-2089 · card declined', time:'2 hours ago', read:false},
  {id:'nc2', icon:'bi-person-plus-fill', title:'New tenant signup', desc:'Bluepeak Studio started a trial', time:'12 minutes ago', read:false},
  {id:'nc3', icon:'bi-arrow-up-circle-fill', title:'Plan upgraded', desc:'Vertex Freight → Enterprise', time:'2 days ago', read:true},
  {id:'nc4', icon:'bi-exclamation-triangle-fill', title:'Email delivery degraded', desc:'Elevated latency on outbound email', time:'Yesterday', read:false},
  {id:'nc5', icon:'bi-headset', title:'New support ticket', desc:'Northwind Retail — Cannot connect Stripe', time:'20 minutes ago', read:true},
];
let logs = [
  {admin:'Bluepeak Studio', type:'Signup', icon:'bi-person-plus-fill', text:'New tenant <b>Bluepeak Studio</b> signed up for a trial', time:'12 minutes ago', date:'2026-09-24'},
  {admin:'System', type:'Billing', icon:'bi-credit-card-fill', text:'Payment failed for <b>Harbor &amp; Co</b> invoice INV-2089', time:'2 hours ago', date:'2026-09-24'},
  {admin:'Sabina Karim', type:'Security', icon:'bi-shield-lock-fill', text:'Super admin <b>Sabina Karim</b> updated 2FA policy', time:'Yesterday, 4:32 PM', date:'2026-09-23'},
  {admin:'Rafiq Hasan', type:'Suspension', icon:'bi-pause-circle-fill', text:'Tenant <b>Cedarline Legal</b> account suspended for non-payment', time:'Yesterday, 11:05 AM', date:'2026-09-23'},
  {admin:'System', type:'Upgrade', icon:'bi-arrow-up-circle-fill', text:'<b>Vertex Freight</b> upgraded from Pro to Enterprise', time:'2 days ago', date:'2026-09-22'},
  {admin:'Anika Islam', type:'Security', icon:'bi-incognito', text:'<b>Anika Islam</b> impersonated Pixel Studio for support', time:'3 days ago', date:'2026-09-21'},
  {admin:'System', type:'Billing', icon:'bi-receipt', text:'Invoice INV-2088 paid by <b>Orbit Fitness</b>', time:'4 days ago', date:'2026-09-20'},
];

/* ---------- Onboarding ---------- */
function onboardingRow(o){
  const pct = Math.round((o.steps/o.total)*100);
  const stMap = {progress:'status-trial', complete:'status-active', stalled:'status-past'};
  const stLabel = {progress:'In progress', complete:'Complete', stalled:'Stalled'};
  return `<tr>
    <td data-label="Tenant" class="cell-main-td">${o.tenant}</td>
    <td data-label="Progress"><div class="progress" style="width:140px;" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:${pct}%"></div></div></td>
    <td data-label="Steps">${o.steps} / ${o.total}</td>
    <td data-label="Last activity">${o.last}</td>
    <td data-label="Status"><span class="status-pill ${stMap[o.status]}">${stLabel[o.status]}</span></td>
  </tr>`;
}
function renderOnboarding(){
  const body = document.getElementById('onboardingBody');
  if(!body) return;
  body.innerHTML = onboardingTenants.map(onboardingRow).join('');
}

/* ---------- API keys ---------- */
function apiKeyRow(k){
  return `<tr data-id="${k.id}">
    <td data-label="Name" class="cell-main-td">${k.name}</td>
    <td data-label="Key"><code style="font-size:.8rem;">${k.key}</code></td>
    <td data-label="Scope">${k.scope}</td>
    <td data-label="Last used">${k.lastUsed}</td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="regenerateKey('${k.id}')" aria-label="Regenerate ${k.name}"><i class="bi bi-arrow-repeat"></i></button>
      <button class="icon-action danger" onclick="confirmRevokeKey('${k.id}')" aria-label="Revoke ${k.name}"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function renderApiKeys(){
  const body = document.getElementById('apiKeysBody');
  if(!body) return;
  if(!apiKeys.length){ body.innerHTML = `<tr><td colspan="5" style="padding:0;">${emptyStateHtml('bi-key','No API keys yet','Create a key to let a service call the Loop API.','New API key','openNewKeyModal()')}</td></tr>`; return; }
  body.innerHTML = apiKeys.map(apiKeyRow).join('');
}
function openNewKeyModal(){
  closeAllDropdowns();
  openModal(`<h4>New API key</h4><div class="modal-sub">Keys are shown once — copy it somewhere safe.</div>
    <div class="form-field"><label class="form-label" for="f-kname">Key name</label><input class="form-control" id="f-kname" placeholder="e.g. Server-side integration" required></div>
    <div class="form-field"><label class="form-label" for="f-kscope">Scope</label><select class="form-select" id="f-kscope">${['Full access','Read only'].map(s=>`<option>${s}</option>`).join('')}</select></div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-cobalt" onclick="createApiKey()">Generate key</button></div>`);
}
function createApiKey(){
  const nameEl = document.getElementById('f-kname');
  if(!nameEl.value.trim()){ toast('Please name the key', 'error'); return; }
  apiKeys.unshift({id:uid(), name:nameEl.value.trim(), key:'sk_live_••••••'+Math.random().toString(36).slice(2,6), scope:document.getElementById('f-kscope').value, lastUsed:'Never'});
  closeModal(); renderApiKeys();
  toast('API key generated', 'ok');
}
function regenerateKey(id){
  const k = apiKeys.find(x=>x.id===id);
  if(!k) return;
  k.key = 'sk_live_••••••'+Math.random().toString(36).slice(2,6);
  renderApiKeys();
  toast(`${k.name} regenerated — update your integration`, 'warn');
}
function confirmRevokeKey(id){
  const k = apiKeys.find(x=>x.id===id);
  if(!k) return;
  openModal(`<div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Revoke ${k.name}?</h4><div class="modal-sub">Any service using this key will immediately lose access.</div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="doRevokeKey('${id}')">Revoke key</button></div>`);
}
function doRevokeKey(id){
  const k = apiKeys.find(x=>x.id===id);
  apiKeys = apiKeys.filter(x=>x.id!==id);
  closeModal(); renderApiKeys();
  toast(k ? `${k.name} revoked` : 'Key revoked', 'warn');
}

/* ---------- Webhooks ---------- */
function webhookRow(w){
  return `<tr data-id="${w.id}">
    <td data-label="URL" class="cell-main-td"><code style="font-size:.8rem;">${w.url}</code></td>
    <td data-label="Events">${w.events}</td>
    <td data-label="Last delivery">${w.lastDelivery}</td>
    <td data-label="Status"><span class="status-pill ${statusMap[w.status]}">${statusLabel[w.status]}</span></td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="sendTestWebhook('${w.id}')" aria-label="Send test event"><i class="bi bi-send"></i></button>
      <button class="icon-action danger" onclick="confirmDeleteWebhook('${w.id}')" aria-label="Delete webhook"><i class="bi bi-trash3"></i></button>
    </div></td>
  </tr>`;
}
function renderWebhooks(){
  const body = document.getElementById('webhooksBody');
  if(!body) return;
  if(!webhooks.length){ body.innerHTML = `<tr><td colspan="5" style="padding:0;">${emptyStateHtml('bi-plug','No webhooks yet','Add a webhook to receive platform events.','New webhook','openNewWebhookModal()')}</td></tr>`; return; }
  body.innerHTML = webhooks.map(webhookRow).join('');
}
function openNewWebhookModal(){
  closeAllDropdowns();
  openModal(`<h4>New webhook</h4><div class="modal-sub">Receive Loop platform events at a URL you control.</div>
    <div class="form-field"><label class="form-label" for="f-wurl">Endpoint URL</label><input class="form-control" id="f-wurl" placeholder="https://example.com/webhook" required></div>
    <div class="form-field"><label class="form-label" for="f-wevents">Events</label><input class="form-control" id="f-wevents" placeholder="e.g. invoice.paid, tenant.suspended"></div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-cobalt" onclick="createWebhook()">Add webhook</button></div>`);
}
function createWebhook(){
  const urlEl = document.getElementById('f-wurl');
  if(!urlEl.value.trim()){ toast('Please add an endpoint URL', 'error'); return; }
  webhooks.unshift({id:uid(), url:urlEl.value.trim(), events:document.getElementById('f-wevents').value.trim()||'all events', lastDelivery:'Never', status:'active'});
  closeModal(); renderWebhooks();
  toast('Webhook added', 'ok');
}
function sendTestWebhook(id){
  const w = webhooks.find(x=>x.id===id);
  toast(w ? `Test event sent to ${w.url}` : 'Test event sent', 'ok');
}
function confirmDeleteWebhook(id){
  const w = webhooks.find(x=>x.id===id);
  if(!w) return;
  openModal(`<div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
    <h4>Delete this webhook?</h4><div class="modal-sub">${w.url}</div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="doDeleteWebhook('${id}')">Delete</button></div>`);
}
function doDeleteWebhook(id){
  webhooks = webhooks.filter(x=>x.id!==id);
  closeModal(); renderWebhooks();
  toast('Webhook deleted', 'warn');
}

/* ---------- Integrations marketplace ---------- */
function integrationCard(i){
  return `<div class="glass stat-card" data-id="${i.id}" style="gap:10px;">
    <div class="stat-icon" style="background:var(--cobalt-soft); color:#7EA1F5;"><i class="bi ${i.icon}"></i></div>
    <div style="font-weight:700;">${i.name}</div>
    <p style="margin:0; color:var(--text-soft); font-size:.82rem; flex:1;">${i.desc}</p>
    <button class="btn ${i.connected?'btn-ghost':'btn-cobalt'} btn-sm" onclick="toggleIntegration('${i.id}')">${i.connected?'Disconnect':'Connect'}</button>
  </div>`;
}
function renderIntegrations(){
  const grid = document.getElementById('integrationsGrid');
  if(!grid) return;
  grid.innerHTML = integrations.map(integrationCard).join('');
}
function toggleIntegration(id){
  const i = integrations.find(x=>x.id===id);
  if(!i) return;
  i.connected = !i.connected;
  renderIntegrations();
  toast(`${i.name} ${i.connected?'connected':'disconnected'}`, i.connected?'ok':'warn');
}

/* ---------- Branding ---------- */
function brandingRow(b){
  return `<tr>
    <td data-label="Tenant" class="cell-main-td">${b.tenant}</td>
    <td data-label="Custom domain"><code style="font-size:.8rem;">${b.domain}</code></td>
    <td data-label="Logo">${b.logo}</td>
    <td data-label="SSL">${b.ssl}</td>
    <td data-label="Status"><span class="status-pill ${statusMap[b.status]}">${statusLabel[b.status]}</span></td>
  </tr>`;
}
function renderBranding(){
  const body = document.getElementById('brandingBody');
  if(!body) return;
  if(!brandingTenants.length){ body.innerHTML = `<tr><td colspan="5" style="padding:0;">${emptyStateHtml('bi-palette','No custom domains yet','Enterprise tenants can request white-label branding.')}</td></tr>`; return; }
  body.innerHTML = brandingTenants.map(brandingRow).join('');
}

/* ---------- Support tickets ---------- */
const ticketStatusMap = {open:'status-past', pending:'status-trial', closed:'status-active'};
const ticketStatusLabel = {open:'Open', pending:'Pending', closed:'Closed'};
function ticketRow(t){
  return `<tr data-id="${t.id}">
    <td data-label="Subject" class="cell-main-td">${t.subject}</td>
    <td data-label="Tenant">${t.tenant}</td>
    <td data-label="Priority">${t.priority}</td>
    <td data-label="Status"><span class="status-pill ${ticketStatusMap[t.status]}">${ticketStatusLabel[t.status]}</span></td>
    <td data-label="Updated">${t.updated}</td>
    <td data-label="Actions"><div class="row-actions">
      <button class="icon-action" onclick="openTicketModal('${t.id}')" aria-label="Open ${t.subject}"><i class="bi bi-eye"></i></button>
      ${t.status!=='closed' ? `<button class="icon-action" onclick="closeTicket('${t.id}')" aria-label="Close ticket"><i class="bi bi-check2"></i></button>` : ''}
    </div></td>
  </tr>`;
}
function renderTickets(filterText){
  const body = document.getElementById('ticketsBody');
  if(!body) return;
  let list = tickets;
  if(filterText){
    const q = filterText.toLowerCase();
    list = tickets.filter(t=> t.subject.toLowerCase().includes(q) || t.tenant.toLowerCase().includes(q));
  }
  if(!list.length){ body.innerHTML = `<tr><td colspan="6" style="padding:0;">${emptyStateHtml('bi-headset','No tickets found','Try a different search term.')}</td></tr>`; return; }
  body.innerHTML = list.map(ticketRow).join('');
}
function openTicketModal(id){
  const t = tickets.find(x=>x.id===id);
  if(!t) return;
  openModal(`<h4>${t.subject}</h4><div class="modal-sub">${t.tenant} · ${t.priority} priority</div>
    <div class="detail-row"><span class="k">Status</span><span>${ticketStatusLabel[t.status]}</span></div>
    <div class="detail-row"><span class="k">Last updated</span><span>${t.updated}</span></div>
    <div class="form-field"><label class="form-label" for="f-treply">Reply</label><textarea class="form-control" id="f-treply" rows="3" placeholder="Type a reply…"></textarea></div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Close</button><button class="btn btn-cobalt" onclick="sendTicketReply('${t.id}')">Send reply</button></div>`);
}
function sendTicketReply(id){
  const t = tickets.find(x=>x.id===id);
  if(t){ t.status = 'pending'; t.updated = 'Just now'; }
  closeModal(); renderTickets();
  toast('Reply sent', 'ok');
}
function closeTicket(id){
  const t = tickets.find(x=>x.id===id);
  if(!t) return;
  t.status = 'closed'; t.updated = 'Just now';
  renderTickets();
  toast(`${t.subject} closed`, 'ok');
}

/* ---------- Data export / GDPR ---------- */
const exportStatusMap = {progress:'status-trial', pending:'status-past', complete:'status-active'};
const exportStatusLabel = {progress:'In progress', pending:'Pending review', complete:'Complete'};
function exportRow(e){
  return `<tr data-id="${e.id}">
    <td data-label="Tenant" class="cell-main-td">${e.tenant}</td>
    <td data-label="Type">${e.type}</td>
    <td data-label="Requested by">${e.by}</td>
    <td data-label="Requested">${e.requested}</td>
    <td data-label="Status"><span class="status-pill ${exportStatusMap[e.status]}">${exportStatusLabel[e.status]}</span></td>
    <td data-label="Actions">${e.status!=='complete' ? `<button class="btn btn-ghost btn-sm" onclick="completeExportRequest('${e.id}')">Mark complete</button>` : ''}</td>
  </tr>`;
}
function renderExportRequests(){
  const body = document.getElementById('exportBody');
  if(!body) return;
  if(!exportRequests.length){ body.innerHTML = `<tr><td colspan="6" style="padding:0;">${emptyStateHtml('bi-shield-check','No requests yet','Export and deletion requests will show up here.','New request','openNewExportModal()')}</td></tr>`; return; }
  body.innerHTML = exportRequests.map(exportRow).join('');
}
function openNewExportModal(){
  closeAllDropdowns();
  openModal(`<h4>New request</h4><div class="modal-sub">Log a data export or account deletion request.</div>
    <div class="form-field"><label class="form-label" for="f-etenant">Tenant</label><select class="form-select" id="f-etenant">${tenants.map(t=>`<option>${t.name}</option>`).join('')}</select></div>
    <div class="form-field"><label class="form-label" for="f-etype">Request type</label><select class="form-select" id="f-etype">${['Full data export','Account deletion'].map(t=>`<option>${t}</option>`).join('')}</select></div>
    <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-cobalt" onclick="createExportRequest()">Log request</button></div>`);
}
function createExportRequest(){
  const tenant = document.getElementById('f-etenant').value;
  const type = document.getElementById('f-etype').value;
  exportRequests.unshift({id:uid(), tenant, type, by:'Super Admin', requested:new Date().toISOString().slice(0,10), status:'pending'});
  closeModal(); renderExportRequests();
  toast('Request logged', 'ok');
}
function completeExportRequest(id){
  const e = exportRequests.find(x=>x.id===id);
  if(!e) return;
  e.status = 'complete';
  renderExportRequests();
  toast(`${e.type} for ${e.tenant} marked complete`, 'ok');
}

/* ---------- Notifications center ---------- */
function notifCenterItem(n){
  return `<div class="dd-item" style="cursor:default; ${n.read?'':'background:var(--glass-strong);'}" data-id="${n.id}">
    <i class="bi ${n.icon}" aria-hidden="true"></i>
    <span style="flex:1;"><span class="t" style="display:block;">${n.title}</span><span class="s">${n.desc} · ${n.time}</span></span>
    ${!n.read ? `<button class="icon-action" style="width:28px;height:28px;flex-shrink:0;" onclick="markNotifRead('${n.id}')" aria-label="Mark as read"><i class="bi bi-check2"></i></button>` : ''}
  </div>`;
}
function renderNotifCenter(){
  const list = document.getElementById('notifCenterList');
  if(!list) return;
  if(!notifCenter.length){ list.innerHTML = emptyStateHtml('bi-bell','All caught up','New platform notifications will appear here.'); return; }
  list.innerHTML = notifCenter.map(notifCenterItem).join('');
}
function markNotifRead(id){
  const n = notifCenter.find(x=>x.id===id);
  if(n) n.read = true;
  renderNotifCenter();
}
function markAllRead(){
  notifCenter.forEach(n=> n.read = true);
  renderNotifCenter();
  toast('All notifications marked read', 'ok');
}

/* ---------- Audit log filtering ---------- */
function logEntryHtml(l){
  return `<div class="d-flex gap-3 align-items-start" style="padding:12px 0; border-bottom:1px solid var(--border-soft);">
    <div class="stat-icon" style="width:34px;height:34px;font-size:.85rem;background:var(--cobalt-soft); color:#7EA1F5; margin-bottom:0;" aria-hidden="true"><i class="bi ${l.icon}"></i></div>
    <div style="flex:1;"><div style="font-size:.87rem;">${l.text}</div><div class="cell-sub">${l.admin} · ${l.type} · ${l.time}</div></div>
  </div>`;
}
function renderLogs(list){
  const el = document.getElementById('logsList');
  if(!el) return;
  list = list || logs;
  if(!list.length){ el.innerHTML = emptyStateHtml('bi-terminal','No matching activity','Try widening your filters.','Reset filters','resetLogFilters()'); return; }
  el.innerHTML = list.map(logEntryHtml).join('');
}
function applyLogFilters(){
  const admin = document.getElementById('logAdminFilter').value;
  const type = document.getElementById('logActionFilter').value;
  const from = document.getElementById('logDateFrom').value;
  const to = document.getElementById('logDateTo').value;
  let list = logs;
  if(admin) list = list.filter(l=> l.admin === admin);
  if(type) list = list.filter(l=> l.type === type);
  if(from) list = list.filter(l=> l.date >= from);
  if(to) list = list.filter(l=> l.date <= to);
  renderLogs(list);
}
function resetLogFilters(){
  ['logAdminFilter','logActionFilter','logDateFrom','logDateTo'].forEach(id=>{ const el = document.getElementById(id); if(el) el.value = ''; });
  renderLogs();
}

/* =========================================================
   Read-only detail modals (notifications, invoices)
   ========================================================= */
const NOTIF_DETAILS = {
  'notif-1': {title:'Payment failed', rows:[['Tenant','Harbor & Co'],['Invoice','INV-2089'],['Amount','$29.00'],['Reason','Card declined']]},
  'notif-2': {title:'New tenant signup', rows:[['Tenant','Bluepeak Studio'],['Plan','Growth (trial)'],['Started','12 minutes ago']]},
  'notif-3': {title:'Plan upgraded', rows:[['Tenant','Vertex Freight'],['From','Pro'],['To','Enterprise'],['When','2 days ago']]},
};
function openDetailModal(key){
  closeAllDropdowns();
  const d = NOTIF_DETAILS[key];
  if(!d) return;
  openModal(`
    <h4>${d.title}</h4>
    <div class="modal-sub">Notification details</div>
    ${d.rows.map(r=>`<div class="detail-row"><span class="k">${r[0]}</span><span>${r[1]}</span></div>`).join('')}
    <div class="modal-actions"><button class="btn btn-cobalt" onclick="closeModal()">Close</button></div>
  `);
}
function openSupportModal(e){
  if(e) e.preventDefault();
  closeAllDropdowns();
  const sidebar = document.querySelector('.sidebar');
  if(sidebar && sidebar.classList.contains('open')){
    sidebar.classList.remove('open');
    const scrim = document.getElementById('sidebarScrim');
    if(scrim) scrim.classList.remove('open');
  }
  openModal(`
    <h4>Contact support</h4>
    <div class="modal-sub">We usually reply within a few hours.</div>
    <form id="supportForm" novalidate>
      <div class="form-field">
        <label class="form-label" for="f-sup-subject">Subject</label>
        <input class="form-control" id="f-sup-subject" placeholder="What's this about?" required aria-describedby="err-sup-subject">
        <div class="field-error" id="err-sup-subject" role="alert"><i class="bi bi-exclamation-circle"></i><span>Please add a subject.</span></div>
      </div>
      <div class="form-field">
        <label class="form-label" for="f-sup-msg">Message</label>
        <textarea class="form-control" id="f-sup-msg" rows="4" placeholder="Describe the issue…" required aria-describedby="err-sup-msg"></textarea>
        <div class="field-error" id="err-sup-msg" role="alert"><i class="bi bi-exclamation-circle"></i><span>Please add a short message.</span></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-cobalt">Send message</button>
      </div>
    </form>
  `);
  const form = document.getElementById('supportForm');
  form.addEventListener('submit', ev=>{
    ev.preventDefault();
    const subj = document.getElementById('f-sup-subject');
    const msg = document.getElementById('f-sup-msg');
    let ok = true;
    if(!subj.value.trim()){ markInvalid(subj, document.getElementById('err-sup-subject'), 'Please add a subject.'); ok = false; } else clearInvalid(subj, document.getElementById('err-sup-subject'));
    if(!msg.value.trim()){ markInvalid(msg, document.getElementById('err-sup-msg'), 'Please add a short message.'); ok = false; } else clearInvalid(msg, document.getElementById('err-sup-msg'));
    if(!ok){ toast('Please fill in the required fields', 'error'); return; }
    closeModal();
    toast('Message sent to support', 'ok');
  });
}

function openRecordModal(type, key){
  if(type === 'invoice'){
    const d = invoices.find(i=>i.id===key);
    if(!d) return;
    openModal(`<h4>${d.id}</h4><div class="modal-sub">Invoice</div>
      <div class="detail-row"><span class="k">Tenant</span><span>${d.tenant}</span></div>
      <div class="detail-row"><span class="k">Amount</span><span>${d.amount}</span></div>
      <div class="detail-row"><span class="k">Date</span><span>${d.date}</span></div>
      <div class="detail-row"><span class="k">Status</span><span>${d.status==='active'?'Paid':'Failed'}</span></div>
      <div class="modal-actions"><button class="btn btn-ghost" onclick="closeModal()">Close</button><button class="btn btn-cobalt" onclick="toast('Downloading receipt…','ok')">Download PDF</button></div>`);
  }
}

/* =========================================================
   Boot
   ========================================================= */
document.addEventListener('DOMContentLoaded', ()=>{
  const savedTheme = localStorage.getItem('loop-theme') || 'dark';
  applyTheme(savedTheme);
  const themeBtn = document.getElementById('themeToggleBtn');
  if(themeBtn){
    themeBtn.addEventListener('click', ()=>{
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }

  wireMobileNav();
  wireDropdown('notifBtn', 'notifPanel');
  wireDropdown('helpBtn', 'helpPanel');
  wireDropdown('avatarBtn', 'avatarPanel');
  document.addEventListener('click', ()=> closeAllDropdowns());

  const overlay = document.getElementById('modalOverlay');
  if(overlay) overlay.addEventListener('click', e=>{ if(e.target === overlay) closeModal(); });

  document.querySelectorAll('.toggle-switch').forEach(t=>{
    t.addEventListener('click', ()=>{ t.classList.toggle('on'); toast('Setting updated', 'ok'); });
  });

  /* ---- Skeleton-first data mount (simulated network delay) ---- */
  const hasTenantsTable = document.getElementById('tenantsBody');
  const hasDashboard = document.getElementById('recentTenantsBody');
  const hasUsersTable = document.getElementById('usersBody');
  const hasSubsTable = document.getElementById('subsBody');
  const hasBillingTable = document.getElementById('billingBody');
  const hasLogs = document.getElementById('logsList');
  const hasFlagsTable = document.getElementById('flagsBody');
  const hasCouponsTable = document.getElementById('couponsBody');
  const hasStaffTable = document.getElementById('staffBody');
  const hasOnboarding = document.getElementById('onboardingBody');
  const hasApiKeys = document.getElementById('apiKeysBody');
  const hasWebhooks = document.getElementById('webhooksBody');
  const hasIntegrations = document.getElementById('integrationsGrid');
  const hasBranding = document.getElementById('brandingBody');
  const hasTickets = document.getElementById('ticketsBody');
  const hasExport = document.getElementById('exportBody');
  const hasNotifCenter = document.getElementById('notifCenterList');

  if(hasTenantsTable) hasTenantsTable.innerHTML = skeletonRows(8, 5);
  if(hasDashboard) hasDashboard.innerHTML = skeletonRows(6, 4);
  if(hasUsersTable) hasUsersTable.innerHTML = skeletonRows(6, 5);
  if(hasSubsTable) hasSubsTable.innerHTML = skeletonRows(6, 4);
  if(hasBillingTable) hasBillingTable.innerHTML = skeletonRows(6, 4);
  if(hasLogs) hasLogs.innerHTML = skeletonLogs(5);
  if(hasFlagsTable) hasFlagsTable.innerHTML = skeletonRows(6, 5);
  if(hasCouponsTable) hasCouponsTable.innerHTML = skeletonRows(7, 4);
  if(hasStaffTable) hasStaffTable.innerHTML = skeletonRows(5, 4);
  if(hasOnboarding) hasOnboarding.innerHTML = skeletonRows(5, 4);
  if(hasApiKeys) hasApiKeys.innerHTML = skeletonRows(5, 2);
  if(hasWebhooks) hasWebhooks.innerHTML = skeletonRows(5, 2);
  if(hasBranding) hasBranding.innerHTML = skeletonRows(5, 2);
  if(hasTickets) hasTickets.innerHTML = skeletonRows(6, 4);
  if(hasExport) hasExport.innerHTML = skeletonRows(6, 3);
  if(hasNotifCenter) hasNotifCenter.innerHTML = skeletonLogs(5);

  setTimeout(()=>{
    if(hasTenantsTable) renderTenants();
    if(hasDashboard) hasDashboard.innerHTML = tenants.slice(0,4).map(recentTenantRow).join('');
    if(hasUsersTable) renderUsers();
    if(hasSubsTable) renderSubs();
    if(hasBillingTable) renderBilling();
    if(hasFlagsTable) renderFlags();
    if(hasCouponsTable) renderCoupons();
    if(hasStaffTable) renderStaff();
    if(hasLogs) renderLogs();
    if(hasOnboarding) renderOnboarding();
    if(hasApiKeys) renderApiKeys();
    if(hasWebhooks) renderWebhooks();
    if(hasIntegrations) renderIntegrations();
    if(hasBranding) renderBranding();
    if(hasTickets) renderTickets();
    if(hasExport) renderExportRequests();
    if(hasNotifCenter) renderNotifCenter();
  }, 650);

  const tenantSearch = document.getElementById('tenantSearch');
  if(tenantSearch) tenantSearch.addEventListener('input', e=> renderTenants(e.target.value));
  const userSearch = document.getElementById('userSearch');
  if(userSearch) userSearch.addEventListener('input', e=> renderUsers(e.target.value));
  const couponSearch = document.getElementById('couponSearch');
  if(couponSearch) couponSearch.addEventListener('input', e=> renderCoupons(e.target.value));
  const ticketSearch = document.getElementById('ticketSearch');
  if(ticketSearch) ticketSearch.addEventListener('input', e=> renderTickets(e.target.value));
});
