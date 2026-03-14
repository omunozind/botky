// Recuperar variables pasadas desde PHP
const wpAjaxUrl = i360Settings.ajaxUrl;
const wpNonce = i360Settings.nonce;
const hasApiKey = i360Settings.hasApiKey;

// �9�1 ATAJO PARA EL DICCIONARIO BILING�0�5E
const t = i360Settings.i18n; 

// ==========================================
// 0. INICIALIZACI�0�7N ROBUSTA DEL SALDO
// ==========================================
let globalWalletBalance = 0;

function initializeBalance() {
    const walletEl = document.getElementById('wallet-display');
    if (walletEl) {
        let cleanText = walletEl.innerText.replace(/[^0-9.-]+/g,"");
        globalWalletBalance = parseFloat(cleanText);
    } else {
        globalWalletBalance = parseFloat(i360Settings.currentBalance) || 0;
    }
}

let workspaces = [];
let availablePlans = [];
let availableAddons = [];
let clients = [];

// DATOS DE RESPALDO
const fallbackAddons = [
    { id: "bot", name: "Extra Bot", price: 10, points: 15 },
    { id: "member", name: "Extra Member", price: 10, points: 15 },
    { id: "bot_user", name: "1,000 Bot Users", price: 10, points: 15 },
    { id: "bot_user_large", name: "10,000 Bot Users", price: 60, points: 120 },
    { id: "lists", name: "Tickets/Lists", price: 30, points: 30 }
];

const WORKSPACE_COST = 20; 
const FREE_PLAN_ID = 'free';

// VARIABLES DE PAGINACI�0�7N
let wsCurrentPage = 1, wsLastPage = 1;
let clCurrentPage = 1, clLastPage = 1;

let currentEditingId = null;
let currentWSData = null; 
let pendingChanges = {}; 
let pointsDelta = 0; 

function updateIcons() { if(typeof lucide !== 'undefined') lucide.createIcons(); }

// ==========================================
// 1. SISTEMA DE UI "LINDO" (BILING�0�5E)
// ==========================================

function ensureContainerExists() {
    if (!document.getElementById('toastContainer')) {
        const toastDiv = document.createElement('div');
        toastDiv.id = 'toastContainer';
        toastDiv.style.cssText = "position: fixed; top: 20px; right: 20px; z-index: 9999999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;";
        document.body.appendChild(toastDiv);
    }
}

window.showToast = (message, type = 'success') => {
    ensureContainerExists();
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    let bg = '#ffffff', border = '#e2e8f0', iconColor = '#22c55e', iconSvg = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>';
    if(type === 'error') { border = '#fee2e2'; iconColor = '#ef4444'; iconSvg = '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'; }
    if(type === 'info') { border = '#e0e7ff'; iconColor = '#6366f1'; iconSvg = '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>'; }
    toast.style.cssText = `pointer-events: auto; display: flex; align-items: center; gap: 12px; padding: 16px; background: ${bg}; border: 1px solid ${border}; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); min-width: 300px; animation: slideIn 0.3s ease-out; margin-bottom: 10px; font-family: sans-serif;`;
    toast.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconSvg}</svg><p style="margin: 0; font-size: 14px; font-weight: 600; color: #1e293b;">${message}</p>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; setTimeout(() => toast.remove(), 300); }, 3000);
}

// ACTUALIZAR SALDO VISUALMENTE
function updateWalletUI(deductionAmount) {
    if (!deductionAmount || deductionAmount <= 0) return;
    
    globalWalletBalance = globalWalletBalance - deductionAmount;
    
    const walletEl = document.getElementById('wallet-display');
    if (walletEl) {
        walletEl.innerText = globalWalletBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        walletEl.style.color = '#dc2626'; // Flash rojo
        walletEl.style.transition = 'color 0.5s ease';
        setTimeout(() => { walletEl.style.color = ''; }, 1500);
    }
}

