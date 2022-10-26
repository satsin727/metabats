<?php
if($_SESSION['id'])
{
$sessid = $_SESSION['id'];
}
else
{
	header( "Location: admin.php" ); 

}
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = :u";
$ins= $conn->prepare($query);
$ins->bindValue( ":u", $sessid, PDO::PARAM_INT );
$ins->execute();
$dta = $ins->fetch();


if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{
    if(isset($_GET['status']))
    { $status = $_GET['status']; }
    else { $status = 1; }
$query = "select * from issues where status = $status order by datetime desc";
$ins= $conn->prepare($query);
$ins->execute();
$data = $ins->fetchAll();
?>

<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
<div class="row">
			<ol class="breadcrumb">
				<li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
				<li class="active">Notices - Issues/Escalations/Process Violation</li>
			</ol>
</div>
<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading"><a href="admin.php?action=addissue"><button name="assignconsultant" class="btn btn-primary">Add Issue/Escalations</button></a></div>
					<div class="panel-body">
						<table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uDs" data-sort-order="asc">
						    <thead>
						    <tr>
						        <th data-field="uid">ID</th>								
						        <th data-field="date">Date</th>
						        <th data-field="headline" data-sortable="true">Issue/Escalations Headline</th>
						        <th data-field="skill" data-sortable="true">Related Skill</th>
						        <th data-field="name"  data-sortable="true">Related Consultant</th>
						        <th data-field="Manager" data-sortable="true">Added by Manager</th>
						        <th data-field="action" data-sortable="true">Actions</th>
						    </tr>
						    </thead>
						   <tbody>
<?php
$i=1;
foreach( $data as $row) { 
$cid=$row['consultant_id'];
$uid=$row['sm_id'];
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$query = "select * from users where `uid` = $uid";
$ins= $conn->prepare($query);
$ins->execute();
$udata = $ins->fetch();
$query2 = "select * from consultants where `cid` = $cid";
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
	?>
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
		<?php 

$time = strtotime($row['datetime']); 
$myFormatForView = date("m/d/y", $time); 

?>
		<td data-search="<?php echo $myFormatForView; ?>"> <?php echo $myFormatForView; ?></td>   
    	<td data-search="<?php echo $row['headline']; ?>"> <a href ="issuecmd.php?do=view&id=<?php echo $row['issueid']; ?>"><?php echo $row['headline']; ?></a></td>            
    	<td data-search="<?php echo $dta2['skillname']; ?>"> <?php echo $dta2['skillname']; ?></td>   
    	<td data-search="<?php echo $cdata['cfname']; ?>"> <?php echo $cdata['cfname']." ".$cdata['clname']; ?></td> 	
    	<td data-search="<?php echo $udata['name']; ?>"> <?php echo $udata['name']; ?></td>  
        <td> 
    		<a href="issuecmd.php?do=edit&id=<?php echo $row['issueid']; ?>"><img src="images/b_edit.png" alt="Change" width="16" height="16" border="0" title="Change" /></a>
    				 &nbsp;&nbsp;&nbsp; 
    			<?php if($dta['level'] == 1 || $dta['level'] == 2) { if($status==1){ ?>	<a href ="issuecmd.php?do=close&id=<?php  echo $row['issueid']; ?>" onClick="return confirm('Are you sure you want to Close this issue ?')"><img src="images/b_drop.png" alt="Close" width="16" height="16" border="0" title="Close"/></a> <?php } 
                else { ?> <a href ="issuecmd.php?do=reopen&id=<?php  echo $row['issueid']; ?>" onClick="return confirm('Are you sure you want to Re-Open this issue ?')"><img src="#" alt="Re-Open" width="16" height="16" border="0" title="Reopen"/></a> <?php } }?>
    				 &nbsp;&nbsp;&nbsp;    			
    	</td> 

    </tr>
    <?php  } 
    else 
    {
	?>      
    <tr>
  		<td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
        <td data-search="<?php echo $row['headline']; ?>"> <a href ="issuecmd.php?do=view&id=<?php echo $row['issueid']; ?>"><?php echo $row['headline']; ?></a></td>            
    	<td data-search="other">Open Issue</td>   
    	<td data-search="Open Issue">Open Issue</td> 	
    	<td data-search="<?php echo $udata['name']; ?>"> <?php echo $udata['name']; ?></td>  
        <td> 
    		<a href="issuecmd.php?do=edit&id=<?php echo $row['issueid']; ?>"><img src="images/b_edit.png" alt="Change" width="16" height="16" border="0" title="Change" /></a>
    				 &nbsp;&nbsp;&nbsp; 
    			<?php if($status==1){ ?>	<a href ="issuecmd.php?do=close&id=<?php  echo $row['issueid']; ?>" onClick="return confirm('Are you sure you want to Close this issue ?')"><img src="images/b_drop.png" alt="Close" width="16" height="16" border="0" title="Close"/></a> <?php } 
                else { ?> <a href ="issuecmd.php?do=reopen&id=<?php  echo $row['issueid']; ?>" onClick="return confirm('Are you sure you want to Re-Open this issue ?')"><img src="#" alt="Re-Open" width="16" height="16" border="0" title="Reopen"/></a> <?php } ?>
    				 &nbsp;&nbsp;&nbsp;    			
    	</td> 

    </tr>
    <?php  }
    
    //for if
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
alert('You Need to be Admin/Manager to view this page.');
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
