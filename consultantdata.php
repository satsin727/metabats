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
		$selected = "updatedhotlist";
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
                            <table data-toggle="table"  data-show-refresh="false" data-show-toggle="false" data-show-columns="false" data-search="false" data-select-item-name="#" data-pagination="false" data-sort-name="#" data-sort-order="asc">
                                <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>App</th>                                    
                                    <th>RC</th>
                                    <th>Sub</th>
                                    <th>ECI</th>
                                </tr>
                                </thead>
                                <tr>
                                    <td>Daily</td>
                                    <?php 
                                        $dapp = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and DATE(appdate) = DATE('$curdate') and `status` = 1")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&appcd_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $dapp; ?></button></center></a></td>
                                    <?php 
                                        $drc = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and DATE(appdate) = DATE('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&rccd_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $drc; ?></button></center></a></td>
                                    <?php 
                                        $dsub = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `rcdone` = 1 and DATE(appdate) = DATE('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&subcd_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $dsub; ?></button></center></a></td>
                                    <?php 
                                        $deci = $conn->query("SELECT COUNT(DISTINCT A.reqid) FROM `app_data` as A INNER JOIN `eci` as B ON A.app_id = B.app_id WHERE A.hasinterview = 1 and B.eci_round= 3 and B.eci_happened = 1 and A.consultant_id=$cid and DATE(B.eci_date) = DATE('$curdate') and A.status = 1")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&ecicd_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $deci; ?></button></center></a></td>
                                </tr>
                                <tr>
                                    <td>Weekly</td>
                                    <?php 
                                        $wapp = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&appcw_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $wapp; ?></button></center></a></td>
                                    <?php 
                                        $wrc = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&rccw_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $wrc; ?></button></center></a></td>
                                    <?php 
                                        $wsub = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `rcdone` = 1 and WEEK(appdate) = WEEK('$curdate') and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&subcw_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $wsub; ?></button></center></a></td>
                                    <?php 
                                        $weci = $conn->query("SELECT COUNT(DISTINCT A.reqid) FROM `app_data` as A INNER JOIN `eci` as B ON A.app_id = B.app_id WHERE A.hasinterview = 1 and B.eci_round= 3 and B.eci_happened = 1 and A.consultant_id=$cid and WEEK(B.eci_date) = WEEK('$curdate') and YEAR(B.eci_date) = YEAR('$curdate') and A.status = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&ecicw_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $weci; ?></button></center></a></td>
                                </tr>
                                <tr>
                                    <td>Monthly</td>
                                    <?php 
                                        $mapp = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&appcm_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $mapp; ?></button></center></a></td>
                                    <?php 
                                        $mrc = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&rccm_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $mrc; ?></button></center></a></td>
                                    <?php 
                                        $msub = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `rcdone` = 1 and MONTH(appdate) = MONTH('$curdate') and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&subcm_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $msub; ?></button></center></a></td>
                                    <?php 
                                        $meci = $conn->query("SELECT COUNT(DISTINCT A.reqid) FROM `app_data` as A INNER JOIN `eci` as B ON A.app_id = B.app_id WHERE A.hasinterview = 1 and B.eci_round= 3 and B.eci_happened = 1 and A.consultant_id=$cid and MONTH(B.eci_date) = MONTH('$curdate') and YEAR(B.eci_date) = YEAR('$curdate') and A.status = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&ecicm_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $meci; ?></button></center></a></td>
                                </tr>
                                <tr>
                                    <td>Yearly</td>
                                    <?php 
                                        $yapp = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&appcy_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $yapp; ?></button></center></a></td>
                                    <?php 
                                        $yrc = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&rccy_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $yrc; ?></button></center></a></td>
                                    <?php 
                                        $ysub = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `rcdone` = 1 and YEAR(appdate) = YEAR('$curdate') and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&subcy_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $ysub; ?></button></center></a></td>
                                    <?php 
                                        $yeci = $conn->query("SELECT COUNT(DISTINCT A.reqid) FROM `app_data` as A INNER JOIN `eci` as B ON A.app_id = B.app_id WHERE A.hasinterview = 1 and B.eci_round= 3 and B.eci_happened = 1 and A.consultant_id=$cid and YEAR(B.eci_date) = YEAR('$curdate') and A.status = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&ecicy_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $yeci; ?></button></center></a></td>
                                </tr>
                                <tr>
                                    <td>Total till date</td>
                                    <?php 
                                        $capp = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&appcall_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $capp; ?></button></center></a></td>
                                    <?php 
                                        $crc = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `rcdone` = 1 and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&rccall_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $crc; ?></button></center></a></td>
                                    <?php 
                                        $csub = $conn->query("SELECT COUNT(*) FROM `app_data` WHERE `consultant_id`= $cid and `subdone` = 1 and `rcdone` = 1 and `status` = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&subcall_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $csub; ?></button></center></a></td>
                                    <?php 
                                        $ceci = $conn->query("SELECT COUNT(DISTINCT A.reqid) FROM `app_data` as A INNER JOIN `eci` as B ON A.app_id = B.app_id WHERE A.hasinterview = 1 and B.eci_round= 3 and B.eci_happened = 1 and A.consultant_id=$cid  and A.status = 1 ")->fetchColumn();
                                    ?>
                                    <td><a href="consultanttracker.php?do=getdata&ecicall_id=<?php echo $cid; ?>" target="_blank"><center><button><?php echo $ceci; ?></button></center></a></td>
                                </tr>
                                    
                                    
                                </table>
                            </td>
                        <tr>
                            <td>
                                <a href="consultanttracker.php?do=getissues&allissues=<?php echo $cid; ?>" target="_blank"><button class="bootbutton">Submission Issues</button></a>&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="consultanttracker.php?do=getnotes&noteid=<?php echo $cid; ?>" target="_blank"><button class="bootbutton">Highlighted Notes</button></a>

                            </td>
                        </tr>
                        </table>

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