window.showResourceError = (type, required, available) => {
    const old = document.getElementById('resourceErrorModal'); if (old) old.remove();
    let title, msg, ic, bg, rl, al;
    if (type === 'balance') {
        title = t.insufficient_balance; 
        msg = t.insufficient_desc_1 + "$" + required.toFixed(2) + t.insufficient_desc_2 + "$" + available.toFixed(2) + ".";
        ic = "#dc2626"; bg = "#fee2e2"; rl = t.cost_of_operation; al = t.available_balance;
        required = "$" + required.toFixed(2); available = "$" + available.toFixed(2);
    } else {
        title = t.insufficient_points; msg = t.points_error_msg;
        ic = "#d97706"; bg = "#fef3c7"; rl = t.required_points; al = t.current_points;
        required = required + " pts"; available = available + " pts";
    }
    const h = `<div id="resourceErrorModal" class="fixed inset-0 z-[99999] flex items-center justify-center p-4"><div style="position:absolute;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);" onclick="document.getElementById('resourceErrorModal').remove()"></div><div style="background:white;border-radius:16px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);width:100%;max-width:400px;padding:24px;position:relative;z-index:10;animation:modalPop 0.3s ease-out;"><div style="text-align:center;margin-bottom:20px;"><div style="width:56px;height:56px;background:${bg};color:${ic};border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div><h3 style="font-size:20px;font-weight:800;color:#1e293b;margin-bottom:8px;">${title}</h3><p style="font-size:14px;color:#64748b;line-height:1.5;">${msg}</p></div><div style="background:#f8fafc;border-radius:12px;padding:16px;margin-bottom:24px;border:1px solid #e2e8f0;"><div style="display:flex;justify-content:space-between;margin-bottom:8px;"><span style="font-size:13px;font-weight:600;color:#64748b;">${rl}:</span><span style="font-size:14px;font-weight:700;color:${type==='balance'?'#dc2626':'#d97706'};">${required}</span></div><div style="width:100%;height:1px;background:#cbd5e1;margin:8px 0;"></div><div style="display:flex;justify-content:space-between;"><span style="font-size:13px;font-weight:600;color:#64748b;">${al}:</span><span style="font-size:14px;font-weight:700;color:#16a34a;">${available}</span></div></div><button onclick="document.getElementById('resourceErrorModal').remove()" style="width:100%;padding:12px;background:#1e293b;color:white;font-weight:700;border-radius:10px;border:none;cursor:pointer;transition:background 0.2s;">${t.understood}</button></div></div>`;
    document.body.insertAdjacentHTML('beforeend', h);
}

window.showConfirmationSummary = (htmlList, totalCost, totalPointsRequired) => {
    const old = document.getElementById('confirmationSummaryModal'); if (old) old.remove();
    const costHtml = totalCost > 0 ? `<div style="display:flex;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid #e2e8f0;"><span style="font-weight:600;color:#1e293b;">${t.total_to_pay}</span><span style="font-weight:700;color:#dc2626;">$${totalCost.toFixed(2)}</span></div>` : '';
    const pointsHtml = totalPointsRequired > 0 ? `<div style="display:flex;justify-content:space-between;margin-top:5px;"><span style="font-weight:600;color:#64748b;">${t.points_required}</span><span style="font-weight:700;color:#d97706;">${totalPointsRequired} pts</span></div>` : '';
    const html = `<div id="confirmationSummaryModal" class="fixed inset-0 z-[99999] flex items-center justify-center p-4"><div style="position:absolute;inset:0;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);" onclick="document.getElementById('confirmationSummaryModal').remove()"></div><div style="background:white;border-radius:16px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);width:100%;max-width:420px;padding:24px;position:relative;z-index:10;animation:modalPop 0.3s ease-out;"><div style="text-align:center;margin-bottom:20px;"><div style="width:56px;height:56px;background:#e0e7ff;color:#4f46e5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div><h3 style="font-size:20px;font-weight:800;color:#1e293b;margin-bottom:8px;">${t.confirm_changes}</h3><p style="font-size:14px;color:#64748b;">${t.confirm_desc}</p></div><div style="background:#f8fafc;border-radius:12px;padding:16px;margin-bottom:24px;border:1px solid #e2e8f0;text-align:left;"><div style="font-size:13px;color:#334155;display:flex;flex-direction:column;gap:8px;">${htmlList}</div>${costHtml} ${pointsHtml}</div><div style="display:flex;gap:12px;"><button onclick="document.getElementById('confirmationSummaryModal').remove()" style="flex:1;padding:12px;background:#f1f5f9;color:#475569;font-weight:700;border-radius:10px;border:none;cursor:pointer;transition:background 0.2s;">${t.cancel}</button><button id="btnFinalConfirm" style="flex:1;padding:12px;background:#4f46e5;color:white;font-weight:700;border-radius:10px;border:none;cursor:pointer;transition:background 0.2s;box-shadow:0 4px 6px -1px rgba(79,70,229,0.2);">${t.confirm_and_save}</button></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    document.getElementById('btnFinalConfirm').onclick = () => { executeSaveChanges(); };
}

window.injectAndShowModal = (title, message, type = 'info', onConfirmCallback = null) => {
    const old = document.getElementById('dynamicGenericModal'); if (old) old.remove();
    let btnColor = `bg-blue-600 hover:bg-blue-700`; if (type === 'confirm') btnColor = `bg-red-600 hover:bg-red-700`; 
    const html = `<div id="dynamicGenericModal" class="fixed inset-0 z-[99999] flex items-center justify-center p-4"><div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('dynamicGenericModal').remove()"></div><div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 relative z-10" style="animation:modalPop 0.3s ease-out;"><div class="text-center"><h3 class="text-xl font-bold text-slate-800 mb-2">${title}</h3><p class="text-slate-500 text-sm mb-6">${message}</p><div class="flex gap-3 w-full">${type === 'confirm' ? `<button onclick="document.getElementById('dynamicGenericModal').remove()" class="flex-1 py-2 bg-slate-100 font-bold rounded-lg text-slate-600 cursor-pointer">${t.cancel}</button>` : ''}<button id="btnGenericConfirm" class="flex-1 py-2 ${btnColor} text-white font-bold rounded-lg cursor-pointer">${type === 'confirm' ? t.confirm : t.accept}</button></div></div></div></div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    document.getElementById('btnGenericConfirm').onclick = () => { document.getElementById('dynamicGenericModal').remove(); if (onConfirmCallback) onConfirmCallback(); };
}

