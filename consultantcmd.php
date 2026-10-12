<?php
require('config.php');
if (empty($_SESSION['id'])) { header('Location: admin.php'); exit; }
$pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$userStmt = $pdo->prepare('SELECT * FROM users WHERE uid = ?');
$userStmt->execute([$_SESSION['id']]);
$user = $userStmt->fetch();
if (!$user || !isset($_SESSION['username']) || $user['sess'] !== $_SESSION['username']) { header('Location: login.php'); exit; }
if (!in_array((int)$user['level'], [1, 2], true)) { http_response_code(403); exit('You need admin access to view this page.'); }
$action = $_GET['do'] ?? '';
$cid = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$cid || !in_array($action, ['edit', 'delete'], true)) { header('Location: admin.php?action=listconsultants'); exit; }
$esc = static function ($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
$errors = [];
$notice = '';
if (empty($_SESSION['consultant_csrf'])) { $_SESSION['consultant_csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['consultant_csrf'];
$recordStmt = $pdo->prepare('SELECT * FROM consultants WHERE cid = ?');
$recordStmt->execute([$cid]);
$record = $recordStmt->fetch();
if (!$record) { http_response_code(404); exit('Consultant not found.'); }

// These fields belong to the basic consultant form. Other columns are handled in separate marketing,
// education and document screens; never overwrite them implicitly.
$fields = [
 'cfname'=>'First Name', 'cmname'=>'Middle Name', 'clname'=>'Last Name',
 'dob'=>'Date of Birth', 'co_email'=>'Original Email', 'colocation'=>'Original Location',
 'co_phonenumber'=>'Original Phone Number', 'covisa'=>'Original Visa Status',
 'relocation'=>'Relocation', 'lastssn'=>'Last 5 SSN', 'passportnumber'=>'Passport Number',
 'passportcountry'=>'Passport Country', 'nda'=>'NDA Signed',

];
$dateFields = ['dob'];
$requiredDates = ['dob'];
$dateValue = static function ($value) {
    if ($value === null || $value === '' || $value === '0000-00-00') return '';
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $value : '';
};
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Invalid form token. Please reload.'); }
    if ($action === 'delete') {
        if ((string)($_POST['confirm_delete'] ?? '') === 'yes') {
            $stmt = $pdo->prepare('UPDATE consultants SET status = 0 WHERE cid = ?');
            $stmt->execute([$cid]);
            header('Location: admin.php?action=listconsultants'); exit;
        }
        $errors[] = 'Deletion was not confirmed.';
    } elseif (isset($_POST['update'])) {
        $updated = [];
        foreach ($fields as $name => $label) {
            $value = trim((string)($_POST[$name] ?? ''));
            if (in_array($name, $dateFields, true)) {
                if ($value === '') {
                    if (in_array($name, $requiredDates, true)) $errors[] = "$label is required.";
                } elseif (!$dateValue($value)) { $errors[] = "$label must be a valid YYYY-MM-DD date."; }
                if ($name === 'dob' && $dateValue($value) && $value > date('Y-m-d')) $errors[] = 'DOB cannot be in the future.';
            }
            if ($name === 'lastssn') {
                if ($value !== '' && !preg_match('/^[0-9]{1,5}$/', $value)) $errors[] = 'Last 5 SSN must contain at most five digits.';
                $value = $value === '' ? '0' : $value; // NOT NULL INT in existing schema.
            }
            if ($name === 'co_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) $errors[] = 'Original email is invalid.';
            if (in_array($name, ['cfname', 'clname', 'colocation', 'co_phonenumber', 'covisa', 'relocation', 'nda'], true) && $value === '') $errors[] = "$label is required.";
            $updated[$name] = $value;
        }
        $skill = filter_var($_POST['skill'] ?? null, FILTER_VALIDATE_INT);
        if (!$skill) $errors[] = 'Please select a skill.';
        else {
            $check = $pdo->prepare('SELECT COUNT(*) FROM skill WHERE sid = ?');
            $check->execute([$skill]);
            if (!$check->fetchColumn()) $errors[] = 'Selected skill is not valid.';
        }
        if (!$errors) {
            $updated['skill'] = $skill;
            $set = implode(', ', array_map(static fn($name) => "`$name` = :$name", array_keys($updated)));
            $stmt = $pdo->prepare("UPDATE consultants SET $set WHERE cid = :cid");
            $updated['cid'] = $cid;
            $stmt->execute($updated);
            header('Location: admin.php?action=listconsultants'); exit;
        }
        $record = array_merge($record, $updated);
    }
}
$skills = $pdo->query('SELECT sid, skillname FROM skill ORDER BY skillname')->fetchAll();
require('includes/header.php');
$selected = 'dashboard';
require('includes/menu.php');
?>
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
 <div class="row"><ol class="breadcrumb"><li><a href="admin.php">Dashboard</a></li><li class="active"><?= $esc(ucfirst($action)) ?> Consultant Basic Details</li></ol></div>
 <div class="row"><div class="col-lg-12"><div class="panel panel-default"><div class="panel-heading">Consultant Basic Details</div><div class="panel-body">
 <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= $esc($error) ?></div><?php endforeach; ?>
 <?php if ($action === 'delete'): ?>
 <form method="post"><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><p>Deactivate consultant <?= $esc(trim($record['cfname'].' '.$record['clname'])) ?>?</p><label><input type="checkbox" name="confirm_delete" value="yes" required> Confirm</label> <button type="submit" class="btn btn-danger">Deactivate</button></form>
 <?php else: ?>
 <form method="post" action="consultantcmd.php?do=edit&amp;id=<?= (int)$cid ?>">
 <input type="hidden" name="csrf" value="<?= $esc($csrf) ?>">
 <table width="100%" border="0" cellspacing="0" cellpadding="0">
 <?php
 $layout = [
   ['cfname','First Name'], ['cmname','Middle Name'], ['clname','Last Name'],
   ['skill','Role (Skill)'], ['dob','DOB'], ['co_email','Consultant Original Email'],
   ['colocation','Consultant Original Location'], ['co_phonenumber','Consultant Original Phone number'],
   ['covisa','Consultant Original visa'], ['relocation','Relocation'], ['lastssn','Last 5 SSN'],
   ['passportnumber','Passport Number'], ['passportcountry','Passport Country'], ['nda','NDA (Agreement) Signed']
 ];
 foreach ($layout as [$name,$label]):
 $value = $record[$name] ?? '';
 if ($name === 'dob') $value = $dateValue($value);
 ?>
 <tr><td width="25%" align="left" valign="top"><label for="<?= $esc($name) ?>"><?= $esc($label) ?><?= $name === 'dob' ? ' (MM/DD/YYYY)' : '' ?>:&nbsp;&nbsp;&nbsp;</label></td>
 <td width="90%" align="left" valign="top">
 <?php if ($name === 'skill'): ?>
 <select id="skill" name="skill" class="form-control-in" required>
 <?php foreach ($skills as $skill): ?><option value="<?= (int)$skill['sid'] ?>" <?= (int)$record['skill'] === (int)$skill['sid'] ? 'selected' : '' ?>><?= $esc($skill['skillname']) ?></option><?php endforeach; ?>
 </select>
 <?php elseif ($name === 'nda'): ?>
 <select id="nda" name="nda" class="form-control-in">
 <?php foreach (['Yes','No','Not Required'] as $choice): ?><option value="<?= $esc($choice) ?>" <?= $value === $choice ? 'selected' : '' ?>><?= $esc($choice) ?></option><?php endforeach; ?>
 </select>
 <?php else: ?>
 <input id="<?= $esc($name) ?>" name="<?= $esc($name) ?>" class="form-control-in" type="<?= $name === 'dob' ? 'date' : ($name === 'co_email' ? 'email' : 'text') ?>" value="<?= $esc($value) ?>" <?= $name === 'dob' ? 'required max="'.date('Y-m-d').'"' : '' ?> <?= $name === 'lastssn' ? 'inputmode="numeric" maxlength="5"' : '' ?>>
 <?php endif; ?>
 </td></tr><tr><td colspan="2">&nbsp;</td></tr>
 <?php endforeach; ?>
 <tr><td align="left"><button type="submit" name="update" value="1" class="btn btn-primary">Update</button></td><td></td></tr>
 </table>
 </form>
 <?php endif; ?>
 </div></div></div></div>
</div>
<?php require('includes/footer.php'); ?>
