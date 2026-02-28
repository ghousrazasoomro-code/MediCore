// ═══════════════════════════════════════════════════════
//  MediCore — Database Connected script.js
//  API endpoint: api.php (same folder)
// ═══════════════════════════════════════════════════════

const API = 'api.php';

// ── AJAX Helper ──────────────────────────────────────────
async function apiCall(action, data = {}) {
  const fd = new FormData();
  fd.append('action', action);
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  try {
    const res = await fetch(API, { method: 'POST', body: fd });
    return await res.json();
  } catch (e) {
    return { success: false, msg: 'Network error: ' + e.message };
  }
}

async function apiGet(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params }).toString();
  try {
    const res = await fetch(`${API}?${qs}`);
    return await res.json();
  } catch (e) {
    return { success: false, msg: 'Network error: ' + e.message };
  }
}

// Utils
function showToast(icon, msg, color = 'var(--teal)') {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div'); t.className = 'toast';
  t.style.borderLeft = `3px solid ${color}`;
  t.innerHTML = `<span class="toast-icon">${icon}</span><span class="toast-text">${msg}</span>`;
  wrap.appendChild(t);
  requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 500); }, 3200);
}
function vEmail(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }
function toggleEye(id, el) { const i = document.getElementById(id); i.type = i.type === 'password' ? 'text' : 'password'; el.textContent = i.type === 'password' ? '👁' : '🙈'; }
function pwStr(v) {
  const bar = document.getElementById('pwBar'), lbl = document.getElementById('pwLbl');
  let s = 0; if (v.length >= 8) s++; if (/[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
  const c = ['#ff4d4d','#ff9900','#f0d000','var(--teal)'], l = ['Weak','Fair','Good','Strong'], w = ['25%','50%','75%','100%'];
  if (!v) { bar.style.width = '0'; lbl.textContent = 'Password strength'; return; }
  const i = Math.min(s - 1, 3); bar.style.background = c[i]; bar.style.width = w[i]; lbl.textContent = l[i]; lbl.style.color = c[i];
}
function today() { return new Date().toLocaleDateString('en-PK'); }
function timeNow() { return new Date().toLocaleTimeString('en-PK', { hour: '2-digit', minute: '2-digit' }); }
function clrErr(ids) { ids.forEach(id => { const e = document.getElementById(id); if (e) { e.classList.remove('merr','mierr','ierr'); const er = document.getElementById(id + 'E'); if (er) er.classList.remove('show'); } }); }
function showErr(id, msg) { const e = document.getElementById(id); if (e) { e.classList.add('mierr'); const er = document.getElementById(id + 'E'); if (er) { if (msg) er.textContent = msg; er.classList.add('show'); } } }
function getVal(id) { const e = document.getElementById(id); return e ? e.value.trim() : ''; }
function clrForm(ids) { ids.forEach(id => { const e = document.getElementById(id); if (!e) return; if (e.tagName === 'SELECT') e.selectedIndex = 0; else e.value = ''; }); }
function pill(txt, cls) { return `<span class="bp ${cls}">${txt}</span>`; }
function pillStatus(s) {
  if (['Confirmed','Paid','Active','Available','Improving'].includes(s)) return pill(s,'bp-s');
  if (['Pending','On Leave','Normal','Stable'].includes(s)) return pill(s,'bp-w');
  if (['Urgent','Critical','Overdue','Off Duty'].includes(s)) return pill(s,'bp-d');
  if (['Scheduled','Processing','Emergency'].includes(s)) return pill(s,'bp-b');
  return pill(s,'bp-g');
}

async function updateDashStats() {
  const res = await apiGet('get_dashboard_stats');
  if (res.success && res.data) {
    document.getElementById('dPat').textContent = res.data.patients_total || 0;
    document.getElementById('dAppt').textContent = res.data.appointments_total || 0;
    document.getElementById('dOpd').textContent = res.data.opd_waiting || 0;
    document.getElementById('dBill').textContent = res.data.bills_today || 0;
    document.getElementById('apptBadge').textContent = res.data.appointments_total || 0;
    document.getElementById('opdBadge').textContent = res.data.opd_waiting || 0;
  }
  refreshDashAppt(); refreshDashPat();
}

// AUTH
let loggedUser = {};
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active',(i===0&&tab==='login')||(i===1&&tab==='register')));
  ['loginPanel','registerPanel','forgotPanel'].forEach(id => document.getElementById(id).classList.remove('active'));
  const p = document.getElementById(tab==='forgot'?'forgotPanel':tab+'Panel'); if (p) p.classList.add('active');
}

async function doLogin() {
  const email = getVal('lEmail'), pass = document.getElementById('lPass').value;
  let ok = true;
  ['lEmail','lPass'].forEach(id => { document.getElementById(id).classList.remove('ierr'); document.getElementById(id+'E').classList.remove('show'); });
  if (!vEmail(email)) { document.getElementById('lEmail').classList.add('ierr'); document.getElementById('lEmailE').classList.add('show'); ok = false; }
  if (pass.length < 6) { document.getElementById('lPass').classList.add('ierr'); document.getElementById('lPassE').classList.add('show'); ok = false; }
  if (!ok) return;
  const btn = document.getElementById('lBtn'); btn.disabled = true;
  document.getElementById('lBtnTxt').textContent = 'Signing in…'; document.getElementById('lSpin').style.display = 'block';
  const res = await apiCall('login', { Email: email, Password: pass });
  btn.disabled = false; document.getElementById('lBtnTxt').textContent = 'Sign In →'; document.getElementById('lSpin').style.display = 'none';
  if (res.success) {
    const u = res.data;
    const fullName = (u.First_Name + ' ' + u.Last_Name).trim();
    loggedUser = { name: fullName, email: u.Email, role: u.Role, initials: fullName.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase() };
    enterApp();
  } else {
    showToast('❌', res.msg || 'Login failed.', 'var(--danger)');
  }
}

async function doRegister() {
  const email = getVal('rEmail'), pass = document.getElementById('rPass').value, conf = document.getElementById('rConf').value;
  let ok = true;
  ['rEmail','rConf'].forEach(id => document.getElementById(id+'E').classList.remove('show'));
  if (!vEmail(email)) { document.getElementById('rEmailE').classList.add('show'); ok = false; }
  if (pass !== conf || !conf) { document.getElementById('rConfE').classList.add('show'); ok = false; }
  if (!ok) return;
  const btn = document.getElementById('rBtn'); btn.disabled = true;
  document.getElementById('rBtnTxt').textContent = 'Creating…'; document.getElementById('rSpin').style.display = 'block';
  const res = await apiCall('register', { First_Name: getVal('rFirst'), Last_Name: getVal('rLast'), Email: email, Role: getVal('rRole'), Password: pass });
  btn.disabled = false; document.getElementById('rBtnTxt').textContent = 'Create Account →'; document.getElementById('rSpin').style.display = 'none';
  if (res.success) { showToast('✅', res.msg, 'var(--teal)'); setTimeout(() => switchTab('login'), 1200); }
  else showToast('❌', res.msg, 'var(--danger)');
}

function doForgot() {
  const email = getVal('fEmail');
  if (!vEmail(email)) { showToast('⚠️','Please enter a valid email.','var(--danger)'); return; }
  showToast('📧','Reset link sent! Check your inbox.','var(--teal)'); setTimeout(() => switchTab('login'), 1000);
}
function socialLogin(p) {
  showToast('🔗',`Connecting to ${p}…`,'var(--teal)');
  loggedUser={name:`${p} User`,email:`user@${p.toLowerCase()}.com`,role:'Doctor',initials:'GU'};
  setTimeout(() => enterApp(), 1600);
}
function enterApp() {
  document.getElementById('uAv').textContent = loggedUser.initials || 'U';
  document.getElementById('uName').textContent = loggedUser.name;
  document.getElementById('uRole').textContent = loggedUser.role;
  document.getElementById('greetName').textContent = loggedUser.name.split(' ')[0];
  document.getElementById('authPage').classList.add('hide');
  setTimeout(() => {
    document.getElementById('authPage').style.display = 'none';
    document.getElementById('appPage').style.display = 'block';
    buildBarChart(); buildCalendar(); updateDashStats(); loadAllData();
    showToast('✅',`Welcome, ${loggedUser.name.split(' ')[0]}!`,'var(--teal)');
  }, 500);
}
function doLogout() {
  document.getElementById('appPage').style.display = 'none';
  document.getElementById('authPage').style.display = 'flex';
  document.getElementById('authPage').classList.remove('hide');
  document.getElementById('lEmail').value=''; document.getElementById('lPass').value='';
  switchTab('login'); showToast('👋','Signed out successfully.','var(--gray)');
}

async function loadAllData() {
  await Promise.all([loadAppointments(),loadPatients(),loadDoctors(),loadOPD(),loadIPD(),loadPharma(),loadRx(),loadLab(),loadBilling(),loadInventory(),loadStaff()]);
}

// NAV
const pageTitles={dashboard:'Dashboard',appointments:'Appointments',patients:'Patients',doctors:'Doctors',opd:'OPD',ipd:'IPD / Wards',pharmacy:'Pharmacy',lab:'Laboratory',billing:'Billing',inventory:'Inventory',staff:'Staff & HR',reports:'Reports',settings:'Settings'};
function showPage(id,btn){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  const pg=document.getElementById('page-'+id); if(pg) pg.classList.add('active');
  if(btn) btn.classList.add('active');
  document.getElementById('tbTitle').textContent=pageTitles[id]||id;
  closeSidebar();
}
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sbOv').classList.toggle('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('sbOv').classList.remove('show');}
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
document.querySelectorAll('.modal-ov').forEach(m=>m.addEventListener('click',e=>{if(e.target===m){m.classList.remove('open');document.body.style.overflow='';}}));