// ==========================================
// DATA LOADING
// ==========================================
async function fetchWorkspaces(page = 1) {
    if (!hasApiKey) { renderTable(); return; }
    const icon = document.getElementById('refreshIcon'); if(icon) icon.classList.add('loading-spin');
    const searchVal = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    const fd = new FormData(); fd.append('action', 'i360_get_workspaces'); fd.append('security', wpNonce); fd.append('page', page); fd.append('search', searchVal);
    try {
        const res = await fetch(wpAjaxUrl, { method: 'POST', body: fd }); const json = await res.json();
        if(json.success) {
            const d = json.data; workspaces = Array.isArray(d.data) ? d.data : [];
            if(d.meta) { wsCurrentPage = d.meta.current_page || 1; wsLastPage = d.meta.last_page || 1; updatePaginationUI(d.meta); } 
            else { document.getElementById('paginationContainer').classList.add('hidden'); }
        }
    } catch(e) { console.error(e); } finally { if(icon) icon.classList.remove('loading-spin'); renderTable(); }
}

async function fetchAddons() {
    const fd = new FormData(); fd.append('action', 'i360_get_addons'); fd.append('security', wpNonce);
    try { 
        const res = await fetch(wpAjaxUrl, { method: 'POST', body: fd }); const json = await res.json(); 
        if(json.success && json.data && Array.isArray(json.data.data)) {
            availableAddons = json.data.data.filter(a => a.status == 1 && a.id !== 'custom_domain').map(a => {
                a.name = a.name || a.display_name || a.label || a.id; return a;
            });
        }
    } catch(e) { console.error("Error fetching addons:", e); }
}

async function fetchPlans() {
    const fd = new FormData(); fd.append('action', 'i360_get_plans'); fd.append('security', wpNonce);
    try { 
        const res = await fetch(wpAjaxUrl, { method: 'POST', body: fd }); const json = await res.json(); 
        if(json.success && json.data && Array.isArray(json.data.data)) {
            availablePlans = json.data.data.filter(p => p.status === 'active');
        }
    } catch(e) { console.error(e); } 
}

async function fetchClients(page=1){ if(!hasApiKey)return; const icon=document.getElementById('refreshClientIcon');if(icon)icon.classList.add('loading-spin');const searchVal=document.getElementById('searchClientInput')?document.getElementById('searchClientInput').value:'';const fd=new FormData();fd.append('action','i360_get_clients');fd.append('security',wpNonce);fd.append('page',page);fd.append('search',searchVal);try{const res=await fetch(wpAjaxUrl,{method:'POST',body:fd});const json=await res.json();if(json.success){clients=json.data.data||[];if(json.data.meta){clCurrentPage=json.data.meta.current_page||1;clLastPage=json.data.meta.last_page||1;updateClientPaginationUI(json.data.meta);}else{document.getElementById('paginationContainerClients').classList.add('hidden');}}}catch(e){}finally{if(icon)icon.classList.remove('loading-spin');renderClientsTable();} }

