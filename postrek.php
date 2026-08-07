<?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Strict Authentication Check
if (empty($_SESSION['id'])) {
    header("Location: admin.php");
    exit(); // Always stop execution after header redirect
}

$sessid = $_SESSION['id'];

// 2. Reuse a Single Database Connection
try {
		$conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		]);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Fetch user authorization details
$ins = $conn->prepare("SELECT * FROM users WHERE `uid` = :u");
$ins->bindValue(":u", $sessid, PDO::PARAM_INT);
$ins->execute();
$dta = $ins->fetch();

if (!isset($_SESSION['username']) || $dta['sess'] !== $_SESSION['username']) {
    echo "<script>
            alert('Not Authorised to view this page. Invalid session. Please log in again.');
            window.location.href='login.php';
          </script>";
    exit();
}

// Handle Form Submission BEFORE sending HTML output
if (isset($_POST['save'])) {
    // Added `string` type hint
		function checkemail(string $str): bool {
			return filter_var(trim($str), FILTER_VALIDATE_EMAIL) !== false;
		}
	
    if (
        empty($_POST['jobtype']) || 
        empty($_POST['rlocation']) || 
        empty($_POST['rduration']) || 
        empty($_POST['rdesc']) || 
        empty($_POST['skillid']) || 
        empty($_POST['cemail']) || 
        !checkemail($_POST['cemail'])
    ) {
        echo "<script>alert('All required fields must be filled correctly. Please check email and other details.');</script>";
    } else {
        $jobtype     = (int)$_POST['jobtype'];
        $rlocation   = trim($_POST['rlocation']);
        $rduration   = trim($_POST['rduration']);
        $rrate       = !empty($_POST['rrate']) ? (int)$_POST['rrate'] : null;
        $rend_client = trim($_POST['rend_client']);
        $rdesc       = $_POST['rdesc'];
        $skillid     = (int)$_POST['skillid'];
        $uid         = (int)$_POST['uid'];
        $remail      = strtolower(trim($_POST['cemail']));
        $domain      = substr($remail, strpos($remail, '@') + 1);
        $ttype       = (int)$_POST['ttype'];
        $req_source  = (int)$_POST['req_source'];
        $nationality = (int)$_POST['nationality'];
        if($jobtype==1)
            {
                $emp_type    = "C2C";
            }
        else if($jobtype==2)
            {
                $emp_type    = "C2H";
            }
        else if($jobtype==3)
            {
                $emp_type    = "W2";
            }
        else if($jobtype==4)
            {
                $emp_type    = "FTE";
            }


        $currentdatetime = date('Y-m-d H:i:s');


        // Check existing client
        $ins = $conn->prepare("SELECT `cid` FROM clients WHERE `remail` = :remail");
        $ins->bindValue(":remail", $remail, PDO::PARAM_STR);
        $ins->execute();
        $cdta = $ins->fetch();

        if ($cdta && isset($cdta['cid'])) {
            $cid = $cdta['cid'];
        } else {
            // Fetch default lid for user
            $userQuery = $conn->prepare("SELECT `def_lid` FROM users WHERE `uid` = :u");
            $userQuery->bindValue(":u", $uid, PDO::PARAM_INT);
            $userQuery->execute();
            $userData = $userQuery->fetch();
            $lid = $userData['def_lid'] ?? null;

            // Safe parameterized query for client insert
            $cinsertquery = $conn->prepare("INSERT INTO `clients` 
                (`lid`, `uid`, `companyname`, `rname`, `rfname`, `remail`, `domain`, `rphone`, `rlocation`, `rtimezon`, `tier`, `status`, `filetarget`) 
                VALUES (:lid, :uid, NULL, NULL, NULL, :remail, :domain, NULL, NULL, NULL, NULL, '1', 'manual')");
            
            $cinsertquery->bindValue(":uid", $uid, PDO::PARAM_INT);
            $cinsertquery->bindValue(":lid", $lid, PDO::PARAM_INT);
            $cinsertquery->bindValue(":remail", $remail, PDO::PARAM_STR);
            $cinsertquery->bindValue(":domain", $domain, PDO::PARAM_STR);
            $cinsertquery->execute();
            $cid = $conn->lastInsertId();
        }

        // Insert Requirement record
        $que = $conn->prepare("INSERT INTO `req` 
            (`uid`, `cid`, `emp_type`, `jobtype`, `rlocation`, `rduration`, `rrate`, `rend_client`, `skillid`, `req_source`, `ttype`, `nationality`, `datetime`) 
            VALUES (:uid, :cid, :emp_type, :jobtype, :rlocation, :rduration, :rrate, :rend_client, :skillid, :req_source, :ttype, :nationality, :datetime)");
        
        $que->bindValue(":uid", $uid, PDO::PARAM_INT);
        $que->bindValue(":cid", $cid, PDO::PARAM_INT);
        $que->bindValue(":emp_type", $emp_type, PDO::PARAM_STR);
        $que->bindValue(":jobtype", $jobtype, PDO::PARAM_INT);
        $que->bindValue(":rlocation", $rlocation, PDO::PARAM_STR);
        $que->bindValue(":rduration", $rduration, PDO::PARAM_STR);
        $que->bindValue(":rrate", $rrate, PDO::PARAM_INT);
        $que->bindValue(":rend_client", $rend_client, PDO::PARAM_STR);
        $que->bindValue(":skillid", $skillid, PDO::PARAM_INT);
        $que->bindValue(":req_source", $req_source, PDO::PARAM_INT);
        $que->bindValue(":ttype", $ttype, PDO::PARAM_INT);
        $que->bindValue(":nationality", $nationality, PDO::PARAM_INT);
        $que->bindValue(":datetime", $currentdatetime, PDO::PARAM_STR);
        $que->execute();

        $reqid = $conn->lastInsertId();

        // Insert Job Description record
        $que1 = $conn->prepare("INSERT INTO `jd` (`reqid`, `rdesc`) VALUES (:reqid, :rdesc)");
        $que1->bindValue(":reqid", $reqid, PDO::PARAM_INT);
        $que1->bindValue(":rdesc", $rdesc, PDO::PARAM_STR);
        $que1->execute();

        echo "<script>
                alert('Requirement Added.');
                window.location.href='admin.php?action=showreqs';
              </script>";
        exit();
    }
}

require("includes/header.php");
require("includes/menu.php");
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">        
    <div class="row">
        <ol class="breadcrumb">
            <li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
            <li class="active">Post Requirement</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-body">

                    <form action="#" method="post">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td width="15%" align="left" valign="top"><label>Skill:</label></td>
                                <td width="85%" align="left" valign="top">
                                    <select name="skillid" class="form-control-in">
                                        <?php
                                        $ins2 = $conn->prepare("SELECT * FROM skill");
                                        $ins2->execute();
                                        $skills = $ins2->fetchAll();
                                        foreach ($skills as $row2) { ?>
                                            <option value="<?php echo htmlspecialchars($row2['sid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($row2['skillname'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <?php if ($dta['level'] == 1) { ?>
                                        <a href="admin.php?action=addskill">Add Skill</a>
                                    <?php } ?>
                                    <label>SM:&nbsp;&nbsp;&nbsp;&nbsp;</label>
                                    <select name="uid" class="form-control-in">
                                        <?php
                                        if ($dta['level'] == 1) {
                                            $ins2 = $conn->prepare("SELECT * FROM users");
                                            $ins2->execute();
                                            $users = $ins2->fetchAll();
                                            foreach ($users as $row2) { ?>
                                                <option value="<?php echo htmlspecialchars($row2['uid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($row2['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php }
                                        } else { ?>
                                            <option value="<?php echo htmlspecialchars($dta['uid'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($dta['name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td width="15%" align="left" valign="top"><label>Location:</label></td>
                                <td width="85%" align="left" valign="top">
                                    <input name="rlocation" class="form-control-in" placeholder="Location">
                                    <label>&nbsp;&nbsp;Duration:&nbsp;&nbsp;</label>
                                    <input name="rduration" class="form-control-in" placeholder="Duration in Months">
                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td width="15%" align="left" valign="top"><label>Job type:</label></td>
                                <td width="85%" align="left" valign="top">
                                    <select name="jobtype" class="form-control-in">
                                        <option value="1">Contract</option>
                                        <option value="2">Contract to hire</option>                                        
                                        <option value="3">W2</option>                                                                                
                                        <option value="4">FTE</option>
                                    </select>
                                    <label>&nbsp;&nbsp;Email:&nbsp;&nbsp;</label>
                                    <div class="search-box" style="display:inline-block;">
                                        <input name="cemail" type="text" autocomplete="off" placeholder="Email ID" />
                                        <div class="result"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td width="15%" align="left" valign="top"><label>Rate($/hr):</label></td>
                                <td width="85%" align="left" valign="top">
                                    <input name="rrate" class="form-control-in" placeholder="Rate only in numbers">
                                    <label>&nbsp;&nbsp;End Client:&nbsp;&nbsp;</label>
                                    <input name="rend_client" class="form-control-in" placeholder="End Client Name (No IP or PV name)">
                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td width="15%" align="left" valign="top"><label>Tier type:</label></td>
                                <td width="85%" align="left" valign="top">
                                    <select name="ttype" class="form-control-in">
                                        <option value="1">Tier 1</option>
                                        <option value="2">Tier 2</option>
                                    </select>
                                    <label>&nbsp;&nbsp;Nationality:&nbsp;&nbsp;</label>
                                    <select name="nationality" class="form-control-in">
                                        <option value="1">American</option>
                                        <option value="2">Indian</option>
                                    </select>
                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td width="15%" align="left" valign="top"><label>Requirement Source:</label></td>
                                <td width="85%" align="left" valign="top">
                                    <select name="req_source" class="form-control-in">
                                        <option value="1">Inbox</option>
                                        <option value="2">Posting</option>
                                        <option value="3">Cold Calls</option>
                                        <option value="4">AMC</option>
                                        <option value="5">Prohires</option>
                                        <option value="6">Google Groups</option>
                                        <option value="7">LinkedIn</option>
                                        <option value="8">Job Portal - Dice</option>
                                        <option value="9">Job Portal - Techfetch</option>
                                        <option value="10">Job Portal - SimplyHired</option>
                                        <option value="11">Job Portal - Careerbuilder</option>
                                        <option value="12">Job Portal - Ziprecruiter</option>
                                        <option value="13">Job Portal - Monster</option>
                                        <option value="14">Job Portal - other</option>                                       
                                        <option value="15">Company Websites</option>
                                        <option value="16">I-Labor</option>
                                         <option value="18">SATS</option>
                                        <option value="17">Other</option>
                                    </select>

                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td width="15%" align="left" valign="top"><label>Job Description:</label></td>
                                <td width="85%" align="left" valign="top">
                                    <textarea class="ckeditor" name="rdesc"></textarea>
                                </td>
                            </tr>
                            <tr><td><label>&nbsp;</label></td></tr>

                            <tr>
                                <td></td>
                                <td>
                                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                                </td>
                            </tr>
                        </table>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require("includes/footer.php"); ?>

<script>
$(document).ready(function(){
    $('.search-box input[type="text"]').on("keyup input", function(){
        var inputVal = $.trim($(this).val());
        var resultDropdown = $(this).siblings(".result");
        if(inputVal.length){
            $.get("backend-search.php", {term: inputVal}).done(function(data){
                resultDropdown.html(data);
            });
        } else {
            resultDropdown.empty();
        }
    });
    
    $(document).on("click", ".result p", function(){
        $(this).parents(".search-box").find('input[type="text"]').val($(this).text());
        $(this).parent(".result").empty();
    });
});
</script>