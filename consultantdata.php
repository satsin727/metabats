<?php
require( "config.php" );
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
$curdate =date('Y-m-d');
$conn=null;

if(isset($_SESSION['username']) && $dta['sess']==$_SESSION['username'])
{

		require("includes/header.php");
		$selected = "assigned";
		require("includes/menu.php");

        $olddate = 0;
        if(isset($_POST['date']))
        {
            $cdate = $_POST['date'];
            $olddate = 1;
            $cdate = strtotime($cdate);
            $curdate =date('Y-m-d',$cdate);
        }
        else
        {
            $curdate =date('Y-m-d');
        }

		if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
		{
				$uid = $dta['uid'];
				$cid = $_GET['id'];
                $query2 = "select * from consultants where `cid` = $cid";
                $ins2= $conn->prepare($query2);
                $ins2->execute();
				$cdata = $ins2->fetch();
				?>


				<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
						<div class="row">
							<ol class="breadcrumb">
								<li><a href=""><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
								<li class="active">Consultant Marketing Details: <?php echo $cdata['cfname'];  ?></li>
							</ol>
						</div><!--/.row-->
						
						<div class="row">
							<div class="col-lg-12">&nbsp;
							</div>
						</div><!--/.row-->
								
						
						<div class="row">
							<div class="col-lg-12">
								<div class="panel panel-default">
									<div class="panel-body">
                        <table>
                            <td width="30%">
								<label>Consultant Name:&nbsp;<?php echo $cdata['cfname']." ".$cdata['cmname']." ".$cdata['clname'];?> </label><br>
								<label>Location:&nbsp;<?php echo $cdata['cmlocation'];?> </label><br>
								<label>Email:&nbsp;<?php echo $cdata['cm_email'];?> </label><br>				
								<label>Phone:&nbsp;<?php echo $cdata['cm_phonenumber'];?> </label><br>
							    <label>DOB:&nbsp;<?php echo $cdata['dob'];?> </label><br> 
								<label>Skype ID:&nbsp;<?php echo $cdata['cm_email'];?> </label><br>
								<?php
								if($cdata['lastssn']!== '0' && isset($cdata['lastssn']))
								{ ?>
								<label>Last 4 SSN:&nbsp;<?php echo $cdata['lastssn'];?> </label><br>
								<?php } ?>
								<label>US Work Authorization:&nbsp;<?php echo $cdata['cmvisa'];?> </label><br>				
								<label>Relocation:&nbsp;<?php echo $cdata['relocation'];?> </label><br>
								<?php
														
								if($cdata['passportnumber']!== '0' && isset($cdata['passportnumber']) )
								{
								if($dta['level']==1 || $dta['level']==2 )
								{ ?>
								<label>Passport Number:&nbsp;<?php echo $cdata['passportnumber'];?> </label><br> 	 	
								<?php } ?>							
								<?php } ?>
								<?php 
								if($cdata['bachelordegree']!== '0' && isset($cdata['bachelordegree']))
								{ ?>
								<br>
								<label>Education Details:&nbsp;</label><br>			
								<label>Bachelor's:&nbsp;<?php echo $cdata['bachelordegree']." from ".$cdata['buniversity']." in ".$cdata['byear'];?> </label><br>
								<?php } ?>
								
								<?php 
								if($cdata['masterdegree']!== '0' && isset($cdata['masterdegree']) )
								{ ?>
								<br>		
								<label>Master's:&nbsp;<?php echo $cdata['masterdegree']." from ".$cdata['muniversity']." in ".$cdata['myear'];?> </label><br>
								<?php } ?>

								<?php 
								if($cdata['linkedin']!== '0' && isset($cdata['linkedin']))
								{ ?>		
								<label>Linkedin:&nbsp;<?php echo $cdata['linkedin'];?> </label><br>
								<?php } ?>
								<?php 
								if($cdata['resume']!== '0' && isset($cdata['resume']))
								{ ?>
								<label>Resume:&nbsp;</label> <a href="download.php?resume=<?php echo $cdata['resume']; ?>&cid=<?php echo $cdata['cid']; ?>"><?php  echo $cdata['cfname']." Resume"; ?></a><br>
								<?php } ?>
								
								<?php 
								if($cdata['visacopy']!== '0' && isset($cdata['visacopy']))
								{ ?>
								<label>Visa:&nbsp;</label> <a href="download.php?visacopy=<?php echo $cdata['visacopy']; ?>&cid=<?php echo $cdata['cid']; ?>"><?php  echo $cdata['cfname']." Visacopy"; ?></a><br>
								<?php } ?>
								<?php 
								if($cdata['dlcopy']!== '0' && isset($cdata['dlcopy']))
								{ ?>
								<label>DL:&nbsp;</label> <a href="download.php?dlcopy=<?php echo $cdata['dlcopy']; ?>&cid=<?php echo $cdata['cid']; ?>"><?php  echo $cdata['cfname']." DL copy"; ?></a><br>
								<?php } ?>
								<?php
								if($cdata['stateid']!== '0' && isset($cdata['stateid']))
								{ ?>
								<label>State ID:&nbsp;</label> <a href="download.php?stateid=<?php echo $cdata['stateid']; ?>&cid=<?php echo $cdata['cid']; ?>"><?php  echo $cdata['cfname']." State ID copy"; ?></a><br>
								<?php } ?>				
								<?php 
								if($cdata['passportcopy']!== '0' && isset($cdata['passportcopy']))
								{ ?>
								<label>Passport:&nbsp;</label> <a href="download.php?passportcopy=<?php echo $cdata['passportcopy']; ?>&cid=<?php echo $cdata['cid']; ?>"><?php  echo $cdata['cfname']." passportcopy"; ?></a><br>
								<?php } ?>				
								<label>Posting Email:&nbsp;<?php echo $cdata['cp_email'];?> </label><br>				
								<label>Posting Password:&nbsp;<?php echo $cdata['cp_password'];?> </label><br>
                            </td>
                            <td width = "100%">
                                <table>
                                <tr>
                                    <th>S.no</th>
                                    <th>App</th>                                    
                                    <th>RC</th>
                                    <th>Sub</th>
                                    <th>ECI</th>
                                </tr>
                                <tr>
                                    <td>Daily</td>
                                    <?php 
                                        $dapp = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid")->fetchColumn();
                                    ?>
                                    <td><?php echo $dapp; ?></td>
                                    <?php 
                                        $drc = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1")->fetchColumn();
                                    ?>
                                    <td><?php echo $drc; ?></td>
                                    <?php 
                                        $dsub = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1")->fetchColumn();
                                    ?>
                                    <td><?php echo $dsub; ?></td>
                                    <?php 
                                        $deci = $conn->query("SELECT COUNT(DISTINCT A.reqid) FROM `app_data` as A INNER JOIN `eci` as B ON A.app_id = B.app_id WHERE A.hasinterview = 1 and B.eci_round= 3 and B.eci_happened = 1 and A.consultant_id=$cid")->fetchColumn();
                                    ?>
                                    <td><?php echo $deci; ?></td>
                                </tr>
                                    
                                </table>
                            </td>

				</form>
										
								</div></div>
							</div><!-- /.col-->
						</div><!-- /.row -->
						
				</div>	
				<?php 


		} //for admin/manager
		else
		{
				echo "<script>
			alert('You Need to be a valid User to view this page.');
			window.location.href='admin.php';
			</script>"; 
		}

		?>
		

		<?php
		require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>