// ... UI HELPERS ...
function updatePointsDelta() { pointsDelta = parseFloat(document.getElementById('pointsInput').value) || 0; }
function updatePaginationUI(meta) { 
    const c = document.getElementById('paginationContainer'); const info = document.getElementById('paginationInfo'); const ctrls = document.getElementById('paginationControls');
    if(meta.total > 0) {
        c.classList.remove('hidden'); info.innerText = `${t.showing} ${meta.from}-${meta.to} ${t.of} ${meta.total}`;
        let html = `<button onclick="changePage(${meta.current_page - 1})" ${meta.current_page <= 1 ? 'disabled' : ''} class="px-3 py-1 bg-white border border-slate-300 text-slate-600 rounded hover:bg-slate-50 text-xs font-bold cursor-pointer disabled:opacity-50">${t.prev}</button>`;
        for(let i=1; i<=meta.last_page; i++) { if(i===1||i===meta.last_page||(i>=meta.current_page-1&&i<=meta.current_page+1)){ let active = i===meta.current_page?'bg-indigo-600 text-white':'bg-white text-slate-600'; html += `<button onclick="changePage(${i})" class="px-3 py-1 border rounded text-xs font-bold cursor-pointer ${active}">${i}</button>`; } else if(i===meta.current_page-2||i===meta.current_page+2) html+=`<span class="px-2 text-slate-400">...</span>`; } 
        html += `<button onclick="changePage(${meta.current_page + 1})" ${meta.current_page >= meta.last_page ? 'disabled' : ''} class="px-3 py-1 bg-white border border-slate-300 text-slate-600 rounded hover:bg-slate-50 text-xs font-bold cursor-pointer disabled:opacity-50">${t.next}</button>`;
        ctrls.innerHTML = html; 
    } else c.classList.add('hidden'); 
}
function changePage(p) { if (p >= 1 && p <= wsLastPage) fetchWorkspaces(p); }
function updateClientPaginationUI(meta) { 
    const c = document.getElementById('paginationContainerClients'); const info = document.getElementById('paginationInfoClients'); const ctrls = document.getElementById('paginationControlsClients');
    if(meta.total > 0) {
        c.classList.remove('hidden'); info.innerText = `${t.showing} ${meta.from}-${meta.to} ${t.of} ${meta.total}`; 
        let html = `<button onclick="changeClientPage(${meta.current_page - 1})" ${meta.current_page <= 1 ? 'disabled' : ''} class="px-3 py-1 bg-white border border-slate-300 text-slate-600 rounded hover:bg-slate-50 text-xs font-bold cursor-pointer disabled:opacity-50">${t.prev}</button>`; 
        for(let i=1; i<=meta.last_page; i++) { if(i===1||i===meta.last_page||(i>=meta.current_page-1&&i<=meta.current_page+1)){ let active = i===meta.current_page?'bg-indigo-600 text-white':'bg-white text-slate-600'; html += `<button onclick="changeClientPage(${i})" class="px-3 py-1 border rounded text-xs font-bold cursor-pointer ${active}">${i}</button>`; } else if(i===meta.current_page-2||i===meta.current_page+2) html+=`<span class="px-2 text-slate-400">...</span>`; } 
        html += `<button onclick="changeClientPage(${meta.current_page + 1})" ${meta.current_page >= meta.last_page ? 'disabled' : ''} class="px-3 py-1 bg-white border border-slate-300 text-slate-600 rounded hover:bg-slate-50 text-xs font-bold cursor-pointer disabled:opacity-50">${t.next}</button>`;
        ctrls.innerHTML = html;
    } else c.classList.add('hidden'); 
}
function changeClientPage(p) { if(p >= 1 && p <= clLastPage) fetchClients(p); }

function renderTable() { 
    const tbody = document.getElementById('tableBody'); const empty = document.getElementById('emptyState'); tbody.innerHTML = ''; 
    if(workspaces.length === 0) { document.getElementById('paginationContainer').classList.add('hidden'); empty.classList.remove('hidden'); return; }
    empty.classList.add('hidden');
    tbody.innerHTML = workspaces.map(ws => {
        const use = ws.bot_user_limit > 0 ? Math.min(100, (ws.bot_user_used/ws.bot_user_limit)*100) : 0;
        const end = ws.billing_end_at ? dayjs(ws.billing_end_at).format('DD MMM YYYY') : '-';
        let ad = []; if(ws.addon_bot > 0) ad.push(ws.addon_bot + ' Bot'); if(ws.addon_member > 0) ad.push(ws.addon_member + ' Mem'); if(ws.addon_bot_user > 0) ad.push(Math.round(ws.addon_bot_user/1000) + 'k Usr'); if(ws.addon_lists > 0) ad.push('List');
        return `<tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0"><td class="px-6 py-4 font-mono text-xs text-slate-500">#${ws.id}</td><td class="px-6 py-4"><p class="text-sm font-bold text-slate-800">${ws.name}</p><p class="text-xs text-slate-500">${ws.owner_name||''}</p></td><td class="px-6 py-4"><p class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded inline-block">${ws.plan}</p><p class="text-xs text-slate-400">${end}</p></td><td class="px-6 py-4"><div class="flex justify-between text-xs mb-1 font-medium"><span class="text-slate-700">${ws.bot_user_used}</span><span class="text-slate-400">/ ${ws.bot_user_limit}</span></div><div class="w-full bg-slate-200 rounded-full h-1.5"><div class="bg-indigo-500 h-1.5 rounded-full" style="width:${use}%"></div></div></td><td class="px-6 py-4 text-center"><span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-100">${ws.points||0}</span></td><td class="px-6 py-4 text-center"><span class="text-xs font-medium text-slate-600">${ad.join(', ')||'-'}</span></td><td class="px-6 py-4 text-right flex justify-end gap-2"><button onclick="openModal(${ws.id})" class="text-slate-600 hover:text-indigo-600 font-bold text-xs bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm cursor-pointer">${t.configure}</button></td></tr>`;
    }).join('');
    updateIcons();
}
function renderClientsTable(){ const tbody=document.getElementById('tableBodyClients'); const empty=document.getElementById('emptyStateClients'); tbody.innerHTML=''; if(clients.length===0){document.getElementById('paginationContainerClients').classList.add('hidden');empty.classList.remove('hidden');return;} empty.classList.add('hidden'); tbody.innerHTML=clients.map(c=>{ const teamsHtml=(c.owned_teams||[]).map(team=>`<button onclick="goToWorkspace(${team.id})" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-medium hover:bg-indigo-100 transition-colors mr-1 mb-1 border border-indigo-100 cursor-pointer"><i data-lucide="external-link" class="w-3 h-3"></i> ${team.name}</button>`).join(''); return `<tr class="hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-0"><td class="px-6 py-4 font-mono text-xs text-slate-500">#${c.id}</td><td class="px-6 py-4"><div class="flex items-center gap-3"><img src="${c.photo_url}" class="w-8 h-8 rounded-full border border-slate-200"><div class="overflow-hidden"><p class="text-sm font-bold text-slate-800 truncate">${c.name}</p><p class="text-xs text-slate-400 truncate">Creado: ${dayjs(c.created_at).format('DD MMM YYYY')}</p></div></div></td><td class="px-6 py-4"><p class="text-xs text-slate-600 flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3 text-slate-400"></i> ${c.email}</p><p class="text-xs text-slate-600 flex items-center gap-1 mt-1"><i data-lucide="phone" class="w-3 h-3 text-slate-400"></i> ${c.phone}</p></td><td class="px-6 py-4">${teamsHtml||'<span class="text-slate-400 text-xs">-</span>'}</td><td class="px-6 py-4 text-center"><span class="text-xs font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded">${c.total_teams_count}</span></td></tr>`; }).join(''); updateIcons(); }
function switchView(v) { ['workspaces','clients','recargas','settings'].forEach(id => { document.getElementById('view_'+id).classList.add('hidden'); document.getElementById('nav_'+id).classList.remove('bg-indigo-600/10','text-indigo-400'); }); document.getElementById('view_'+v).classList.remove('hidden'); document.getElementById('nav_'+v).classList.add('bg-indigo-600/10','text-indigo-400'); if(v==='workspaces'&&hasApiKey)fetchWorkspaces(1); if(v==='clients'&&hasApiKey)fetchClients(1); }
function goToRecargas() { document.getElementById('noBalanceModal').classList.add('hidden'); document.getElementById('editModal').classList.add('hidden'); switchView('recargas'); }
function goToWorkspace(id) { switchView('workspaces'); document.getElementById('searchInput').value = id; fetchWorkspaces(1); }