// APPOINTMENTS
async function saveAppt(){
  const patient=getVal('aPatName'); if(!patient){showErr('aPatName','Patient name required');return;}
  const res=await apiCall('save_appointment',{patient,doctor:getVal('aDoctor'),department:getVal('aDept'),appt_date:getVal('aDate'),appt_time:getVal('aTime'),type:getVal('aType'),status:getVal('aStatus')||'Scheduled',notes:getVal('aNotes')});
  if(res.success){clrForm(['aPatName','aDoctor','aDept','aType','aDate','aTime','aStatus','aNotes']);closeModal('apptModal');showToast('✅',`Appointment booked for ${patient}`,'var(--teal)');await loadAppointments();updateDashStats();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadAppointments(){const res=await apiGet('get_appointments');if(res.success){renderApptTable(res.data);refreshDashAppt(res.data||[]);}}
function renderApptTable(rows=[]){
  const tb=document.getElementById('apptTable');
  document.getElementById('apptCount').textContent=rows.length+' records';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="7">No appointments yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td><b>${r.patient}</b></td><td>${r.doctor||'—'}</td><td>${r.department||'—'}</td><td>${r.appt_date||'—'} · ${r.appt_time||'—'}</td><td>${r.type||'—'}</td><td>${pillStatus(r.status)}</td><td><div class="act-btns"><select onchange="updateApptStatus(${r.id},this.value)" style="font-size:.75rem;padding:3px 6px;background:var(--card2);color:var(--white);border:1px solid var(--border);border-radius:6px"><option value="">Change Status</option><option>Confirmed</option><option>Pending</option><option>Completed</option><option>Cancelled</option></select><button class="btn-sm btn-d" onclick="delAppt(${r.id})">✕</button></div></td></tr>`).join('');
}
async function delAppt(id){const res=await apiCall('delete_appointment',{id});if(res.success){showToast('🗑','Appointment removed','var(--gray)');await loadAppointments();updateDashStats();}}
async function updateApptStatus(id,status){if(!status)return;const res=await apiCall('update_appointment_status',{id,status});if(res.success){showToast('🔄',`Status → ${status}`,'var(--gold)');await loadAppointments();}}
function refreshDashAppt(rows=[]){
  const el=document.getElementById('dashApptList');
  if(!rows.length){el.innerHTML='<div style="color:var(--gray);font-size:.84rem;text-align:center;padding:20px">No appointments yet. Book one above! 📅</div>';return;}
  const colors=['linear-gradient(135deg,#00c4a7,#009e86)','linear-gradient(135deg,#e8b86d,#c99240)','linear-gradient(135deg,#7fa8ff,#4070cc)','linear-gradient(135deg,#ff6b6b,#cc4444)'];
  el.innerHTML=rows.slice(0,4).map((r,i)=>`<div class="appt-item"><div class="appt-av" style="background:${colors[i%4]}">${r.patient.charAt(0)}</div><div style="flex:1"><div class="appt-name">${r.patient}</div><div class="appt-det">${r.department||'—'} · ${r.doctor||'—'}</div></div><div style="text-align:right"><div class="appt-time">${r.appt_time||'—'}</div>${pillStatus(r.status)}</div></div>`).join('');
}

// PATIENTS
async function savePat(){
  const first=getVal('pFirst'); if(!first){showErr('pFirst','First name required');return;}
  const last=getVal('pLast'),dob=getVal('pDob'),gender=getVal('pGender');
  let age=0; if(dob){const d=new Date(dob);age=Math.floor((Date.now()-d)/(365.25*24*3600*1000));}
  const res=await apiCall('save_patient',{name:(first+' '+last).trim(),age,gender,blood_type:getVal('pBlood'),contact:getVal('pContact'),email:'',address:getVal('pAddr')||'',department:getVal('pDept'),status:'Active'});
  if(res.success){clrForm(['pFirst','pLast','pDob','pGender','pBlood','pContact','pDept','pDoctor','pAddr','pEmerg','pNotes']);closeModal('patModal');showToast('✅',`Registered! ID: ${res.data.patient_id}`,'var(--teal)');await loadPatients();updateDashStats();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadPatients(){const res=await apiGet('get_patients');if(res.success){renderPatTable(res.data);refreshDashPat(res.data||[]);}}
function renderPatTable(rows=[]){
  const tb=document.getElementById('patTable');
  document.getElementById('patCount').textContent=rows.length+' records';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="8">No patients registered yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td><b>${r.name}</b></td><td style="color:var(--teal);font-family:monospace">${r.patient_id}</td><td>${r.age||'—'} / ${r.gender||'—'}</td><td><span style="background:var(--danger);color:#fff;padding:2px 8px;border-radius:12px;font-size:.75rem">${r.blood_type||'—'}</span></td><td>${r.contact||'—'}</td><td>${r.department||'—'}</td><td>${pillStatus(r.status)}</td><td><button class="btn-sm btn-d" onclick="delPat(${r.id})">✕</button></td></tr>`).join('');
}
async function delPat(id){const res=await apiCall('delete_patient',{id});if(res.success){showToast('🗑','Patient removed','var(--gray)');await loadPatients();updateDashStats();}}
function refreshDashPat(rows=[]){
  const tb=document.getElementById('dashPatTable');
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="5">No patients registered yet.</td></tr>';return;}
  tb.innerHTML=rows.slice(0,5).map(r=>`<tr><td><b>${r.name}</b></td><td style="color:var(--teal);font-family:monospace;font-size:.8rem">${r.patient_id}</td><td>${r.age||'—'}</td><td>${r.department||'—'}</td><td>${pillStatus(r.status)}</td></tr>`).join('');
}

// DOCTORS
async function saveDoc(){
  const name=getVal('dName'); if(!name){showErr('dName','Name required');return;}
  const res=await apiCall('save_doctor',{name,specialization:getVal('dSpec'),experience:getVal('dExp'),schedule:getVal('dSched'),contact:getVal('dContact'),email:'',status:getVal('dStatus')||'Available'});
  if(res.success){clrForm(['dName','dSpec','dExp','dSched','dContact','dEmail','dStatus']);closeModal('docModal');showToast('✅',`Dr. ${name} added. ID: ${res.data.doc_id}`,'var(--teal)');await loadDoctors();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadDoctors(){const res=await apiGet('get_doctors');if(res.success)renderDocTable(res.data);}
function renderDocTable(rows=[]){
  const tb=document.getElementById('docTable');
  document.getElementById('docCount').textContent=rows.length+' records';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="7">No doctors added yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td><b>Dr. ${r.name}</b><br><small style="color:var(--teal);font-family:monospace">${r.doc_id}</small></td><td>${r.specialization||'—'}</td><td>${r.experience||'—'}</td><td>${r.schedule||'—'}</td><td>${r.contact||'—'}</td><td>${pillStatus(r.status)}</td><td><button class="btn-sm btn-d" onclick="delDoc(${r.id})">✕</button></td></tr>`).join('');
}
async function delDoc(id){const res=await apiCall('delete_doctor',{id});if(res.success){showToast('🗑','Doctor removed','var(--gray)');await loadDoctors();}}

// OPD
async function saveOPD(){
  const patient=getVal('oPatName'); if(!patient){showErr('oPatName','Patient name required');return;}
  const res=await apiCall('save_opd',{patient,age:getVal('oAge')||0,doctor:getVal('oDoctor'),department:getVal('oDept'),complaint:getVal('oComplaint'),priority:getVal('oPriority')||'Normal'});
  if(res.success){clrForm(['oPatName','oAge','oDoctor','oDept','oComplaint','oPriority']);closeModal('opdModal');showToast('🎫',`Token: ${res.data.token}`,'var(--teal)');await loadOPD();updateDashStats();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadOPD(){const res=await apiGet('get_opd');if(res.success)renderOPDQueue(res.data);}
function renderOPDQueue(rows=[]){
  const waiting=rows.filter(r=>r.status!=='Seen'),seen=rows.filter(r=>r.status==='Seen');
  document.getElementById('opdWait').textContent=waiting.length;
  document.getElementById('opdSeen').textContent=seen.length;
  document.getElementById('opdLast').textContent=rows.length?rows[0].token:'—';
  const el=document.getElementById('opdQueue');
  if(!rows.length){el.innerHTML='<div style="color:var(--gray);text-align:center;padding:24px">No tokens issued yet.</div>';return;}
  el.innerHTML=rows.map(r=>{const priBg=r.priority==='Emergency'?'var(--danger)':r.priority==='Urgent'?'var(--gold)':'var(--teal)';return `<div style="display:flex;align-items:center;gap:14px;padding:12px;border-bottom:1px solid var(--border);opacity:${r.status==='Seen'?.5:1}"><div style="background:${priBg};color:#000;font-weight:700;width:64px;text-align:center;padding:6px;border-radius:8px;font-size:.8rem">${r.token}</div><div style="flex:1"><div style="font-weight:600">${r.patient}</div><div style="font-size:.78rem;color:var(--gray)">${r.department||'—'} · ${r.doctor||'—'} · ${r.priority}</div>${r.complaint?`<div style="font-size:.75rem;color:var(--gray);margin-top:2px">${r.complaint}</div>`:''}</div><div style="display:flex;gap:6px">${r.status!=='Seen'?`<button class="btn-sm btn-p" onclick="markOPDSeen(${r.id})">✓ Seen</button>`:pill('Seen','bp-s')}<button class="btn-sm btn-d" onclick="delOPD(${r.id})">✕</button></div></div>`;}).join('');
}
async function markOPDSeen(id){const res=await apiCall('mark_opd_seen',{id});if(res.success){showToast('✅','Marked as seen','var(--teal)');await loadOPD();updateDashStats();}}
async function delOPD(id){const res=await apiCall('delete_opd',{id});if(res.success){showToast('🗑','Token removed','var(--gray)');await loadOPD();updateDashStats();}}

// IPD
async function saveIPD(){
  const patient=getVal('iPatName'); if(!patient){showErr('iPatName','Patient name required');return;}
  const res=await apiCall('save_ipd',{patient,age_gender:getVal('iAgeGen'),ward:getVal('iWard'),bed:getVal('iBed'),doctor:getVal('iDoctor'),admission_date:getVal('iDate')||new Date().toISOString().split('T')[0],diagnosis:getVal('iDiagnosis'),condition:getVal('iCondition')||'Stable'});
  if(res.success){clrForm(['iPatName','iAgeGen','iWard','iBed','iDoctor','iDate','iDiagnosis','iCondition']);closeModal('ipdModal');showToast('🛏',`${patient} admitted. ID: ${res.data.admission_id}`,'var(--teal)');await loadIPD();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadIPD(){const res=await apiGet('get_ipd');if(res.success)renderIPDTable(res.data);}
function renderIPDTable(rows=[]){
  const tb=document.getElementById('ipdTable');
  document.getElementById('ipdCount').textContent=rows.filter(r=>r.status!=='Discharged').length+' admitted';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="8">No admissions yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr style="opacity:${r.status==='Discharged'?.5:1}"><td><b>${r.bed||'—'}</b></td><td>${r.ward||'—'}</td><td><b>${r.patient}</b><br><small style="color:var(--gray)">${r.age_gender||''}</small></td><td>${r.admission_date||'—'}</td><td>${r.doctor||'—'}</td><td>${r.diagnosis||'—'}</td><td>${pillStatus(r.condition)}</td><td><div class="act-btns">${r.status!=='Discharged'?`<button class="btn-sm btn-p" onclick="dischargeIPD(${r.id})">Discharge</button>`:pill('Discharged','bp-g')}<button class="btn-sm btn-d" onclick="delIPD(${r.id})">✕</button></div></td></tr>`).join('');
}
async function dischargeIPD(id){const res=await apiCall('discharge_ipd',{id});if(res.success){showToast('🏠','Patient discharged.','var(--teal)');await loadIPD();}}
async function delIPD(id){const res=await apiCall('delete_ipd',{id});if(res.success){showToast('🗑','Record deleted.','var(--gray)');await loadIPD();}}

// PHARMACY
async function savePharma(){
  const name=getVal('mName'); if(!name){showErr('mName','Medicine name required');return;}
  const res=await apiCall('save_pharma',{name,category:getVal('mCat'),stock:getVal('mStock')||0,unit:getVal('mUnit'),min_stock:getVal('mMin')||0,price:getVal('mPrice')||0,expiry_date:getVal('mExpiry')});
  if(res.success){clrForm(['mName','mCat','mStock','mUnit','mMin','mPrice','mExpiry']);closeModal('pharmaModal');showToast('💊',`${name} added.`,'var(--teal)');await loadPharma();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadPharma(){const res=await apiGet('get_pharma');if(res.success)renderPharmaTable(res.data);}
function renderPharmaTable(rows=[]){
  const tb=document.getElementById('pharmaTable'); if(!tb)return;
  document.getElementById('pharmaCount').textContent=rows.length+' medicines';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="7">No medicines yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>{const low=parseInt(r.stock)<=parseInt(r.min_stock);const sc=parseInt(r.stock)===0?'var(--danger)':low?'var(--gold)':'var(--teal)';return `<tr><td><b>${r.name}</b></td><td>${r.category||'—'}</td><td style="color:${sc};font-weight:600">${r.stock} ${r.unit||''}</td><td>${r.min_stock||0}</td><td>Rs. ${parseFloat(r.price||0).toLocaleString()}</td><td>${r.expiry_date||'—'}</td><td>${low?pill('Low','bp-d'):pill('OK','bp-s')}</td><td><button class="btn-sm btn-d" onclick="delPharma(${r.id})">✕</button></td></tr>`;}).join('');
}
async function delPharma(id){const res=await apiCall('delete_pharma',{id});if(res.success){showToast('🗑','Medicine removed.','var(--gray)');await loadPharma();}}

async function saveRx(){
  const patient=getVal('rxPat'); if(!patient){showErr('rxPat','Patient name required');return;}
  const res=await apiCall('save_rx',{patient,doctor:getVal('rxDoc'),medicine:getVal('rxMed'),quantity:getVal('rxQty')||1});
  if(res.success){clrForm(['rxPat','rxDoc','rxMed','rxQty']);closeModal('rxModal');showToast('📋',`Dispensed. ID: ${res.data.rx_id}`,'var(--teal)');await loadPharma();await loadRx();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadRx(){const res=await apiGet('get_rx');if(res.success)renderRxTable(res.data);}
function renderRxTable(rows=[]){
  const tb=document.getElementById('rxTable'); if(!tb)return;
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="6">No prescriptions yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td style="color:var(--teal);font-family:monospace">${r.rx_id}</td><td><b>${r.patient}</b></td><td>${r.medicine||'—'}</td><td>${r.quantity}</td><td>${r.doctor||'—'}</td><td>${r.dispensed_at?r.dispensed_at.split(' ')[0]:'—'}</td></tr>`).join('');
}

// LABORATORY
async function saveLab(){
  const patient=getVal('lbPat'); if(!patient){showErr('lbPat','Patient name required');return;}
  const res=await apiCall('save_lab',{patient,test_name:getVal('lbTest'),ordered_by:getVal('lbDoc'),priority:getVal('lbPri')||'Normal',notes:getVal('lbNotes')});
  if(res.success){clrForm(['lbPat','lbTest','lbDoc','lbPri','lbNotes']);closeModal('labModal');showToast('🔬',`Lab order: ${res.data.lab_id}`,'var(--teal)');await loadLab();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadLab(){const res=await apiGet('get_lab');if(res.success)renderLabTable(res.data);}
function renderLabTable(rows=[]){
  const tb=document.getElementById('labTable'); if(!tb)return;
  const countEl=document.getElementById('labCount'); if(countEl) countEl.textContent=rows.length+' orders';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="8">No lab orders yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td style="color:var(--teal);font-family:monospace">${r.lab_id}</td><td><b>${r.patient}</b></td><td>${r.test_name||'—'}</td><td>${r.ordered_by||'—'}</td><td>${r.ordered_at?r.ordered_at.split(' ')[0]:'—'}</td><td>${r.result||'—'}</td><td>${pillStatus(r.status)}</td><td><div class="act-btns"><select onchange="updateLabStatus(${r.id},this.value)" style="font-size:.75rem;padding:3px 6px;background:var(--card2);color:var(--white);border:1px solid var(--border);border-radius:6px"><option value="">Update</option><option>Processing</option><option>Ready</option><option>Completed</option></select><button class="btn-sm btn-d" onclick="delLab(${r.id})">✕</button></div></td></tr>`).join('');
}
async function updateLabStatus(id,status){if(!status)return;const res=await apiCall('update_lab_status',{id,status});if(res.success){showToast('🔬',`Lab → ${status}`,'var(--gold)');await loadLab();}}
async function delLab(id){const res=await apiCall('delete_lab',{id});if(res.success){showToast('🗑','Lab order deleted.','var(--gray)');await loadLab();}}

// BILLING
async function saveBill(){
  const patient=getVal('bPat'); if(!patient){showErr('bPat','Patient name required');return;}
  const res=await apiCall('save_bill',{patient,services:getVal('bServices'),amount:getVal('bAmount')||0,payment_method:getVal('bMethod')||'Cash',status:getVal('bStatus')||'Pending'});
  if(res.success){clrForm(['bPat','bServices','bAmount','bMethod','bStatus']);closeModal('billModal');showToast('💳',`Invoice ${res.data.bill_id} generated.`,'var(--teal)');await loadBilling();updateDashStats();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadBilling(){const res=await apiGet('get_billing');if(res.success)renderBillTable(res.data);}
function renderBillTable(rows=[]){
  const tb=document.getElementById('billTable'); if(!tb)return;
  const countEl=document.getElementById('billCount'); if(countEl) countEl.textContent=rows.length+' invoices';
  const total=rows.reduce((s,r)=>s+parseFloat(r.amount||0),0);
  const totalEl=document.getElementById('billTotal'); if(totalEl) totalEl.textContent='Rs. '+total.toLocaleString();
  const paidEl=document.getElementById('billPaid'); if(paidEl) paidEl.textContent=rows.filter(r=>r.status==='Paid').length;
  const pendEl=document.getElementById('billPend'); if(pendEl) pendEl.textContent=rows.filter(r=>r.status==='Pending').length;
  const overEl=document.getElementById('billOver'); if(overEl) overEl.textContent=rows.filter(r=>r.status==='Overdue').length;
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="7">No invoices yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td style="color:var(--teal);font-family:monospace">${r.bill_id}</td><td><b>${r.patient}</b></td><td>${r.services||'—'}</td><td style="font-weight:600">Rs. ${parseFloat(r.amount||0).toLocaleString()}</td><td>${r.payment_method||'—'}</td><td>${pillStatus(r.status)}</td><td><div class="act-btns"><select onchange="updateBillStatus(${r.id},this.value)" style="font-size:.75rem;padding:3px 6px;background:var(--card2);color:var(--white);border:1px solid var(--border);border-radius:6px"><option value="">Status</option><option>Paid</option><option>Pending</option><option>Overdue</option></select><button class="btn-sm btn-d" onclick="delBill(${r.id})">✕</button></div></td></tr>`).join('');
}
async function updateBillStatus(id,status){if(!status)return;const res=await apiCall('update_bill_status',{id,status});if(res.success){showToast('💳',`Status → ${status}`,'var(--gold)');await loadBilling();updateDashStats();}}
async function delBill(id){const res=await apiCall('delete_bill',{id});if(res.success){showToast('🗑','Invoice removed.','var(--gray)');await loadBilling();updateDashStats();}}

// INVENTORY
async function saveInv(){
  const name=getVal('invName'); if(!name){showErr('invName','Item name required');return;}
  const res=await apiCall('save_inventory',{item_name:name,category:getVal('invCat'),stock:getVal('invStock')||0,unit:getVal('invUnit'),reorder_level:getVal('invReorder')||0,supplier:getVal('invSupplier')});
  if(res.success){clrForm(['invName','invCat','invStock','invUnit','invReorder','invSupplier']);closeModal('invModal');showToast('📦',`${name} added.`,'var(--teal)');await loadInventory();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadInventory(){const res=await apiGet('get_inventory');if(res.success)renderInvTable(res.data);}
function renderInvTable(rows=[]){
  const tb=document.getElementById('invTable'); if(!tb)return;
  const countEl=document.getElementById('invCount'); if(countEl) countEl.textContent=rows.length+' items';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="8">No inventory items yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>{const st=parseInt(r.stock)===0?'Out of Stock':parseInt(r.stock)<=parseInt(r.reorder_level)?'Reorder':'OK';return `<tr><td><b>${r.item_name}</b></td><td>${r.category||'—'}</td><td>${r.stock}</td><td>${r.unit||'—'}</td><td>${r.reorder_level||0}</td><td>${r.supplier||'—'}</td><td>${st==='OK'?pill('OK','bp-s'):pill(st,'bp-d')}</td><td><button class="btn-sm btn-d" onclick="delInv(${r.id})">✕</button></td></tr>`;}).join('');
}
async function delInv(id){const res=await apiCall('delete_inventory',{id});if(res.success){showToast('🗑','Item removed.','var(--gray)');await loadInventory();}}

// STAFF
async function saveStaff(){
  const name=getVal('stName'); if(!name){showErr('stName','Name required');return;}
  const res=await apiCall('save_staff',{name,department:getVal('stDept'),role:getVal('stRole'),contact:getVal('stContact'),shift:getVal('stShift')||'Morning',salary:getVal('stSalary')||0,status:getVal('stStatus')||'Active'});
  if(res.success){clrForm(['stName','stDept','stRole','stContact','stShift','stSalary','stStatus']);closeModal('staffModal');showToast('✅',`${name} added. ID: ${res.data.staff_id}`,'var(--teal)');await loadStaff();}
  else showToast('❌',res.msg,'var(--danger)');
}
async function loadStaff(){const res=await apiGet('get_staff');if(res.success)renderStaffTable(res.data);}
function renderStaffTable(rows=[]){
  const tb=document.getElementById('staffTable'); if(!tb)return;
  const countEl=document.getElementById('staffCount'); if(countEl) countEl.textContent=rows.length+' staff';
  if(!rows.length){tb.innerHTML='<tr class="empty-row"><td colspan="9">No staff added yet.</td></tr>';return;}
  tb.innerHTML=rows.map(r=>`<tr><td><b>${r.name}</b></td><td style="color:var(--teal);font-family:monospace;font-size:.8rem">${r.staff_id}</td><td>${r.department||'—'}</td><td>${r.role||'—'}</td><td>${r.contact||'—'}</td><td>${r.shift||'—'}</td><td>Rs. ${parseFloat(r.salary||0).toLocaleString()}</td><td>${pillStatus(r.status)}</td><td><button class="btn-sm btn-d" onclick="delStaff(${r.id})">✕</button></td></tr>`).join('');
}
async function delStaff(id){const res=await apiCall('delete_staff',{id});if(res.success){showToast('🗑','Staff removed.','var(--gray)');await loadStaff();}}

// REPORTS
async function genReport(type){
  document.getElementById('reportOutput').style.display='block';
  document.getElementById('reportTitle').textContent='📊 '+type;
  document.getElementById('reportBody').innerHTML='<p style="color:var(--gray)">Loading…</p>';
  const typeMap={'Patient Summary':'patients','Revenue Report':'billing','Appointments':'appointments','Bed Occupancy':'ipd','Lab Tests':'opd','Pharmacy Usage':'billing'};
  const apiType=typeMap[type]||'billing';
  const from=document.getElementById('rFrom')?.value||new Date(new Date().getFullYear(),new Date().getMonth(),1).toISOString().split('T')[0];
  const to=document.getElementById('rTo')?.value||new Date().toISOString().split('T')[0];
  const res=await apiGet('get_reports',{type:apiType,from,to});
  let html='<div style="color:var(--gray);font-size:.9rem;line-height:1.9">';
  if(!res.success||!res.data.length){html+='<p>No data found for selected period.</p>';}
  else if(type==='Revenue Report'){
    const total=res.data.reduce((s,r)=>s+parseFloat(r.amount||0),0);
    const paid=res.data.filter(r=>r.status==='Paid');
    html+=`<b>Total Invoices:</b> ${res.data.length}<br><b>Total:</b> Rs. ${total.toLocaleString()}<br><b>Paid:</b> ${paid.length}<br><br>`;
    html+='<table style="width:100%;font-size:.8rem"><thead><tr><th>Invoice</th><th>Patient</th><th>Services</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
    html+=res.data.map(r=>`<tr><td>${r.bill_id}</td><td>${r.patient}</td><td>${r.services||'—'}</td><td>Rs. ${parseFloat(r.amount).toLocaleString()}</td><td>${r.status}</td></tr>`).join('');
    html+='</tbody></table>';
  }else{
    html+=`<b>Records found:</b> ${res.data.length}<br><br>`;
    const keys=Object.keys(res.data[0]);
    html+='<table style="width:100%;font-size:.8rem"><thead><tr>'+keys.map(k=>`<th>${k}</th>`).join('')+'</tr></thead><tbody>';
    html+=res.data.map(r=>'<tr>'+keys.map(k=>`<td>${r[k]||'—'}</td>`).join('')+'</tr>').join('');
    html+='</tbody></table>';
  }
  html+='</div>';
  document.getElementById('reportBody').innerHTML=html;
  document.getElementById('reportOutput').scrollIntoView({behavior:'smooth'});
  showToast('📊',`${type} generated`,'var(--teal)');
}

// BAR CHART
function buildBarChart(){
  const data=[{d:'Mon',v:38},{d:'Tue',v:52},{d:'Wed',v:45},{d:'Thu',v:60},{d:'Fri',v:48},{d:'Sat',v:30},{d:'Sun',v:20}];
  const max=70,chart=document.getElementById('barChart'),lbls=document.getElementById('barLabels');
  chart.innerHTML='';lbls.innerHTML='';
  data.forEach(d=>{
    const wrap=document.createElement('div');wrap.className='bar-wrap';
    const bar=document.createElement('div');bar.className='bar';bar.style.background='var(--teal)';bar.style.height='3px';bar.title=`${d.d}: ${d.v}`;
    wrap.appendChild(bar);chart.appendChild(wrap);
    const lbl=document.createElement('div');lbl.style.cssText='flex:1;text-align:center;font-size:.64rem;color:var(--gray)';lbl.textContent=d.d;lbls.appendChild(lbl);
    setTimeout(()=>{bar.style.height=(d.v/max*100)+'%';},200);
  });
}

// CALENDAR
function buildCalendar(){const now=new Date();renderCal(document.getElementById('miniCal'),now.getFullYear(),now.getMonth(),now);}
function renderCal(cal,year,month,today){
  const mn=['January','February','March','April','May','June','July','August','September','October','November','December'];
  const fd=new Date(year,month,1).getDay(),dim=new Date(year,month+1,0).getDate(),pd=new Date(year,month,0).getDate();
  cal.innerHTML=`<div class="cal-hdr"><button class="cal-nav" onclick="chMon(-1)">‹</button><div class="cal-title">${mn[month]} ${year}</div><button class="cal-nav" onclick="chMon(1)">›</button></div><div class="cal-dhdr">${['Su','Mo','Tu','We','Th','Fr','Sa'].map(d=>`<span>${d}</span>`).join('')}</div><div class="cal-days" id="calDays"></div>`;
  const grid=cal.querySelector('#calDays');
  for(let i=fd-1;i>=0;i--){const d=document.createElement('div');d.className='cd prev-m';d.textContent=pd-i;grid.appendChild(d);}
  for(let d=1;d<=dim;d++){const div=document.createElement('div');div.className='cd';div.textContent=d;if(d===today.getDate()&&month===today.getMonth()&&year===today.getFullYear())div.classList.add('today');div.onclick=()=>showToast('📅',`${mn[month]} ${d} selected`,'var(--teal)');grid.appendChild(div);}
  window._cs={year,month,today};
}
window.chMon=function(dir){let{year,month,today}=window._cs;month+=dir;if(month<0){month=11;year--;}if(month>11){month=0;year++;}renderCal(document.getElementById('miniCal'),year,month,today);};
document.querySelectorAll('input[type="date"]').forEach(el=>{el.valueAsDate=new Date();});
