<?php
// ============================================================
//  MediCore API - api.php
//  Rakhein: index.php ke saath same folder mein
//  Database: Project (localhost)
// ============================================================

// PHP errors ko suppress karo taake JSON break na ho
error_reporting(0);
ini_set('display_errors', 0);

// Output buffer - koi bhi unwanted output (warnings/notices) catch karo
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// DB Connection
function getDB() {
    $con = mysqli_connect('localhost', 'root', '', 'Project');
    if (!$con) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'msg' => 'DB Connection Failed: ' . mysqli_connect_error()]);
        exit;
    }
    mysqli_set_charset($con, 'utf8mb4');
    return $con;
}

function res($ok, $msg = '', $data = []) {
    ob_end_clean(); // koi bhi PHP warning/notice output clear karo
    echo json_encode(['success' => $ok, 'msg' => $msg, 'data' => $data]);
    exit;
}

function safe($con, $val) {
    return mysqli_real_escape_string($con, trim($val));
}

// Auto ID Generator
function nextId($con, $table, $col, $prefix) {
    $plen = strlen($prefix) + 2;
    $r = mysqli_query($con, "SELECT MAX(CAST(SUBSTRING(`$col`, $plen) AS UNSIGNED)) as mx FROM `$table`");
    $row = mysqli_fetch_assoc($r);
    $n = ($row['mx'] ?? 0) + 1;
    return $prefix . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$con    = getDB();

switch ($action) {

    // === REGISTER ===
    case 'register':
        $fn   = safe($con, $_POST['First_Name'] ?? '');
        $ln   = safe($con, $_POST['Last_Name']  ?? '');
        $em   = safe($con, $_POST['Email']      ?? '');
        $role = safe($con, $_POST['Role']       ?? '');
        $pass = password_hash($_POST['Password'] ?? '', PASSWORD_DEFAULT);
        if (!$fn || !$em || !$role) res(false, 'Required fields missing.');
        $q = "INSERT INTO `Register` (First_Name,Last_Name,Email,Role,Password) VALUES ('$fn','$ln','$em','$role','$pass')";
        if (mysqli_query($con, $q)) res(true, 'Account created! Please sign in.');
        else res(false, 'Email already exists. ' . mysqli_error($con));
        break;

    // === LOGIN ===
    case 'login':
        $em   = safe($con, $_POST['Email']    ?? '');
        $pass = $_POST['Password'] ?? '';
        $r    = mysqli_query($con, "SELECT * FROM `Register` WHERE Email='$em' LIMIT 1");
        if ($row = mysqli_fetch_assoc($r)) {
            if (password_verify($pass, $row['Password'])) {
                unset($row['Password']);
                res(true, 'Login successful.', $row);
            }
        }
        res(false, 'Invalid email or password.');
        break;

    // === PATIENTS ===
    case 'save_patient':
        $pid  = nextId($con, 'patients', 'patient_id', 'PAT');
        $name = safe($con, $_POST['name']       ?? '');
        $age  = (int)($_POST['age']             ?? 0);
        $gen  = safe($con, $_POST['gender']     ?? '');
        $bl   = safe($con, $_POST['blood_type'] ?? '');
        $c2   = safe($con, $_POST['contact']    ?? '');
        $em   = safe($con, $_POST['email']      ?? '');
        $addr = safe($con, $_POST['address']    ?? '');
        $dept = safe($con, $_POST['department'] ?? '');
        $stat = safe($con, $_POST['status']     ?? 'Active');
        if (!$name) res(false, 'Patient name required.');
        $q = "INSERT INTO patients (patient_id,name,age,gender,blood_type,contact,email,address,department,status) VALUES ('$pid','$name',$age,'$gen','$bl','$c2','$em','$addr','$dept','$stat')";
        if (mysqli_query($con, $q)) res(true, "Patient $name registered.", ['patient_id' => $pid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_patients':
        $r = mysqli_query($con, "SELECT * FROM patients ORDER BY created_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_patient':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM patients WHERE id=$id");
        res(true, 'Patient deleted.');
        break;

    // === DOCTORS ===
    case 'save_doctor':
        $did   = nextId($con, 'doctors', 'doc_id', 'DOC');
        $name  = safe($con, $_POST['name']           ?? '');
        $spec  = safe($con, $_POST['specialization'] ?? '');
        $exp   = safe($con, $_POST['experience']     ?? '');
        $sched = safe($con, $_POST['schedule']       ?? '');
        $cont  = safe($con, $_POST['contact']        ?? '');
        $em    = safe($con, $_POST['email']          ?? '');
        $stat  = safe($con, $_POST['status']         ?? 'Available');
        if (!$name) res(false, 'Doctor name required.');
        $q = "INSERT INTO doctors (doc_id,name,specialization,experience,schedule,contact,email,status) VALUES ('$did','$name','$spec','$exp','$sched','$cont','$em','$stat')";
        if (mysqli_query($con, $q)) res(true, "Dr. $name added.", ['doc_id' => $did]);
        else res(false, mysqli_error($con));
        break;

    case 'get_doctors':
        $r = mysqli_query($con, "SELECT * FROM doctors ORDER BY created_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_doctor':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM doctors WHERE id=$id");
        res(true, 'Doctor removed.');
        break;

    // === APPOINTMENTS ===
    case 'save_appointment':
        $aid  = nextId($con, 'appointments', 'appt_id', 'APT');
        $pat  = safe($con, $_POST['patient']    ?? '');
        $doc  = safe($con, $_POST['doctor']     ?? '');
        $dept = safe($con, $_POST['department'] ?? '');
        $date = safe($con, $_POST['appt_date']  ?? '');
        $time = safe($con, $_POST['appt_time']  ?? '');
        $type = safe($con, $_POST['type']       ?? 'Consultation');
        $stat = safe($con, $_POST['status']     ?? 'Scheduled');
        $note = safe($con, $_POST['notes']      ?? '');
        if (!$pat) res(false, 'Patient name required.');
        $q = "INSERT INTO appointments (appt_id,patient,doctor,department,appt_date,appt_time,type,status,notes) VALUES ('$aid','$pat','$doc','$dept','$date','$time','$type','$stat','$note')";
        if (mysqli_query($con, $q)) res(true, "Appointment booked for $pat.", ['appt_id' => $aid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_appointments':
        $r = mysqli_query($con, "SELECT * FROM appointments ORDER BY appt_date DESC, appt_time DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_appointment':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM appointments WHERE id=$id");
        res(true, 'Appointment deleted.');
        break;

    case 'update_appointment_status':
        $id   = (int)($_POST['id'] ?? 0);
        $stat = safe($con, $_POST['status'] ?? 'Confirmed');
        mysqli_query($con, "UPDATE appointments SET status='$stat' WHERE id=$id");
        res(true, 'Status updated.');
        break;

    // === OPD ===
    case 'save_opd':
        $token = nextId($con, 'opd', 'token', 'OPD');
        $pat   = safe($con, $_POST['patient']    ?? '');
        $age   = (int)($_POST['age']             ?? 0);
        $doc   = safe($con, $_POST['doctor']     ?? '');
        $dept  = safe($con, $_POST['department'] ?? '');
        $comp  = safe($con, $_POST['complaint']  ?? '');
        $pri   = safe($con, $_POST['priority']   ?? 'Normal');
        if (!$pat) res(false, 'Patient name required.');
        $q = "INSERT INTO opd (token,patient,age,doctor,department,complaint,priority,status) VALUES ('$token','$pat',$age,'$doc','$dept','$comp','$pri','Waiting')";
        if (mysqli_query($con, $q)) res(true, "Token issued: $token", ['token' => $token]);
        else res(false, mysqli_error($con));
        break;

    case 'get_opd':
        $r = mysqli_query($con, "SELECT * FROM opd ORDER BY created_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'mark_opd_seen':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "UPDATE opd SET status='Seen' WHERE id=$id");
        res(true, 'Marked as seen.');
        break;

    case 'delete_opd':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM opd WHERE id=$id");
        res(true, 'OPD token removed.');
        break;

    // === IPD ===
    case 'save_ipd':
        $aid   = nextId($con, 'ipd', 'admission_id', 'IPD');
        $pat   = safe($con, $_POST['patient']        ?? '');
        $agGen = safe($con, $_POST['age_gender']     ?? '');
        $ward  = safe($con, $_POST['ward']           ?? '');
        $bed   = safe($con, $_POST['bed']            ?? '');
        $doc   = safe($con, $_POST['doctor']         ?? '');
        $date  = safe($con, $_POST['admission_date'] ?? date('Y-m-d'));
        $diag  = safe($con, $_POST['diagnosis']      ?? '');
        $cond  = safe($con, $_POST['condition']      ?? 'Stable');
        if (!$pat) res(false, 'Patient name required.');
        $q = "INSERT INTO ipd (admission_id,patient,age_gender,ward,bed,doctor,admission_date,diagnosis,`condition`) VALUES ('$aid','$pat','$agGen','$ward','$bed','$doc','$date','$diag','$cond')";
        if (mysqli_query($con, $q)) res(true, "$pat admitted. ID: $aid", ['admission_id' => $aid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_ipd':
        $r = mysqli_query($con, "SELECT * FROM ipd ORDER BY created_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'discharge_ipd':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "UPDATE ipd SET status='Discharged' WHERE id=$id");
        res(true, 'Patient discharged.');
        break;

    case 'delete_ipd':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM ipd WHERE id=$id");
        res(true, 'Record deleted.');
        break;

    // === PHARMACY ===
    case 'save_pharma':
        $mid    = nextId($con, 'pharmacy', 'med_id', 'MED');
        $name   = safe($con, $_POST['name']        ?? '');
        $cat    = safe($con, $_POST['category']    ?? '');
        $stock  = (int)($_POST['stock']            ?? 0);
        $unit   = safe($con, $_POST['unit']        ?? '');
        $min    = (int)($_POST['min_stock']        ?? 0);
        $price  = (float)($_POST['price']          ?? 0);
        $expiry = safe($con, $_POST['expiry_date'] ?? '');
        if (!$name) res(false, 'Medicine name required.');
        $q = "INSERT INTO pharmacy (med_id,name,category,stock,unit,min_stock,price,expiry_date) VALUES ('$mid','$name','$cat',$stock,'$unit',$min,$price,'$expiry')";
        if (mysqli_query($con, $q)) res(true, "$name added.", ['med_id' => $mid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_pharma':
        $r = mysqli_query($con, "SELECT * FROM pharmacy ORDER BY name ASC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_pharma':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM pharmacy WHERE id=$id");
        res(true, 'Medicine removed.');
        break;

    // === PRESCRIPTIONS / DISPENSE ===
    case 'save_rx':
        $rxid = nextId($con, 'prescriptions', 'rx_id', 'RX');
        $pat  = safe($con, $_POST['patient']  ?? '');
        $doc  = safe($con, $_POST['doctor']   ?? '');
        $med  = safe($con, $_POST['medicine'] ?? '');
        $qty  = (int)($_POST['quantity']      ?? 1);
        if (!$pat) res(false, 'Patient name required.');
        $q = "INSERT INTO prescriptions (rx_id,patient,doctor,medicine,quantity) VALUES ('$rxid','$pat','$doc','$med',$qty)";
        if (mysqli_query($con, $q)) {
            if ($med) mysqli_query($con, "UPDATE pharmacy SET stock=stock-$qty WHERE name='$med' AND stock>0");
            res(true, "Dispensed. ID: $rxid", ['rx_id' => $rxid]);
        } else res(false, mysqli_error($con));
        break;

    case 'get_rx':
        $r = mysqli_query($con, "SELECT * FROM prescriptions ORDER BY dispensed_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    // === LABORATORY ===
    case 'save_lab':
        $lid  = nextId($con, 'lab', 'lab_id', 'LAB');
        $pat  = safe($con, $_POST['patient']    ?? '');
        $test = safe($con, $_POST['test_name']  ?? '');
        $doc  = safe($con, $_POST['ordered_by'] ?? '');
        $pri  = safe($con, $_POST['priority']   ?? 'Normal');
        $note = safe($con, $_POST['notes']      ?? '');
        if (!$pat) res(false, 'Patient name required.');
        $q = "INSERT INTO lab (lab_id,patient,test_name,ordered_by,priority,notes) VALUES ('$lid','$pat','$test','$doc','$pri','$note')";
        if (mysqli_query($con, $q)) res(true, "Lab order $lid placed.", ['lab_id' => $lid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_lab':
        $r = mysqli_query($con, "SELECT * FROM lab ORDER BY ordered_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'update_lab_status':
        $id   = (int)($_POST['id'] ?? 0);
        $stat = safe($con, $_POST['status'] ?? 'Ready');
        mysqli_query($con, "UPDATE lab SET status='$stat' WHERE id=$id");
        res(true, 'Lab status updated.');
        break;

    case 'delete_lab':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM lab WHERE id=$id");
        res(true, 'Lab order deleted.');
        break;

    // === BILLING ===
    case 'save_bill':
        $bid    = nextId($con, 'billing', 'bill_id', 'INV');
        $pat    = safe($con, $_POST['patient']        ?? '');
        $svc    = safe($con, $_POST['services']       ?? '');
        $amt    = (float)($_POST['amount']            ?? 0);
        $method = safe($con, $_POST['payment_method'] ?? 'Cash');
        $stat   = safe($con, $_POST['status']         ?? 'Pending');
        if (!$pat) res(false, 'Patient name required.');
        $q = "INSERT INTO billing (bill_id,patient,services,amount,payment_method,status) VALUES ('$bid','$pat','$svc',$amt,'$method','$stat')";
        if (mysqli_query($con, $q)) res(true, "Invoice $bid generated.", ['bill_id' => $bid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_billing':
        $r = mysqli_query($con, "SELECT * FROM billing ORDER BY created_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_bill':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM billing WHERE id=$id");
        res(true, 'Bill deleted.');
        break;

    case 'update_bill_status':
        $id   = (int)($_POST['id'] ?? 0);
        $stat = safe($con, $_POST['status'] ?? 'Paid');
        mysqli_query($con, "UPDATE billing SET status='$stat' WHERE id=$id");
        res(true, 'Bill status updated.');
        break;

    // === INVENTORY ===
    case 'save_inventory':
        $iid     = nextId($con, 'inventory', 'inv_id', 'ITEM');
        $name    = safe($con, $_POST['item_name']     ?? '');
        $cat     = safe($con, $_POST['category']      ?? '');
        $stock   = (int)($_POST['stock']              ?? 0);
        $unit    = safe($con, $_POST['unit']          ?? '');
        $reorder = (int)($_POST['reorder_level']      ?? 0);
        $supp    = safe($con, $_POST['supplier']      ?? '');
        if (!$name) res(false, 'Item name required.');
        $q = "INSERT INTO inventory (inv_id,item_name,category,stock,unit,reorder_level,supplier) VALUES ('$iid','$name','$cat',$stock,'$unit',$reorder,'$supp')";
        if (mysqli_query($con, $q)) res(true, "$name added.", ['inv_id' => $iid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_inventory':
        $r = mysqli_query($con, "SELECT * FROM inventory ORDER BY item_name ASC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_inventory':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM inventory WHERE id=$id");
        res(true, 'Item deleted.');
        break;

    // === STAFF ===
    case 'save_staff':
        $sid    = nextId($con, 'staff', 'staff_id', 'STF');
        $name   = safe($con, $_POST['name']       ?? '');
        $dept   = safe($con, $_POST['department'] ?? '');
        $role   = safe($con, $_POST['role']       ?? '');
        $cont   = safe($con, $_POST['contact']    ?? '');
        $shift  = safe($con, $_POST['shift']      ?? 'Morning');
        $salary = (float)($_POST['salary']        ?? 0);
        $stat   = safe($con, $_POST['status']     ?? 'Active');
        if (!$name) res(false, 'Staff name required.');
        $q = "INSERT INTO staff (staff_id,name,department,role,contact,shift,salary,status) VALUES ('$sid','$name','$dept','$role','$cont','$shift',$salary,'$stat')";
        if (mysqli_query($con, $q)) res(true, "$name added to staff.", ['staff_id' => $sid]);
        else res(false, mysqli_error($con));
        break;

    case 'get_staff':
        $r = mysqli_query($con, "SELECT * FROM staff ORDER BY created_at DESC");
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    case 'delete_staff':
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($con, "DELETE FROM staff WHERE id=$id");
        res(true, 'Staff member removed.');
        break;

    // === DASHBOARD STATS ===
    case 'get_dashboard_stats':
        $today = date('Y-m-d');
        $stats = [
            'patients_total'     => mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM patients"))[0],
            'appointments_total' => mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM appointments"))[0],
            'opd_waiting'        => mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM opd WHERE status='Waiting' AND DATE(created_at)='$today'"))[0],
            'bills_today'        => mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM billing WHERE DATE(created_at)='$today'"))[0],
            'revenue_today'      => mysqli_fetch_row(mysqli_query($con, "SELECT COALESCE(SUM(amount),0) FROM billing WHERE status='Paid' AND DATE(created_at)='$today'"))[0],
            'ipd_admitted'       => mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM ipd WHERE status='Admitted'"))[0],
            'low_stock_meds'     => mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM pharmacy WHERE stock <= min_stock"))[0],
        ];
        res(true, '', $stats);
        break;

    // === REPORTS ===
    case 'get_reports':
        $type = safe($con, $_GET['type'] ?? 'billing');
        $from = safe($con, $_GET['from'] ?? date('Y-m-01'));
        $to   = safe($con, $_GET['to']   ?? date('Y-m-d'));
        $queryMap = [
            'billing'      => "SELECT * FROM billing WHERE DATE(created_at) BETWEEN '$from' AND '$to' ORDER BY created_at DESC",
            'appointments' => "SELECT * FROM appointments WHERE appt_date BETWEEN '$from' AND '$to' ORDER BY appt_date DESC",
            'opd'          => "SELECT * FROM opd WHERE DATE(created_at) BETWEEN '$from' AND '$to' ORDER BY created_at DESC",
            'patients'     => "SELECT * FROM patients WHERE DATE(created_at) BETWEEN '$from' AND '$to' ORDER BY created_at DESC",
            'ipd'          => "SELECT * FROM ipd WHERE admission_date BETWEEN '$from' AND '$to' ORDER BY admission_date DESC",
        ];
        if (!isset($queryMap[$type])) res(false, 'Invalid report type.');
        $r = mysqli_query($con, $queryMap[$type]);
        $rows = [];
        while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        res(true, '', $rows);
        break;

    default:
        res(false, 'Unknown action: ' . $action);
}

mysqli_close($con);