async function openModal(id) {
    currentEditingId = id; pointsDelta = 0; const m = document.getElementById('editModal'); m.classList.remove('hidden'); document.getElementById('modalLoading').classList.remove('hidden'); setTimeout(()=>document.getElementById('modalPanel').classList.remove('translate-x-full'), 10);
    const legacyBtn = document.getElementById('btnDelete'); if (legacyBtn) { const container = legacyBtn.closest('.mt-4') || legacyBtn.parentNode; if(container) container.style.display = 'none'; legacyBtn.style.display = 'none'; }
    try {
        const fdWs = new FormData(); fdWs.append('action', 'i360_get_workspace_details'); fdWs.append('security', wpNonce); fdWs.append('id', id);
        const resWs = await fetch(wpAjaxUrl, { method: 'POST', body: fdWs }); const jsonWs = await resWs.json();
        if (jsonWs.success && jsonWs.data && jsonWs.data.data) currentWSData = jsonWs.data.data; else currentWSData = workspaces.find(w => w.id === id); 
        if(availablePlans.length === 0) await fetchPlans(); 
        if(availableAddons.length === 0) await fetchAddons();
        
        let addonsToRender = availableAddons.length > 0 ? availableAddons : fallbackAddons;
        let userAddons = addonsToRender.filter(a => { const n = (a.name || a.label || "").toLowerCase(); return n.includes('user') || n.includes('contact'); });
        userAddons.forEach(a => { let size = 1; const n = (a.name || "").toLowerCase(); if(n.includes('10,000')||n.includes('10k')||a.id==='bot_user_large') size = 10000; else if(n.includes('1,000')||n.includes('1k')) size = 1000; a._packSize = size; }); 
        userAddons.sort((a,b) => b._packSize - a._packSize);
        let remUsers = currentWSData.addon_bot_user || 0; let userQtyMap = {};
        userAddons.forEach(a => { if(a._packSize > 1) { const q = Math.floor(remUsers / a._packSize); userQtyMap[a.id] = q; remUsers -= (q * a._packSize); } else { userQtyMap[a.id] = 0; } });

        document.getElementById('modalTitle').innerText = currentWSData.name; document.getElementById('modalId').innerText = `ID: ${currentWSData.id}`; document.getElementById('modalPoints').innerText = (currentWSData.points||0) + ' pts'; document.getElementById('currentPointsDisplay').innerText = (currentWSData.points||0); document.getElementById('statusToggle').checked = !currentWSData.is_paused; document.getElementById('pointsInput').value = '';
        const addonsBox = document.getElementById('addonsContainer');
        const dangerZoneHtml = `<div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;"><h4 style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">${t.danger_zone}</h4><button onclick="deleteWorkspace(${currentWSData.id})" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 16px; background: #fff1f2; border: 1px solid #fee2e2; border-radius: 12px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fff1f2'"><div style="display: flex; align-items: center; gap: 12px;"><div style="padding: 8px; background: white; border-radius: 8px; color: #ef4444; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></div><div style="text-align: left;"><p style="font-size: 14px; font-weight: 700; color: #b91c1c; margin: 0;">${t.delete_workspace}</p><p style="font-size: 12px; color: #ef4444; margin: 2px 0 0 0;">${t.irreversible}</p></div></div><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button></div>`;
        const addonsHtml = addonsToRender.map(a => {
            const displayName = a.name || a.display_name || a.label || "Addon"; const ptsCost = (a.points !== undefined) ? a.points : (a.price || 0); const nameLower = displayName.toLowerCase(); let currentQty = 0;
            if (nameLower.includes('bot') && !nameLower.includes('user')) currentQty = currentWSData.addon_bot || 0; else if (nameLower.includes('member')) currentQty = currentWSData.addon_member || 0; else if (nameLower.includes('list') || nameLower.includes('ticket')) currentQty = (currentWSData.addon_lists > 0) ? 1 : 0; else if (nameLower.includes('user') || nameLower.includes('contact')) currentQty = userQtyMap[a.id] || 0;
            const highlight = currentQty > 0 ? 'border-indigo-200 bg-indigo-50/30' : 'border-slate-200 bg-white';
            return `<div class="flex items-center justify-between p-4 rounded-xl border shadow-sm addon-row ${highlight}" data-id="${a.id}" data-price="${a.price}" data-name="${displayName}" data-points="${ptsCost}"><div class="flex items-center gap-4"><div class="p-2.5 bg-slate-50 text-indigo-600 rounded-lg"><i data-lucide="box" class="w-5 h-5"></i></div><div><p class="text-sm font-bold text-slate-800 m-0">${displayName}</p><p class="text-xs text-slate-500 m-0">$${a.price} USD + ${ptsCost} Pts</p></div></div><div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white"><button onclick="updateModalAddon('${a.id}', -1)" class="px-3 py-1.5 hover:bg-slate-100 font-bold border-r cursor-pointer">-</button><span id="qty_${a.id}" class="w-12 text-center text-sm font-bold bg-slate-50 py-1.5 text-slate-800">${currentQty}</span><button onclick="updateModalAddon('${a.id}', 1)" class="px-3 py-1.5 hover:bg-slate-100 font-bold border-l cursor-pointer">+</button></div></div>`;
        }).join('');
        addonsBox.innerHTML = addonsHtml + dangerZoneHtml;
        const sel = document.getElementById('planSelector'); sel.innerHTML = availablePlans.map(p => `<option value="${p.id}">${p.name} ($${p.price})</option>`).join(''); const match = availablePlans.find(p => p.id === currentWSData.plan); if(match) sel.value = currentWSData.plan; else { const opt = document.createElement('option'); opt.value = currentWSData.plan; opt.text = `${currentWSData.plan} (Actual)`; opt.selected = true; sel.add(opt); sel.value = currentWSData.plan; }
        updateIcons();
    } catch(e) { console.error(e); showToast(t.error_details, "error"); closeModal(); } finally { document.getElementById('modalLoading').classList.add('hidden'); }
}

