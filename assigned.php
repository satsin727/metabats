<?php
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); 

}

$conn=null;
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{
$uid = $dta['uid'];
$rmid = $dta['rmid'];
if($dta['level'] == 1 || $dta['level'] == 2 )
{
$query = "select * from assigned";
}
elseif ($dta['level'] == 3)
{
$query = "select * from assigned where `uid` = $rmid"; }
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
$conn=null;
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Assigned Consultants</li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="uid">ID</th>
						        <th data-field="name"  data-sortable="true">Consultant Name</th>
						        <th data-field="skill" data-sortable="true">Skill</th>
						        <th data-field="Manager" data-sortable="true">Assigned Manager</th>
						        <th data-field="Action" data-sortable="true">Details</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 
$cid=$row['cid'];
$uid=$row['uid'];
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = $uid AND `status` = 1";
$ins= $conn->prepare($query);
$ins->execute();
$udata = $ins->fetch();
$query2 = "select * from consultants where `cid` = $cid AND `status` = 1";
$ins2= $conn->prepare($query2);
$ins2->execute();
$cdata = $ins2->fetch();

if(isset($cdata['cid']) && isset($udata['uid']))
{
								$sid = $cdata['skill'];
								$q2 = "select * from skill where `sid` = $sid";
								$ins3= $conn->prepare($q2);
								$ins3->execute(); 
								$dta2 = $ins3->fetch(); 
								$conn=null; 
	?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
    	<td data-search="<?php echo $cdata['cfname']; ?>"> <?php echo $cdata['cfname']." ".$cdata['clname']; ?></td>
    	<td data-search="<?php echo $dta2['skillname']; ?>"> <?php echo $dta2['skillname']; ?></td>    	
    	<td data-search="<?php echo $udata['name']; ?>"> <?php echo $udata['name']; ?></td>    	<td> 
    		<a href="assigneddetails.php?id=<?php echo $row['id']; ?>">Get Details</a>			
    	</td> 
    </tr>
    <?php  } //for if
} //foreach
?>
						   </tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

</div>
<?php

}
else
{
	echo "<script>
alert('You Need to be valid user to view this page.');
window.location.href='admin.php';
</script>"; 
}
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>
