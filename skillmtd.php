<?php

//MTD Skill wise Data

//MTD consultant wise Data
/*
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
$ins->bindValue( ":u", $sessid, PDO::PARAM_STR );
$ins->execute();
$dta = $ins->fetch();
$curdate =date('Y-m-d');

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

require("includes/header.php");
require("includes/menu.php");

if($dta['level'] == 1 || $dta['level'] == 2)
{
    $uid = $dta['uid'];
*/
require("includes/header.php");

    if(isset($_POST['date']))
    {
        $cdate = $_POST['date'];
    }
    else
    {
        $cdate = date("m/d/y");
    }
    $cdate = strtotime($cdate);
    $cdate = date('Y-m-d H:i:s',$cdate);
?>
<td width="90%" align="left" valign="top"> <form action="" method="post"><input name="date" id="datepicker"> <button name="submit" class="btn btn-primary">Submit</button></td> <td> Current date: <?php echo date("m/d/y",strtotime($cdate)); ?> </div>
<br>
<table border="1" cellpadding="1" cellspacing="1" style="width:500px">

    <thead>
		 <tr>
            <th>Skill</th>
            <th>App</th>
			<th>RC</th>
			<th>Sub</th>
        </tr>
    <tbody>
        <?php
$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
$q = "select * from skill";
$ins= $conn->prepare($q);
$ins->execute();
$data = $ins->fetchAll();

foreach( $data as $row) 
{

    $skill = $row['sid'];
    $app = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and ( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(end_date) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $rc = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and rcdone = 1 and ( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(end_date) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    $sub = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and rcdone = 1 and subdone = 1 and( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(end_date) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
    /*//$eci = $conn->query("select COUNT(*) from app_data AS A LEFT JOIN consultants AS B ON A.consultant_id = B.cid where B.skill = $skill and rcdone = 1 and subdone = 1 and hasinterview =1 and ( MONTH(A.appdate) = MONTH('$cdate') AND YEAR(end_date) = YEAR('$cdate') ) order by A.appdate asc")->fetchColumn();
        $qeci = "select distinct app_id from eci where `eci_happened` =1  and `eci_round` = 3  and `status` = 1";
        $ins= $conn->prepare($qeci);
        $ins->execute();
        $deci = $ins->fetchAll();
        $c=0;
        foreach($deci as $ueci)
        { $a = $ueci['app_id'];
        $date = $conn->query("SELECT eci_date FROM `eci` WHERE `eci_happened` =1 and `eci_round` = 3 and `app_id`= $a")->fetchColumn();
        if( date("m",strtotime($date)) == date("m",strtotime($cdate))  && date("y",strtotime($date)) == date("y",strtotime($cdate)) )
        {
            $c++;
        }
        }
        $eci = $c; */
        ?>
        <tr>
                    <td><?php echo $row['skillname']; ?></td>
                    <td><?php echo $app; ?></td>
                    <td><?php echo $rc; ?></td>
                    <td><?php echo $sub; ?></td>
                <!--  <td><?php // echo $eci; ?></td> -->
                </tr>
        <?php 

} ?>


		
	</tbody>
</table>

<p>&nbsp;</p>

<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/chart.min.js"></script>
	<!--<script src="js/chart-data.js"></script>
	 <script src="js/easypiechart.js"></script>
	<script src="js/easypiechart-data.js"></script> -->
	<script src="js/bootstrap-datepicker.js"></script>
	<script src="js/bootstrap-table.js"></script>
	<script>
		$('#calendar').datepicker({
		});

		!function ($) {
		    $(document).on("click","ul.nav li.parent > a > span.icon", function(){          
		        $(this).find('em:first').toggleClass("glyphicon-minus");      
		    }); 
		    $(".sidebar span.icon").find('em:first').addClass("glyphicon-plus");
		}(window.jQuery);

		$(window).on('resize', function () {
		  if ($(window).width() > 768) $('#sidebar-collapse').collapse('show')
		})
		$(window).on('resize', function () {
		  if ($(window).width() <= 767) $('#sidebar-collapse').collapse('hide')
		})
	</script>	
<?php

/*
}
else
{
	echo "<script>
alert('You Need to be Admin to view this page.');
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

*/

?>