window.updateModalAddon = (id, delta) => { const el = document.getElementById(`qty_${id}`); let val = parseInt(el.innerText); el.innerText = Math.max(0, val + delta); }

function confirmSaveChanges() {
    pendingChanges = { id: currentEditingId, newPlan: document.getElementById('planSelector').value, newStatus: document.getElementById('statusToggle').checked, addons: [], pointsDelta: pointsDelta, pointsNote: "" };
    let changesHtml = ""; let changesFound = false; let totalCostToPay = 0; let totalPointsRequired = 0; 
    
    if(pendingChanges.newPlan !== currentWSData.plan) { 
        const selectedPlanObj = availablePlans.find(p => p.id === pendingChanges.newPlan); const planCost = selectedPlanObj ? parseFloat(selectedPlanObj.price) : 0; const select = document.getElementById('planSelector'); const planName = select.options[select.selectedIndex].text;
        changesHtml += `<div style="display: flex; align-items: center; gap: 8px; color: #4f46e5; margin-bottom: 6px;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg> Plan: <b>${currentWSData.plan}</b> -> <b>${planName}</b> ${planCost > 0 ? `($${planCost})` : `<span style="background:#dcfce7; color:#166534; padding:2px 6px; border-radius:4px; font-size:11px;">${t.free}</span>`}</div>`; 
        totalCostToPay += planCost; 
        changesFound = true; 
    }
    if(pendingChanges.newStatus !== !currentWSData.is_paused) { const txt = pendingChanges.newStatus ? t.active : 'Pause'; changesHtml += `<div style="display: flex; align-items: center; gap: 8px; color: #d97706; margin-bottom: 6px;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path><line x1="12" y1="2" x2="12" y2="12"></line></svg> ${t.status}: <b>${txt}</b></div>`; changesFound = true; }
    if(pendingChanges.pointsDelta !== 0) { const type = pendingChanges.pointsDelta > 0 ? t.topup : t.deduct; const color = pendingChanges.pointsDelta > 0 ? '#16a34a' : '#dc2626'; changesHtml += `<div style="display: flex; align-items: center; gap: 8px; color: ${color}; margin-bottom: 6px;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg> ${type} ${Math.abs(pendingChanges.pointsDelta)} ${t.points_free}</div>`; changesFound = true; }

    let aR=availableAddons.length>0?availableAddons:fallbackAddons;
    let uA=aR.filter(a=>{const n=(a.name||a.label||"").toLowerCase();return n.includes('user')||n.includes('contact');});
    uA.forEach(a=>{let s=1;const n=(a.name||"").toLowerCase();if(n.includes('10,000')||n.includes('10k')||a.id==='bot_user_large')s=10000;else if(n.includes('1,000')||n.includes('1k'))s=1000;a._packSize=s;});
    uA.sort((a,b)=>b._packSize-a._packSize);
    let rem=currentWSData.addon_bot_user||0;let oM={};
    uA.forEach(a=>{if(a._packSize>1){const q=Math.floor(rem/a._packSize);oM[a.id]=q;rem-=(q*a._packSize);}else oM[a.id]=0;});

    document.querySelectorAll('.addon-row').forEach(row => {
        const id = row.dataset.id; const name = row.dataset.name || "Addon"; const qty = parseInt(document.getElementById(`qty_${id}`).innerText); const price = parseFloat(row.dataset.price); const pointsCostUnit = parseFloat(row.dataset.points) || 0;
        let oQ = 0; const nl = name.toLowerCase();
        if (nl.includes('bot') && !nl.includes('user')) oQ = currentWSData.addon_bot || 0; else if (nl.includes('member')) oQ = currentWSData.addon_member || 0; else if (nl.includes('list') || nl.includes('ticket')) oQ = (currentWSData.addon_lists > 0) ? 1 : 0; else if (nl.includes('user') || nl.includes('contact')) oQ = oM[id] || 0;
        if(qty !== oQ) { const diff = qty - oQ; const action = diff > 0 ? t.add : t.remove; let lC = 0, lP = 0; if (diff > 0) { lC = price * diff; lP = pointsCostUnit * diff; totalCostToPay += lC; totalPointsRequired += lP; } const color = diff > 0 ? '#16a34a' : '#dc2626'; changesHtml += `<div style="display: flex; align-items: center; gap: 8px; color: ${color}; margin-bottom: 6px;"><b>${action} ${Math.abs(diff)} x ${name}</b></div>`; pendingChanges.addons.push({ id: id, qty: diff }); changesFound = true; }
    });

    if(!changesFound) { showToast(t.no_changes, "info"); return; }
    
    pendingChanges.totalCost = totalCostToPay;

    const currentPoints = parseFloat(currentWSData.points || 0); const projectedPoints = currentPoints + pointsDelta;
    if (totalPointsRequired > projectedPoints) { showResourceError('points', totalPointsRequired, projectedPoints); return; }
    if (totalCostToPay > globalWalletBalance) { showResourceError('balance', totalCostToPay, globalWalletBalance); return; }

    showConfirmationSummary(changesHtml, totalCostToPay, totalPointsRequired);
}

