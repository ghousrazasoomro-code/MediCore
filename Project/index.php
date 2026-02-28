<?php
// ── PHP Registration Handler ──────────────────────────────
$success_msg = '';
$error_msg   = '';

if (isset($_POST['sb'])) {
    $fn   = trim($_POST['First_Name']      ?? '');
    $ln   = trim($_POST['Last_Name']       ?? '');
    $em   = trim($_POST['Email']           ?? '');
    $role = trim($_POST['Role']            ?? '');
    $pass = $_POST['Password']             ?? '';
    $conf = $_POST['Confirm_Password']     ?? '';

    if (!$fn || !$em || !$role) {
        $error_msg = 'Please fill all required fields.';
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error_msg = 'Password must be at least 6 characters.';
    } elseif ($pass !== $conf) {
        $error_msg = 'Passwords do not match.';
    } else {
        $con = @mysqli_connect('localhost', 'root', '', 'Project');
        if (!$con) {
            $error_msg = 'Database connection failed. Please try again later.';
        } else {
            mysqli_set_charset($con, 'utf8mb4');
            $fn_s   = mysqli_real_escape_string($con, $fn);
            $ln_s   = mysqli_real_escape_string($con, $ln);
            $em_s   = mysqli_real_escape_string($con, $em);
            $role_s = mysqli_real_escape_string($con, $role);
            $hash   = password_hash($pass, PASSWORD_DEFAULT);
            $q = "INSERT INTO `Register` (First_Name,Last_Name,Email,Role,Password)
                  VALUES ('$fn_s','$ln_s','$em_s','$role_s','$hash')";
            if (mysqli_query($con, $q)) {
                $success_msg = 'Account created successfully! Please sign in.';
            } else {
                $error_msg = 'Email already exists or an error occurred.';
            }
            mysqli_close($con);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MediCore — Hospital Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  /* PHP message alerts */
  .php-alert {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 500;
    margin-bottom: 14px;
    text-align: center;
    animation: fadeIn .4s ease;
  }
  .php-alert.success { background: rgba(0,200,150,.15); border: 1px solid rgba(0,200,150,.35); color: #00c896; }
  .php-alert.error   { background: rgba(255,80,80,.13);  border: 1px solid rgba(255,80,80,.3);  color: #ff6b6b; }
  @keyframes fadeIn  { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
</style>
</head>
<body>

<!-- ══════════════ AUTH PAGE ══════════════ -->
<div id="authPage">
  <div class="auth-left">
    <div class="auth-brand">
      <div class="auth-brand-icon">🏥</div>
      <div class="auth-brand-name">Medi<span>Core</span></div>
    </div>
    <h1 class="auth-headline">Smart Healthcare<br>for <em>Modern Hospitals</em></h1>
    <p class="auth-desc">MediCore unifies every department into one powerful, intuitive platform trusted by 500+ hospitals worldwide.</p>
    <div class="auth-perks">
      <div class="perk"><div class="perk-icon">📋</div>Complete patient records & history</div>
      <div class="perk"><div class="perk-icon">📅</div>AI-powered scheduling & appointments</div>
      <div class="perk"><div class="perk-icon">💰</div>Automated billing & insurance claims</div>
      <div class="perk"><div class="perk-icon">📊</div>Real-time analytics dashboard</div>
      <div class="perk"><div class="perk-icon">🔐</div>HIPAA compliant & ISO 27001 certified</div>
    </div>
    <div class="float-cards">
      <div class="float-card"><span>🟢</span><div><div class="float-title">28 Patients Today</div><div class="float-sub">↑ 12% from yesterday</div></div></div>
      <div class="float-card"><span>💊</span><div><div class="float-title">Pharmacy Alert</div><div class="float-sub">3 items low in stock</div></div></div>
      <div class="float-card"><span>📅</span><div><div class="float-title">Next: 10:30 AM</div><div class="float-sub">Dr. Malik — Cardiology</div></div></div>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-wrap">
      <div class="tabs">
        <!-- Auto-open register tab if there's a PHP message -->
        <button class="tab-btn <?= empty($success_msg) && empty($error_msg) ? 'active' : '' ?>" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn <?= (!empty($success_msg) || !empty($error_msg)) ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
      </div>

      <!-- LOGIN -->
      <div class="form-panel <?= empty($success_msg) && empty($error_msg) ? 'active' : '' ?>" id="loginPanel">
        <div class="form-title">Welcome back 👋</div>
        <div class="form-subtitle">Sign in to access your hospital dashboard</div>
        <div class="fg"><label>Email Address</label>
          <div class="fw"><input type="email" id="lEmail" placeholder="doctor@hospital.com"><span class="fi">✉️</span></div>
          <div class="emsg" id="lEmailE">Please enter a valid email.</div>
        </div>
        <div class="fg"><label>Password</label>
          <div class="fw"><input type="password" id="lPass" placeholder="••••••••">
            <span class="fi">🔒</span><span class="eye" onclick="toggleEye('lPass',this)">👁</span></div>
          <div class="emsg" id="lPassE">Password must be at least 6 characters.</div>
        </div>
        <div class="row-between">
          <label class="chk"><input type="checkbox"><div class="cbox"></div>Remember me</label>
          <a href="#" class="flink" onclick="switchTab('forgot');return false">Forgot password?</a>
        </div>
        <button class="btn-full" id="lBtn" onclick="doLogin()">
          <span id="lBtnTxt">Sign In →</span><span class="spin" id="lSpin"></span>
        </button>
        <div class="divider">or continue with</div>
        <div class="social-row">
          <button class="soc-btn" onclick="socialLogin('Google')">🌐 Google</button>
          <button class="soc-btn" onclick="socialLogin('Microsoft')">🪟 Microsoft</button>
        </div>
      </div>

      <!-- REGISTER -->
      <form method="POST" action="">
        <div class="form-panel <?= (!empty($success_msg) || !empty($error_msg)) ? 'active' : '' ?>" id="registerPanel">
          <div class="form-title">Create Account 🏥</div>
          <div class="form-subtitle">Join thousands of healthcare professionals</div>


            <?php if ($success_msg): ?>
            <div class="php-alert success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
            <div class="php-alert error"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>
        

          <div class="two-col">
            <div class="fg"><label>First Name</label><div class="fw"><input type="text" id="rFirst" name="First_Name" placeholder="John"><span class="fi">👤</span></div></div>
            <div class="fg"><label>Last Name</label><div class="fw"><input type="text" id="rLast" name="Last_Name" placeholder="Doe"><span class="fi">👤</span></div></div>
          </div>
          <div class="fg"><label>Work Email</label>
            <div class="fw"><input type="email" id="rEmail" name="Email" placeholder="john@hospital.com"><span class="fi">✉️</span></div>
            <div class="emsg" id="rEmailE">Please enter a valid email.</div>
          </div>
          <div class="fg"><label>Role</label>
            <div class="fw">
              <select id="rRole" name="Role" onchange="this.style.color='var(--white)'">
                <option value="" disabled  style="color:var(--gray)">Select your role</option>
                <option value="Doctor">Doctor</option>
                <option value="Nurse">Nurse</option>
                <option value="Receptionist">Receptionist</option>
                <option value="Lab Technician">Lab Technician</option>
                <option value="Pharmacist">Pharmacist</option>
                <option value="Admin">Admin</option></select><span class="fi">🏷️</span>
            </div>
          </div>
          <div class="fg"><label>Password</label>
            <div class="fw"><input type="password" id="rPass" name="Password" placeholder="Min. 6 characters" oninput="pwStr(this.value)">
              <span class="fi">🔒</span><span class="eye" onclick="toggleEye('rPass',this)">👁</span></div>
            <div class="pw-str"><div class="pw-bar" id="pwBar"></div></div>
            <div class="pw-lbl" id="pwLbl">Password strength</div>
          </div>
          <div class="fg"><label>Confirm Password</label>
            <div class="fw"><input type="password" id="rConf" name="Confirm_Password" placeholder="Repeat password"><span class="fi">🔒</span></div>
            <div class="emsg" id="rConfE">Passwords do not match.</div>
          </div>
          <button class="btn-full" id="rBtn" type="submit" name="sb" style="margin-top:6px">
            <span id="rBtnTxt">Create Account →</span><span class="spin" id="rSpin"></span>
          </button>
        </div>
      </form>

      <!-- FORGOT -->
      <div class="form-panel" id="forgotPanel">
        <div class="form-title">Reset Password 🔐</div>
        <div class="form-subtitle">Enter your email to receive a reset link</div>
        <div class="fg"><label>Email Address</label>
          <div class="fw"><input type="email" id="fEmail" placeholder="you@hospital.com"><span class="fi">✉️</span></div>
        </div>
        <button class="btn-full" onclick="doForgot()">Send Reset Link →</button>
        <div style="text-align:center;margin-top:14px">
          <a href="#" class="flink" onclick="switchTab('login');return false">← Back to Sign In</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════ APP PAGE ══════════════ -->
<div id="appPage">
  <div class="sb-ov" id="sbOv" onclick="closeSidebar()"></div>

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sb-brand">
      <div class="sb-icon">🏥</div>
      <div class="sb-name">Medi<span>Core</span></div>
    </div>
    <nav class="sb-nav">
      <div class="nav-grp">
        <div class="nav-grp-lbl">Main</div>
        <button class="nav-item active" onclick="showPage('dashboard',this)"><span class="ni">📊</span>Dashboard</button>
        <button class="nav-item" onclick="showPage('appointments',this)"><span class="ni">📅</span>Appointments<span class="nbadge" id="apptBadge">0</span></button>
        <button class="nav-item" onclick="showPage('patients',this)"><span class="ni">👥</span>Patients</button>
        <button class="nav-item" onclick="showPage('doctors',this)"><span class="ni">🩺</span>Doctors</button>
      </div>
      <div class="nav-grp">
        <div class="nav-grp-lbl">Clinical</div>
        <button class="nav-item" onclick="showPage('opd',this)"><span class="ni">🏥</span>OPD<span class="nbadge" id="opdBadge">0</span></button>
        <button class="nav-item" onclick="showPage('ipd',this)"><span class="ni">🛏</span>IPD / Wards</button>
        <button class="nav-item" onclick="showPage('pharmacy',this)"><span class="ni">💊</span>Pharmacy</button>
        <button class="nav-item" onclick="showPage('lab',this)"><span class="ni">🔬</span>Laboratory</button>
        <button class="nav-item" onclick="showPage('billing',this)"><span class="ni">💳</span>Billing</button>
      </div>
      <div class="nav-grp">
        <div class="nav-grp-lbl">Admin</div>
        <button class="nav-item" onclick="showPage('inventory',this)"><span class="ni">🏪</span>Inventory</button>
        <button class="nav-item" onclick="showPage('staff',this)"><span class="ni">👨‍💼</span>Staff & HR</button>
        <button class="nav-item" onclick="showPage('reports',this)"><span class="ni">📈</span>Reports</button>
        <button class="nav-item" onclick="showPage('settings',this)"><span class="ni">⚙️</span>Settings</button>
      </div>
    </nav>
    <div class="sb-footer">
      <div class="user-chip">
        <div class="u-av" id="uAv">DR</div>
        <div style="flex:1;min-width:0"><div class="u-name" id="uName">Dr. User</div><div class="u-role" id="uRole">Doctor</div></div>
        <button class="logout-btn" onclick="doLogout()" title="Sign Out">🚪</button>
      </div>
    </div>
  </aside>

  <!-- TOPBAR -->
  <header class="topbar">
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <div class="tb-title" id="tbTitle">Dashboard</div>
    <div class="tb-space"></div>
    <div class="srch"><span>🔍</span><input type="text" placeholder="Search…"></div>
    <div style="display:flex;gap:8px">
      <div class="ib" title="Messages">📧<span class="ndot"></span></div>
      <div class="ib" title="Notifications">🔔<span class="ndot"></span></div>
    </div>
  </header>

  <!-- MAIN -->
  <main class="main-content">

    <!-- ═══ DASHBOARD ═══ -->
    <div class="page active" id="page-dashboard">
      <div class="ph"><h1>Good morning, <span id="greetName">Doctor</span> 👋</h1><p>Here's what's happening at your hospital today.</p></div>
      <div class="sg">
        <div class="sc" data-icon="👥"><div class="sc-lbl">Patients Today</div><div class="sc-val" id="dPat" style="color:var(--teal)">0</div><div class="sc-ch up">Live count</div></div>
        <div class="sc" data-icon="📅"><div class="sc-lbl">Appointments</div><div class="sc-val" id="dAppt" style="color:var(--gold)">0</div><div class="sc-ch up">Total booked</div></div>
        <div class="sc" data-icon="🛏"><div class="sc-lbl">OPD Tokens</div><div class="sc-val" id="dOpd" style="color:#7fa8ff">0</div><div class="sc-ch up">In queue</div></div>
        <div class="sc" data-icon="💰"><div class="sc-lbl">Bills Generated</div><div class="sc-val" id="dBill">0</div><div class="sc-ch up">Today</div></div>
      </div>
      <div class="two-grid">
        <div class="card"><div class="card-hdr"><h3>📊 Weekly Visits</h3></div>
          <div class="card-body">
            <div class="bar-chart" id="barChart"></div>
            <div style="display:flex;gap:7px;margin-top:7px" id="barLabels"></div>
          </div>
        </div>
        <div class="card" style="padding:0"><div class="mini-cal" id="miniCal"></div></div>
      </div>
      <div class="full-row">
        <div class="card"><div class="card-hdr"><h3>📅 Today's Appointments</h3><button class="btn-sm btn-p" onclick="openModal('apptModal')">+ Book New</button></div>
          <div class="card-body" id="dashApptList"><div style="color:var(--gray);font-size:.84rem;text-align:center;padding:20px">No appointments yet. Book one above! 📅</div></div>
        </div>
      </div>
      <div class="full-row">
        <div class="card"><div class="card-hdr"><h3>👥 Recent Patients</h3><button class="btn-sm btn-p" onclick="showPage('patients',null);setTimeout(()=>openModal('patModal'),100)">+ Register Patient</button></div>
          <div style="overflow-x:auto">
            <table><thead><tr><th>Name</th><th>ID</th><th>Age</th><th>Department</th><th>Status</th></tr></thead>
            <tbody id="dashPatTable"><tr class="empty-row"><td colspan="5">No patients registered yet. Register above! 👥</td></tr></tbody></table>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ APPOINTMENTS ═══ -->
    <div class="page" id="page-appointments">
      <div class="ph"><h1>📅 Appointments</h1><p>Book and manage patient appointments.</p></div>
      <div class="gap-bar">
        <button class="btn-sm btn-p" onclick="openModal('apptModal')">+ New Appointment</button>
        <button class="btn-sm btn-o">Export</button>
      </div>
      <div class="card"><div class="card-hdr"><h3>All Appointments</h3><span id="apptCount" style="font-size:.8rem;color:var(--gray)">0 records</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Patient</th><th>Doctor</th><th>Department</th><th>Date & Time</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="apptTable"><tr class="empty-row"><td colspan="7">No appointments yet. Click "+ New Appointment" to add one.</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ PATIENTS ═══ -->
    <div class="page" id="page-patients">
      <div class="ph"><h1>👥 Patients</h1><p>Complete patient registry and medical records.</p></div>
      <div class="gap-bar">
        <button class="btn-sm btn-p" onclick="openModal('patModal')">+ Register Patient</button>
        <button class="btn-sm btn-o">Export</button>
      </div>
      <div class="card"><div class="card-hdr"><h3>Patient Registry</h3><span id="patCount" style="font-size:.8rem;color:var(--gray)">0 records</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Name</th><th>ID</th><th>Age/Gender</th><th>Blood Type</th><th>Contact</th><th>Department</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="patTable"><tr class="empty-row"><td colspan="8">No patients registered yet. Click "+ Register Patient" to add one.</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ DOCTORS ═══ -->
    <div class="page" id="page-doctors">
      <div class="ph"><h1>🩺 Doctors</h1><p>Manage medical staff and specializations.</p></div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('docModal')">+ Add Doctor</button></div>
      <div class="card"><div class="card-hdr"><h3>Doctor Directory</h3><span id="docCount" style="font-size:.8rem;color:var(--gray)">0 records</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Name</th><th>Specialization</th><th>Experience</th><th>Schedule</th><th>Contact</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="docTable"><tr class="empty-row"><td colspan="7">No doctors added yet. Click "+ Add Doctor" to add one.</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ OPD ═══ -->
    <div class="page" id="page-opd">
      <div class="ph"><h1>🏥 OPD Management</h1><p>Outpatient department queue and tokens.</p></div>
      <div class="sg">
        <div class="sc" data-icon="⏳"><div class="sc-lbl">Waiting</div><div class="sc-val" id="opdWait" style="color:var(--gold)">0</div></div>
        <div class="sc" data-icon="✅"><div class="sc-lbl">Seen Today</div><div class="sc-val" id="opdSeen" style="color:var(--teal)">0</div></div>
        <div class="sc" data-icon="🔢"><div class="sc-lbl">Last Token</div><div class="sc-val" id="opdLast">—</div></div>
      </div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('opdModal')">+ Issue Token</button></div>
      <div class="card"><div class="card-hdr"><h3>OPD Queue</h3></div>
        <div class="card-body" id="opdQueue"><div style="color:var(--gray);text-align:center;padding:24px;font-size:.85rem">No tokens issued yet. Click "+ Issue Token" to start.</div></div>
      </div>
    </div>

    <!-- ═══ IPD ═══ -->
    <div class="page" id="page-ipd">
      <div class="ph"><h1>🛏 IPD / Ward Management</h1><p>Inpatient admissions and bed management.</p></div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('ipdModal')">+ New Admission</button></div>
      <div class="card"><div class="card-hdr"><h3>Current Admissions</h3><span id="ipdCount" style="font-size:.8rem;color:var(--gray)">0 admitted</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Bed</th><th>Ward</th><th>Patient</th><th>Admitted On</th><th>Doctor</th><th>Diagnosis</th><th>Condition</th><th>Action</th></tr></thead>
          <tbody id="ipdTable"><tr class="empty-row"><td colspan="8">No admissions yet. Click "+ New Admission" to add one.</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ PHARMACY ═══ -->
    <div class="page" id="page-pharmacy">
      <div class="ph"><h1>💊 Pharmacy</h1><p>Drug inventory, prescriptions and dispensing.</p></div>
      <div class="gap-bar">
        <button class="btn-sm btn-p" onclick="openModal('pharmaModal')">+ Add Medicine</button>
        <button class="btn-sm btn-o" onclick="openModal('rxModal')">+ Dispense Prescription</button>
      </div>
      <div class="card full-row"><div class="card-hdr"><h3>Medicine Inventory</h3><span id="pharmaCount" style="font-size:.8rem;color:var(--gray)">0 items</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Medicine</th><th>Category</th><th>Stock</th><th>Unit</th><th>Min Stock</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="pharmaTable"><tr class="empty-row"><td colspan="7">No medicines added yet. Click "+ Add Medicine".</td></tr></tbody></table>
        </div>
      </div>
      <div class="card"><div class="card-hdr"><h3>Dispensed Prescriptions</h3></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Rx #</th><th>Patient</th><th>Medicine</th><th>Qty</th><th>Doctor</th><th>Date</th></tr></thead>
          <tbody id="rxTable"><tr class="empty-row"><td colspan="6">No prescriptions dispensed yet.</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ LAB ═══ -->
    <div class="page" id="page-lab">
      <div class="ph"><h1>🔬 Laboratory</h1><p>Test orders and results management.</p></div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('labModal')">+ New Test Order</button></div>
      <div class="card"><div class="card-hdr"><h3>Test Orders</h3><span id="labCount" style="font-size:.8rem;color:var(--gray)">0 orders</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Order #</th><th>Patient</th><th>Test</th><th>Ordered By</th><th>Date</th><th>Result</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="labTable"><tr class="empty-row"><td colspan="8">No test orders yet. Click "+ New Test Order".</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ BILLING ═══ -->
    <div class="page" id="page-billing">
      <div class="ph"><h1>💳 Billing & Finance</h1><p>Invoices, payments and insurance claims.</p></div>
      <div class="sg">
        <div class="sc" data-icon="💰"><div class="sc-lbl">Total Billed</div><div class="sc-val" id="billTotal" style="color:var(--teal)">₨ 0</div></div>
        <div class="sc" data-icon="✅"><div class="sc-lbl">Paid</div><div class="sc-val" id="billPaid" style="color:var(--teal)">0</div></div>
        <div class="sc" data-icon="⏳"><div class="sc-lbl">Pending</div><div class="sc-val" id="billPend" style="color:var(--gold)">0</div></div>
        <div class="sc" data-icon="❌"><div class="sc-lbl">Overdue</div><div class="sc-val" id="billOver" style="color:var(--danger)">0</div></div>
      </div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('billModal')">+ Create Invoice</button></div>
      <div class="card"><div class="card-hdr"><h3>Invoices</h3><span id="billCount" style="font-size:.8rem;color:var(--gray)">0 invoices</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Invoice #</th><th>Patient</th><th>Services</th><th>Amount</th><th>Payment Method</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="billTable"><tr class="empty-row"><td colspan="7">No invoices yet. Click "+ Create Invoice" to add one.</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ INVENTORY ═══ -->
    <div class="page" id="page-inventory">
      <div class="ph"><h1>🏪 Inventory</h1><p>Medical supplies and equipment stock.</p></div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('invModal')">+ Add Item</button></div>
      <div class="card"><div class="card-hdr"><h3>Stock Overview</h3><span id="invCount" style="font-size:.8rem;color:var(--gray)">0 items</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Item</th><th>Category</th><th>Stock</th><th>Unit</th><th>Reorder Level</th><th>Supplier</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="invTable"><tr class="empty-row"><td colspan="8">No inventory items yet. Click "+ Add Item".</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ STAFF ═══ -->
    <div class="page" id="page-staff">
      <div class="ph"><h1>👨‍💼 Staff & HR</h1><p>Employee records and payroll management.</p></div>
      <div class="gap-bar"><button class="btn-sm btn-p" onclick="openModal('staffModal')">+ Add Staff</button></div>
      <div class="card"><div class="card-hdr"><h3>Staff Directory</h3><span id="staffCount" style="font-size:.8rem;color:var(--gray)">0 staff</span></div>
        <div style="overflow-x:auto">
          <table><thead><tr><th>Name</th><th>Employee ID</th><th>Department</th><th>Role</th><th>Contact</th><th>Shift</th><th>Salary</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="staffTable"><tr class="empty-row"><td colspan="9">No staff added yet. Click "+ Add Staff".</td></tr></tbody></table>
        </div>
      </div>
    </div>

    <!-- ═══ REPORTS ═══ -->
    <div class="page" id="page-reports">
      <div class="ph"><h1>📈 Reports & Analytics</h1><p>Generate hospital performance reports.</p></div>
      <div class="mod-grid">
        <div class="mod-card" onclick="genReport('Patient Summary')"><span class="mi">📊</span><span class="mt">Patient Summary</span><span class="ms">Monthly report</span></div>
        <div class="mod-card" onclick="genReport('Revenue Report')"><span class="mi">💰</span><span class="mt">Revenue Report</span><span class="ms">Financial summary</span></div>
        <div class="mod-card" onclick="genReport('Appointments')"><span class="mi">📅</span><span class="mt">Appointments</span><span class="ms">Trend analysis</span></div>
        <div class="mod-card" onclick="genReport('Pharmacy Usage')"><span class="mi">💊</span><span class="mt">Pharmacy Usage</span><span class="ms">Drug consumption</span></div>
        <div class="mod-card" onclick="genReport('Lab Tests')"><span class="mi">🔬</span><span class="mt">Lab Tests</span><span class="ms">Test statistics</span></div>
        <div class="mod-card" onclick="genReport('Bed Occupancy')"><span class="mi">🛏</span><span class="mt">Bed Occupancy</span><span class="ms">Ward utilization</span></div>
      </div>
      <div class="card" id="reportOutput" style="display:none"><div class="card-hdr"><h3 id="reportTitle"></h3></div><div class="card-body" id="reportBody"></div></div>
    </div>

    <!-- ═══ SETTINGS ═══ -->
    <div class="page" id="page-settings">
      <div class="ph"><h1>⚙️ Settings</h1><p>Manage hospital profile and preferences.</p></div>
      <div class="fsec"><div class="fsec-title">🏥 Hospital Profile</div>
        <div class="frow"><div class="ff"><label>Hospital Name</label><input type="text" value="MediCore City Hospital"></div><div class="ff"><label>Registration No.</label><input type="text" value="PMDC-2021-0234"></div></div>
        <div class="frow"><div class="ff"><label>Address</label><input type="text" value="123 Healthcare Ave, Karachi"></div><div class="ff"><label>Phone</label><input type="text" value="+92-21-35000000"></div></div>
        <div class="frow"><div class="ff"><label>Email</label><input type="email" value="admin@medicore.pk"></div><div class="ff"><label>Website</label><input type="text" value="www.medicore.pk"></div></div>
        <div class="action-bar"><button class="btn-sm btn-o">Cancel</button><button class="btn-sm btn-p" onclick="showToast('✅','Settings saved!','var(--teal)')">Save Changes</button></div>
      </div>
      <div class="fsec"><div class="fsec-title">🔐 Security</div>
        <div class="frow"><div class="ff"><label>Current Password</label><input type="password" placeholder="••••••••"></div><div class="ff"><label>New Password</label><input type="password" placeholder="••••••••"></div></div>
        <div class="action-bar"><button class="btn-sm btn-p" onclick="showToast('🔐','Password updated!','var(--teal)')">Update Password</button></div>
      </div>
    </div>

  </main>
</div>

<!-- ══════════════ MODALS ══════════════ -->

<!-- APPOINTMENT MODAL -->
<div class="modal-ov" id="apptModal">
  <div class="modal">
    <div class="modal-hdr"><h2>📅 Book Appointment</h2><button class="modal-close" onclick="closeModal('apptModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Patient Name</label><input type="text" id="aPatName" placeholder="Full name"><div class="merr" id="aPatNameE">Required</div></div>
        <div class="mfg"><label>Doctor</label>
          <select id="aDoctor"><option value="" disabled selected>Select doctor</option>
            <option>Dr. Kamran Malik</option><option>Dr. Fatima Rehman</option><option>Dr. Zeeshan Ahmed</option><option>Dr. Hassan Ali</option><option>Dr. Rehana Siddiqui</option>
          </select>
        </div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Department</label>
          <select id="aDept"><option value="" disabled selected>Select dept</option>
            <option>Cardiology</option><option>Orthopedics</option><option>Pediatrics</option>
            <option>Emergency</option><option>Gynecology</option><option>Neurology</option><option>General</option>
          </select>
        </div>
        <div class="mfg"><label>Appointment Type</label>
          <select id="aType"><option value="" disabled selected>Select type</option>
            <option>New Visit</option><option>Follow-up</option><option>Routine Check</option><option>Emergency</option><option>Consultation</option>
          </select>
        </div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Date</label><input type="date" id="aDate"></div>
        <div class="mfg"><label>Time</label><input type="time" id="aTime"></div>
      </div>
      <div class="mfg"><label>Status</label>
        <select id="aStatus"><option>Confirmed</option><option>Pending</option><option>Urgent</option><option>Scheduled</option></select>
      </div>
      <div class="mfg"><label>Notes (optional)</label><textarea id="aNotes" placeholder="Any additional notes…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('apptModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveAppt()">Book Appointment</button>
    </div>
  </div>
</div>

<!-- PATIENT MODAL -->
<div class="modal-ov" id="patModal">
  <div class="modal">
    <div class="modal-hdr"><h2>👥 Register Patient</h2><button class="modal-close" onclick="closeModal('patModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>First Name</label><input type="text" id="pFirst" placeholder="First name"><div class="merr" id="pFirstE">Required</div></div>
        <div class="mfg"><label>Last Name</label><input type="text" id="pLast" placeholder="Last name"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Date of Birth</label><input type="date" id="pDob"></div>
        <div class="mfg"><label>Gender</label>
          <select id="pGender"><option value="" disabled selected>Select</option><option>Male</option><option>Female</option><option>Other</option></select>
        </div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Blood Type</label>
          <select id="pBlood"><option value="" disabled selected>Select</option>
            <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
            <option>O+</option><option>O-</option><option>AB+</option><option>AB-</option>
          </select>
        </div>
        <div class="mfg"><label>Contact Number</label><input type="text" id="pContact" placeholder="+92-300-0000000"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Department</label>
          <select id="pDept"><option value="" disabled selected>Select</option>
            <option>Cardiology</option><option>Orthopedics</option><option>Pediatrics</option>
            <option>Emergency</option><option>Gynecology</option><option>Neurology</option><option>General</option>
          </select>
        </div>
        <div class="mfg"><label>Assigned Doctor</label>
          <select id="pDoctor"><option value="" disabled selected>Select</option>
            <option>Dr. Kamran Malik</option><option>Dr. Fatima Rehman</option><option>Dr. Zeeshan Ahmed</option><option>Dr. Hassan Ali</option>
          </select>
        </div>
      </div>
      <div class="mfg"><label>Address</label><input type="text" id="pAddr" placeholder="Street, City"></div>
      <div class="mfg"><label>Emergency Contact</label><input type="text" id="pEmerg" placeholder="Name & number"></div>
      <div class="mfg"><label>Medical History / Notes</label><textarea id="pNotes" placeholder="Allergies, chronic conditions, etc."></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('patModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="savePat()">Register Patient</button>
    </div>
  </div>
</div>

<!-- DOCTOR MODAL -->
<div class="modal-ov" id="docModal">
  <div class="modal">
    <div class="modal-hdr"><h2>🩺 Add Doctor</h2><button class="modal-close" onclick="closeModal('docModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Full Name</label><input type="text" id="dName" placeholder="Dr. Full Name"><div class="merr" id="dNameE">Required</div></div>
        <div class="mfg"><label>Specialization</label><input type="text" id="dSpec" placeholder="e.g. Cardiologist"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Experience (years)</label><input type="number" id="dExp" placeholder="e.g. 10"></div>
        <div class="mfg"><label>Contact</label><input type="text" id="dContact" placeholder="+92-300-0000000"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Email</label><input type="email" id="dEmail" placeholder="doctor@hospital.com"></div>
        <div class="mfg"><label>Schedule</label><input type="text" id="dSched" placeholder="e.g. Mon–Fri"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Department</label>
          <select id="dDept"><option value="" disabled selected>Select</option>
            <option>Cardiology</option><option>Orthopedics</option><option>Pediatrics</option>
            <option>Emergency</option><option>Gynecology</option><option>Neurology</option><option>General</option>
          </select>
        </div>
        <div class="mfg"><label>Status</label>
          <select id="dStatus"><option>Available</option><option>On Leave</option><option>Off Duty</option></select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('docModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveDoc()">Add Doctor</button>
    </div>
  </div>
</div>

<!-- OPD TOKEN MODAL -->
<div class="modal-ov" id="opdModal">
  <div class="modal">
    <div class="modal-hdr"><h2>🏥 Issue OPD Token</h2><button class="modal-close" onclick="closeModal('opdModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Patient Name</label><input type="text" id="oPatName" placeholder="Full name"><div class="merr" id="oPatNameE">Required</div></div>
        <div class="mfg"><label>Age</label><input type="number" id="oAge" placeholder="Age"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Doctor</label>
          <select id="oDoctor"><option value="" disabled selected>Select doctor</option>
            <option>Dr. Kamran Malik</option><option>Dr. Fatima Rehman</option><option>Dr. Zeeshan Ahmed</option><option>Dr. Hassan Ali</option>
          </select>
        </div>
        <div class="mfg"><label>Department</label>
          <select id="oDept"><option value="" disabled selected>Select dept</option>
            <option>Cardiology</option><option>Orthopedics</option><option>Pediatrics</option>
            <option>Emergency</option><option>Gynecology</option><option>General</option>
          </select>
        </div>
      </div>
      <div class="mfg"><label>Chief Complaint</label><textarea id="oComplaint" placeholder="Describe the patient's complaint…" style="min-height:70px"></textarea></div>
      <div class="mfg"><label>Priority</label>
        <select id="oPriority"><option>Normal</option><option>Urgent</option><option>Emergency</option></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('opdModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveOPD()">Issue Token</button>
    </div>
  </div>
</div>

<!-- IPD MODAL -->
<div class="modal-ov" id="ipdModal">
  <div class="modal">
    <div class="modal-hdr"><h2>🛏 New Admission</h2><button class="modal-close" onclick="closeModal('ipdModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Patient Name</label><input type="text" id="iPatName" placeholder="Full name"><div class="merr" id="iPatNameE">Required</div></div>
        <div class="mfg"><label>Age / Gender</label><input type="text" id="iAgeGen" placeholder="e.g. 45 / Male"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Ward</label>
          <select id="iWard"><option value="" disabled selected>Select ward</option>
            <option>Cardiology</option><option>General</option><option>Pediatrics</option><option>ICU</option><option>Maternity</option><option>Surgical</option>
          </select>
        </div>
        <div class="mfg"><label>Bed Number</label><input type="text" id="iBed" placeholder="e.g. A-12"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Assigned Doctor</label>
          <select id="iDoctor"><option value="" disabled selected>Select</option>
            <option>Dr. Kamran Malik</option><option>Dr. Fatima Rehman</option><option>Dr. Zeeshan Ahmed</option><option>Dr. Hassan Ali</option>
          </select>
        </div>
        <div class="mfg"><label>Admission Date</label><input type="date" id="iDate"></div>
      </div>
      <div class="mfg"><label>Diagnosis</label><input type="text" id="iDiagnosis" placeholder="Primary diagnosis"></div>
      <div class="mfg"><label>Condition</label>
        <select id="iCondition"><option>Stable</option><option>Critical</option><option>Improving</option><option>Serious</option></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('ipdModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveIPD()">Admit Patient</button>
    </div>
  </div>
</div>

<!-- PHARMACY MEDICINE MODAL -->
<div class="modal-ov" id="pharmaModal">
  <div class="modal">
    <div class="modal-hdr"><h2>💊 Add Medicine</h2><button class="modal-close" onclick="closeModal('pharmaModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Medicine Name</label><input type="text" id="mName" placeholder="Medicine name"><div class="merr" id="mNameE">Required</div></div>
        <div class="mfg"><label>Category</label>
          <select id="mCat"><option value="" disabled selected>Select</option>
            <option>Antibiotic</option><option>Analgesic</option><option>Antiviral</option><option>Hormone</option><option>Vitamin</option><option>Cardiac</option><option>Antidiabetic</option><option>Other</option>
          </select>
        </div>
      </div>
      <div class="m3col">
        <div class="mfg"><label>Stock Quantity</label><input type="number" id="mStock" placeholder="0"></div>
        <div class="mfg"><label>Unit</label>
          <select id="mUnit"><option>Strips</option><option>Vials</option><option>Tablets</option><option>Bottles</option><option>Pens</option><option>Boxes</option></select>
        </div>
        <div class="mfg"><label>Min Stock Level</label><input type="number" id="mMin" placeholder="0"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Price (₨)</label><input type="number" id="mPrice" placeholder="0"></div>
        <div class="mfg"><label>Expiry Date</label><input type="date" id="mExpiry"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('pharmaModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="savePharma()">Add Medicine</button>
    </div>
  </div>
</div>

<!-- DISPENSE PRESCRIPTION MODAL -->
<div class="modal-ov" id="rxModal">
  <div class="modal">
    <div class="modal-hdr"><h2>📋 Dispense Prescription</h2><button class="modal-close" onclick="closeModal('rxModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Patient Name</label><input type="text" id="rxPat" placeholder="Patient name"><div class="merr" id="rxPatE">Required</div></div>
        <div class="mfg"><label>Doctor</label>
          <select id="rxDoc"><option value="" disabled selected>Select</option>
            <option>Dr. Kamran Malik</option><option>Dr. Fatima Rehman</option><option>Dr. Zeeshan Ahmed</option>
          </select>
        </div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Medicine</label><input type="text" id="rxMed" placeholder="Medicine name"></div>
        <div class="mfg"><label>Quantity</label><input type="number" id="rxQty" placeholder="Qty"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('rxModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveRx()">Dispense</button>
    </div>
  </div>
</div>

<!-- LAB MODAL -->
<div class="modal-ov" id="labModal">
  <div class="modal">
    <div class="modal-hdr"><h2>🔬 New Test Order</h2><button class="modal-close" onclick="closeModal('labModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Patient Name</label><input type="text" id="lbPat" placeholder="Patient name"><div class="merr" id="lbPatE">Required</div></div>
        <div class="mfg"><label>Test Name</label><input type="text" id="lbTest" placeholder="e.g. CBC, LFT, X-Ray"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Ordered By</label>
          <select id="lbDoc"><option value="" disabled selected>Select</option>
            <option>Dr. Kamran Malik</option><option>Dr. Fatima Rehman</option><option>Dr. Zeeshan Ahmed</option><option>Dr. Hassan Ali</option>
          </select>
        </div>
        <div class="mfg"><label>Priority</label>
          <select id="lbPri"><option>Normal</option><option>Urgent</option><option>STAT</option></select>
        </div>
      </div>
      <div class="mfg"><label>Notes</label><textarea id="lbNotes" placeholder="Any special instructions…" style="min-height:60px"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('labModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveLab()">Place Order</button>
    </div>
  </div>
</div>

<!-- BILLING MODAL -->
<div class="modal-ov" id="billModal">
  <div class="modal">
    <div class="modal-hdr"><h2>💳 Create Invoice</h2><button class="modal-close" onclick="closeModal('billModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Patient Name</label><input type="text" id="bPat" placeholder="Patient name"><div class="merr" id="bPatE">Required</div></div>
        <div class="mfg"><label>Services Provided</label><input type="text" id="bServices" placeholder="e.g. Consultation + X-Ray"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Amount (₨)</label><input type="number" id="bAmount" placeholder="0"></div>
        <div class="mfg"><label>Payment Method</label>
          <select id="bMethod"><option>Cash</option><option>Card</option><option>Insurance</option><option>Online Transfer</option></select>
        </div>
      </div>
      <div class="mfg"><label>Status</label>
        <select id="bStatus"><option>Paid</option><option>Pending</option><option>Overdue</option></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('billModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveBill()">Generate Invoice</button>
    </div>
  </div>
</div>

<!-- INVENTORY MODAL -->
<div class="modal-ov" id="invModal">
  <div class="modal">
    <div class="modal-hdr"><h2>🏪 Add Inventory Item</h2><button class="modal-close" onclick="closeModal('invModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Item Name</label><input type="text" id="invName" placeholder="Item name"><div class="merr" id="invNameE">Required</div></div>
        <div class="mfg"><label>Category</label>
          <select id="invCat"><option value="" disabled selected>Select</option>
            <option>PPE</option><option>Consumable</option><option>Equipment</option><option>Furniture</option><option>Medicine</option><option>Other</option>
          </select>
        </div>
      </div>
      <div class="m3col">
        <div class="mfg"><label>Stock Qty</label><input type="number" id="invStock" placeholder="0"></div>
        <div class="mfg"><label>Unit</label><input type="text" id="invUnit" placeholder="Pcs / Boxes…"></div>
        <div class="mfg"><label>Reorder Level</label><input type="number" id="invReorder" placeholder="0"></div>
      </div>
      <div class="mfg"><label>Supplier</label><input type="text" id="invSupplier" placeholder="Supplier name"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('invModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveInv()">Add Item</button>
    </div>
  </div>
</div>

<!-- STAFF MODAL -->
<div class="modal-ov" id="staffModal">
  <div class="modal">
    <div class="modal-hdr"><h2>👨‍💼 Add Staff</h2><button class="modal-close" onclick="closeModal('staffModal')">✕</button></div>
    <div class="modal-body">
      <div class="m2col">
        <div class="mfg"><label>Full Name</label><input type="text" id="stName" placeholder="Full name"><div class="merr" id="stNameE">Required</div></div>
        <div class="mfg"><label>Department</label>
          <select id="stDept"><option value="" disabled selected>Select</option>
            <option>Cardiology</option><option>Orthopedics</option><option>Pediatrics</option>
            <option>Emergency</option><option>Pharmacy</option><option>Laboratory</option><option>Administration</option>
          </select>
        </div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Role</label>
          <select id="stRole"><option value="" disabled selected>Select</option>
            <option>Doctor</option><option>Nurse</option><option>Receptionist</option>
            <option>Lab Technician</option><option>Pharmacist</option><option>Admin</option><option>Cleaner</option>
          </select>
        </div>
        <div class="mfg"><label>Contact</label><input type="text" id="stContact" placeholder="+92-300-0000000"></div>
      </div>
      <div class="m2col">
        <div class="mfg"><label>Shift</label>
          <select id="stShift"><option>Morning</option><option>Evening</option><option>Night</option><option>Rotating</option></select>
        </div>
        <div class="mfg"><label>Salary (₨)</label><input type="number" id="stSalary" placeholder="e.g. 50000"></div>
      </div>
      <div class="mfg"><label>Status</label>
        <select id="stStatus"><option>Active</option><option>On Leave</option><option>Resigned</option></select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm btn-o" onclick="closeModal('staffModal')">Cancel</button>
      <button class="btn-sm btn-p" onclick="saveStaff()">Add Staff</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast-wrap" id="toastWrap"></div>

<script src="script.js"></script>
</body>
</html>
