<?php
if (empty($_SESSION['id'])) { header('Location: admin.php'); exit; }
$pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$stmt = $pdo->prepare('SELECT * FROM users WHERE uid = ?');
$stmt->execute([$_SESSION['id']]);
$user = $stmt->fetch();
if (!$user || !isset($_SESSION['username']) || $user['sess'] !== $_SESSION['username']) { header('Location: login.php'); exit; }
if (!in_array((int)$user['level'], [1, 2], true)) { http_response_code(403); exit('You need admin access to view this page.'); }
$status = isset($_GET['status']) && (string)$_GET['status'] === '0' ? 0 : 1;
$stmt = $pdo->prepare('SELECT c.*, s.skillname FROM consultants c LEFT JOIN skill s ON s.sid = c.skill WHERE c.status = :status ORDER BY c.cfname ASC');
$stmt->execute(['status' => $status]);
$consultants = $stmt->fetchAll();
$esc = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$showDate = static function ($value) {
    if (!$value || $value === '0000-00-00') return '—';
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $dt->format('m/d/Y') : '—';
};
require('includes/header.php');
require('includes/menu.php');
?>
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
 <div class="row"><ol class="breadcrumb"><li><a href="admin.php">Dashboard</a></li><li class="active">Consultants</li></ol></div>
 <div class="row"><div class="col-lg-12"><div class="panel panel-default">
 <div class="panel-heading">
 <a class="btn btn-primary" href="admin.php?action=addconsultant">Add a Consultant</a>
 <a class="btn btn-primary" href="admin.php?action=addskill">Add Skill</a>
 <a class="btn btn-primary" href="admin.php?action=assignconsultant">Assign Consultant</a>
 <a class="btn btn-default" href="admin.php?action=listconsultants&amp;status=<?= $status === 1 ? 0 : 1 ?>"><?= $status === 1 ? 'View Inactive' : 'View Active' ?></a>
 </div>
 <div class="panel-body">
 <table data-toggle="table" data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-pagination="true" data-sort-name="name" data-sort-order="asc">
 <thead><tr><th data-field="id" data-sortable="true">ID</th><th data-field="skill" data-sortable="true">Skill</th><th data-field="name" data-sortable="true">Name</th><th data-field="location" data-sortable="true">Location</th><th data-field="visa" data-sortable="true">Visa Status</th><th data-field="dob" data-sortable="true">DOB</th><th data-field="dateadded" data-sortable="true">Date Added</th><th data-field="details">Add Details / Edit</th></tr></thead>
 <tbody>
 <?php foreach ($consultants as $index => $row): $cid = (int)$row['cid']; ?>
 <tr>
 <td><?= $index + 1 ?></td>
 <td><?= $esc($row['skillname'] ?? '') ?></td>
 <td><?= $esc(trim(($row['cfname'] ?? '').' '.($row['cmname'] ?? '').' '.($row['clname'] ?? ''))) ?></td>
 <td><?= $esc($row['colocation']) ?></td>
 <td><?= $esc($row['covisa']) ?></td>
 <td data-order="<?= $esc($row['dob'] ?? '') ?>"><?= $esc($showDate($row['dob'] ?? '')) ?></td>
 <td data-order="<?= $esc($row['dateadded'] ?? '') ?>"><?= $esc($showDate($row['dateadded'] ?? '')) ?></td>
 <td>
 <a href="consultantdetails.php?add=marketdetails&amp;id=<?= $cid ?>">Marketing</a>&nbsp;
 <a href="consultantdetails.php?add=educationdetails&amp;id=<?= $cid ?>">Education</a>&nbsp;
 <a href="consultantdetails.php?add=files&amp;id=<?= $cid ?>">Other</a>&nbsp;
 <a href="consultantcmd.php?do=edit&amp;id=<?= $cid ?>" title="Edit"><img src="images/b_edit.png" alt="Edit" width="16" height="16"></a>
 <?php if ((int)$user['level'] === 1): ?>&nbsp;<a href="consultantcmd.php?do=delete&amp;id=<?= $cid ?>" title="Deactivate"><img src="images/b_drop.png" alt="Deactivate" width="16" height="16"></a><?php endif; ?>
 </td></tr>
 <?php endforeach; ?>
 </tbody></table>
 </div></div></div></div>
</div>
<?php require('includes/footer.php'); ?>