async function executeSaveChanges() {
    const modal = document.getElementById('confirmationSummaryModal'); if(modal) modal.remove();
    if(pendingChanges.newPlan !== currentWSData.plan) { const fd = new FormData(); fd.append('action', 'i360_change_plan'); fd.append('security', wpNonce); fd.append('id', pendingChanges.id); fd.append('plan', pendingChanges.newPlan); await fetch(wpAjaxUrl, { method:'POST', body:fd }); }
    if(pendingChanges.newStatus !== !currentWSData.is_paused) { const fd = new FormData(); fd.append('action', 'i360_change_status'); fd.append('security', wpNonce); fd.append('id', pendingChanges.id); fd.append('status_action', pendingChanges.newStatus ? 'active' : 'pause'); await fetch(wpAjaxUrl, { method:'POST', body:fd }); }
    if(pendingChanges.pointsDelta !== 0) { const fd = new FormData(); fd.append('action', 'i360_manage_points'); fd.append('security', wpNonce); fd.append('workspace_id', pendingChanges.id); fd.append('type', pendingChanges.pointsDelta > 0 ? 'topup' : 'deduct'); fd.append('points', Math.abs(pendingChanges.pointsDelta)); fd.append('note', pendingChanges.pointsNote); try { await fetch(wpAjaxUrl, { method: 'POST', body: fd }); } catch(e) { console.error(e); } }
    for (const addon of pendingChanges.addons) { if(addon.qty !== 0) { const fd = new FormData(); fd.append('action', 'i360_manage_addon'); fd.append('security', wpNonce); fd.append('workspace_id', pendingChanges.id); fd.append('type', addon.qty > 0 ? 'add' : 'remove'); fd.append('payload', JSON.stringify({ price_id: addon.id, quantity: Math.abs(addon.qty) })); try { const res = await fetch(wpAjaxUrl, { method:'POST', body:fd }); const json = await res.json(); if(!json.success) injectAndShowModal(t.error_addon, `${t.error_process} ${addon.id}: ${json.data.message}`, "error"); } catch(e) { console.error(e); } } }
    
    if (pendingChanges.totalCost > 0) { updateWalletUI(pendingChanges.totalCost); }

    closeModal(); showToast(t.success_saved, "success"); fetchWorkspaces(wsCurrentPage);
}

