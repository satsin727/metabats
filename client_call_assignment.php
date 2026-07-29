<?php
session_start();
require_once("config.php");

if ($_SESSION['id']) {
    $sessid = $_SESSION['id'];
} else {
    header("Location: admin.php");
    exit;
}

$conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

$query = "SELECT * FROM users WHERE uid=:u";
$ins = $conn->prepare($query);
$ins->bindValue(":u", $sessid, PDO::PARAM_INT);
$ins->execute();
$dta = $ins->fetch(PDO::FETCH_ASSOC);

if (!isset($_SESSION['username']) || $dta['sess'] != $_SESSION['username']) {
    echo "<script>
    alert('Not Authorised to view this page.');
    window.location.href='login.php';
    </script>";
    exit;
}

require("includes/header.php");
require("includes/menu.php");

$search = '';

if(isset($_GET['search']))
{
    $search = trim($_GET['search']);
}
$where = "
WHERE c.status=1
AND LOWER(c.remail) NOT LIKE 'abc@%'
";

$params = array();
if($search!='')
{
    $where .= "
    AND
    (
    c.domain LIKE :search
    )
    ";

    $params[':search'] = "%".$search."%";
}

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';

if ($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) {

    $uid = $dta['uid'];

    if ($dta['level'] == 1 || $dta['level'] == 2) {

        $sql = "
            SELECT
            c.*,
            h.call_datetime,
            h.connected,
            u.name AS called_by
            FROM clients c
            LEFT JOIN
            (
                SELECT h1.*
                FROM client_call_history h1
                INNER JOIN
                (
                    SELECT cid,
                    MAX(call_datetime) latest_call
                    FROM client_call_history
                    GROUP BY cid
                ) h2
                ON h1.cid=h2.cid
                AND h1.call_datetime=h2.latest_call
            ) h
            ON c.cid=h.cid
            LEFT JOIN users u
            ON h.uid=u.uid
            ".$where."
            LIMIT 500
        ";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

    } else {

        $sql = "
            SELECT
            c.*,
            h.call_datetime,
            h.connected,
            u.name AS called_by
            FROM clients c
            LEFT JOIN
            (
                SELECT h1.*
                FROM client_call_history h1
                INNER JOIN
                (
                    SELECT cid,
                    MAX(call_datetime) latest_call
                    FROM client_call_history
                    GROUP BY cid
                ) h2
                ON h1.cid=h2.cid
                AND h1.call_datetime=h2.latest_call
            ) h
            ON c.cid=h.cid
            LEFT JOIN users u
            ON h.uid=u.uid
            ".$where."
            AND c.uid=:uid
            LIMIT 500
        ";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        // Safely bind :uid for level 3 users
        $stmt->bindValue(":uid", $uid, PDO::PARAM_INT);
    }

    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php
if(isset($_GET['assigned']))
{
?>
<div class="alert alert-success">
<strong>Success!</strong>
<?php echo (int)$_GET['assigned']; ?> client(s) assigned successfully.
<?php
if(isset($_GET['duplicate']) && $_GET['duplicate']>0)
{
    echo "<br><strong>".(int)$_GET['duplicate']."</strong> duplicate assignment(s) skipped.";
}
if(isset($_GET['failed']) && $_GET['failed']>0)
{
    echo "<br><strong>".(int)$_GET['failed']."</strong> invalid client(s) skipped.";
}
?>
</div>
<?php
}

if(isset($_GET['msg']))
{
    if($_GET['msg']=="date")
    {
?>
<div class="alert alert-danger">Please select a call date.</div>
<?php
    }
    if($_GET['msg']=="noclients")
    {
?>
<div class="alert alert-danger">Please select at least one client.</div>
<?php
    }
}
?>

<div class="panel panel-default">
<div class="panel-body">

<!-- Wrapping the whole controls and table inside ONE form so inputs/buttons pass correctly -->
<form method="post" action="clientcallcmd.php?action=assign" id="assignmentForm">

<div class="row">
    <div class="col-md-3">
        <label>Call Date</label>
        <input type="date" name="call_date" id="call_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
    </div>

    <div class="col-md-3">
        <label>&nbsp;</label>
        <div>
            <button type="submit" class="btn btn-success">Assign Selected Clients</button>
        </div>
    </div>

    <div class="col-md-3">
        <div>
            <h4>Selected :<span id="selectedCount">0</span></h4>
            <button type="button" class="btn btn-primary" id="selectAll">Select All</button>
            <button type="button" class="btn btn-warning" id="clearAll">Clear</button>
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <input type="text" name="search" class="form-control" placeholder="Domain" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button type="submit" formaction="admin.php?action=clientassignment" formmethod="get" class="btn btn-primary">
            <span class="glyphicon glyphicon-search"></span> Search
        </button>
        <input type="hidden" name="action" value="clientassignment">
    </div>
</div>

<br>

<?php if($search!=''): ?>
<div class="alert alert-success">
Showing search results for <strong><?php echo htmlspecialchars($search); ?></strong> (Maximum 500 records)
</div>
<?php endif; ?>

<table data-toggle="table" data-search="true" data-pagination="true" data-show-columns="true" data-show-toggle="true" data-show-refresh="true" data-sort-name="Company" data-page-size="25" class="table table-bordered table-hover">
<thead>
<tr>
<th><input type="checkbox" id="masterCheck"></th>
<th data-field="id">S.no</th>
<th data-field="company" data-sortable="true">Company</th>
<th data-field="contact" data-sortable="true">Name</th>
<th data-field="email" data-sortable="true">Email</th>
<th data-field="phone" data-sortable="true">Phone</th>
<th data-field="domain" data-sortable="true" data-visible="false">Domain</th>
<th data-field="location" data-sortable="true" data-visible="false">Location</th>
<th data-field="timezone" data-sortable="true" data-visible="false">Timezone</th>
<th data-field="tier" data-sortable="true" data-visible="false">Tier</th>
<th data-field="lastcall" data-sortable="true">Last Called</th>
<th data-field="lastby" data-sortable="true">Last By</th>
<th data-field="status" data-sortable="true">Status</th>
<th data-field="history">Call History</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
foreach($clients as $row)
{
?>
<tr>
<td>
<input type="checkbox" class="clientCheck" name="client[]" value="<?php echo $row['cid']; ?>" data-company="<?php echo htmlspecialchars($row['companyname']); ?>">
</td>
<td><?php echo $i++; ?></td>
<td><?php echo htmlspecialchars($row['companyname']); ?></td>
<td><?php echo htmlspecialchars($row['rname']); ?></td>
<td><?php echo htmlspecialchars($row['remail']); ?></td>
<td><?php echo htmlspecialchars($row['rphone']); ?></td>
<td><?php echo htmlspecialchars($row['domain']); ?></td>
<td><?php echo htmlspecialchars($row['rlocation'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($row['rtimezon'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($row['tier'] ?? ''); ?></td>
<td>
<?php
if(empty($row['call_datetime'])) {
    echo "<span class='text-muted'>Never</span>";
} else {
    echo date("d-M-Y",strtotime($row['call_datetime']));
}
?>
</td>
<td><?php echo empty($row['called_by']) ? "-" : htmlspecialchars($row['called_by']); ?></td>
<td>
<?php
if(empty($row['call_datetime'])) {
    echo '<span class="label label-default">Never Called</span>';
} elseif($row['connected']==1) {
    echo '<span class="label label-success">Connected</span>';
} else {
    echo '<span class="label label-danger">Not Connected</span>';
}
?>
</td>
<td>
<a href="admin.php?action=clienthistory&cid=<?php echo $row['cid']; ?>" class="btn btn-xs btn-info">
<span class="glyphicon glyphicon-time"></span> History
</a>
</td>
</tr>
<?php
}
?>

</tbody>
</table>

<input type="hidden" name="assigned_by" value="<?php echo $sessid; ?>">

</form>

</div>
</div>

<script>
function updateSelectedCount() {
    document.getElementById("selectedCount").innerHTML = document.querySelectorAll(".clientCheck:checked").length;
}

$(document).on("change", ".clientCheck", function () {
    updateSelectedCount();
});

$(document).on("change", "#masterCheck", function () {
    $(".clientCheck").prop("checked", $(this).prop("checked"));
    updateSelectedCount();
});

$("#selectAll").click(function(){
    $(".clientCheck").prop("checked", true);
    $("#masterCheck").prop("checked", true);
    updateSelectedCount();
});

$("#clearAll").click(function(){
    $(".clientCheck").prop("checked", false);
    $("#masterCheck").prop("checked", false);
    updateSelectedCount();
});

$("#assignmentForm").submit(function(e){
    // Only validate selection if the submit button triggered the assignment action
    if(document.activeElement && document.activeElement.type === "submit" && document.activeElement.innerText.includes("Assign")) {
        if($(".clientCheck:checked").length == 0) {
            alert("Please select at least one client.");
            e.preventDefault();
            return false;
        }
        return confirm("Assign selected clients to the selected date?");
    }
});
</script>

<?php
}
else
{
    echo "<script>
    alert('You Need to be Admin to view this page.');
    window.location.href='admin.php';
    </script>";
}

echo "</div>";

require("includes/footer.php");
?>