function deleteWorkspace(id) { if (id) currentEditingId = id; injectAndShowModal(t.confirm_delete_title, t.confirm_delete_desc, "confirm", function() { const fd = new FormData(); fd.append('action', 'i360_delete_workspace'); fd.append('security', wpNonce); fd.append('id', currentEditingId); fetch(wpAjaxUrl, { method:'POST', body:fd }).then(r=>r.json()).then(d=>{ if(d.success) { closeModal(); showToast(t.success_deleted, "success"); fetchWorkspaces(wsCurrentPage); } else { injectAndShowModal(t.error, d.data.message, "error"); } }); }); }
function saveWPSettings() { const key = document.getElementById('settings_api_key').value; const btn = document.getElementById('btnSaveSettings'); btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> ${t.loading}`; updateIcons(); const fd = new FormData(); fd.append('action', 'i360_save_settings'); fd.append('security', wpNonce); fd.append('api_key', key); fetch(wpAjaxUrl, { method:'POST', body:fd }).then(r=>r.json()).then(d=>{ if(d.success){ showToast(t.success_settings, "success"); setTimeout(()=>location.reload(),1000); } else { injectAndShowModal(t.error, t.error_settings, "error"); } }); }

function submitCreateWorkspace() {
    const required = ['new_name', 'new_email', 'new_password', 'new_team_name'];
    let error = false;
    required.forEach(id => {
        const el = document.getElementById(id);
        if(!el.value.trim()) { el.style.borderColor = '#ef4444'; error = true; } else { el.style.borderColor = '#e2e8f0'; }
    });
    
    if(error) { showToast(t.error, "error"); return; }

    const payload = {
        name: document.getElementById('new_name').value, email: document.getElementById('new_email').value, password: document.getElementById('new_password').value, team_name: document.getElementById('new_team_name').value, phone: document.getElementById('new_phone').value, locale: document.getElementById('new_locale').value, require_email_verification: document.getElementById('new_require_verification').checked?'yes':'no', template_ns: document.getElementById('new_template_ns').value, openai_key: document.getElementById('new_openai_key').value, trial_days: parseInt(document.getElementById('new_trial_days').value)||14
    };

    const fd = new FormData(); fd.append('action', 'i360_create_workspace'); fd.append('security', wpNonce); fd.append('payload', JSON.stringify(payload));
    const btn = document.getElementById('btnCreateConfirm'); const og = btn.innerHTML;
    
    btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> ${t.loading}`; btn.disabled = true; updateIcons(); 
    
    fetch(wpAjaxUrl, { method:'POST', body:fd }).then(r=>r.json()).then(d=>{
        if(d.success){ closeCreateModal(); showToast(t.success_created, "success"); fetchWorkspaces(1); document.getElementById('createForm').reset(); } else { injectAndShowModal(t.error, d.data.message || t.error, "error"); }
    }).catch(e => { console.error(e); showToast(t.error, "error"); }).finally(() => { btn.innerHTML = og; btn.disabled = false; updateIcons(); });
}

function closeModal() { document.getElementById('modalPanel').classList.add('translate-x-full'); setTimeout(()=>document.getElementById('editModal').classList.add('hidden'), 300); }
window.openCreateModal = () => document.getElementById('createModal').classList.remove('hidden');
window.closeCreateModal = () => document.getElementById('createModal').classList.add('hidden');
window.simulateApiLoad = () => fetchWorkspaces(1);

document.addEventListener('DOMContentLoaded', () => { 
    ensureContainerExists(); 
    initializeBalance(); 
    renderTable(); 
    setTimeout(() => { if(typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
    if(hasApiKey) { fetchWorkspaces(1); fetchPlans(); fetchAddons(); } 
});