	<?php if(isset($_SESSION['username'])) { 	?>
	
	<div id="sidebar-collapse" class="col-sm-3 col-lg-2 sidebar">
	<br> <br>
		<?php 

 $conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
 $query = "select * from users where `sess` = :sess";
 $ins= $conn->prepare($query);
 $ins->bindValue( ":sess", $_SESSION['username'], PDO::PARAM_STR );
 $ins->execute();
 $dta = $ins->fetch();

 if(isset($_GET['selected']))
{
	$selected = $_GET['selected'];
}
		?>
		<ul class="nav menu">
			<li class="<?php if($selected=="postreq") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=postreq"><svg class="glyph stroked dashboard-dial"><use xlink:href="#stroked-dashboard-dial"></use></svg>Post Req</a></li>
			<?php if($dta['level'] == 3)
{ ?>		<li class="<?php if($selected=="showreqs") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=showreqs"><svg class="glyph stroked dashboard-dial"><use xlink:href="#stroked-dashboard-dial"></use></svg>My Reqs</a></li>  <?php } ?>
			<li class="<?php if($selected=="showapplications") { echo "active"; } else { echo "parent"; } ?>" >
						<a href="admin.php?action=showapplications">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Applications
						</a>
			</li>
			<li class="<?php if($selected=="showrc") { echo "active"; } else { echo "parent"; } ?>" >
						<a href="admin.php?action=showrc">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Rate Confirmations
						</a>
			</li>
			<li class="<?php if($selected=="showsub") { echo "active"; } else { echo "parent"; } ?>" >
						<a href="admin.php?action=showsub">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Submission
						</a>
			</li>
			<li class="<?php if($selected=="showeci") { echo "active"; } else { echo "parent"; } ?>" >
						<a href="admin.php?action=showeci">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Interviews
						</a>
			</li>
			<li class="<?php if($selected=="assigned") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=assigned"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg>My Consultants</a></li>
			<li class="<?php if($selected=="updatedhotlist") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=updatedhotlist"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg>Updated Hotlist</a></li>
<?php if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{ ?> <li class="<?php if($selected=="listall") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=listall"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg>Add Client</a></li> <?php } ?>

<?php if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{ ?> <li class="<?php if($selected=="clientslist") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=clientslist"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg>My Clients</a></li> <?php } ?>


<li class="<?php if($selected=="showallreqs") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=showallreqs"><svg class="glyph stroked dashboard-dial"><use xlink:href="#stroked-dashboard-dial"></use></svg>All Reqs</a></li>

	<!--		<li class="<?php if($selected=="updatedhotlist") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=updatedhotlist"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg>Updated Hotlist</a></li>
-->
<?php if($dta['level'] == 1 || $dta['level'] == 2)
{ ?>
			<li class="<?php if($selected=="assign") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=assign"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg>Assign Consultants</a></li> 

			<li class="<?php if($selected=="listconsultants") { echo "active"; } else { echo "parent"; } ?>">
				<a href="admin.php?action=listconsultants">
					<svg class="glyph stroked chevron-down"><use xlink:href="#stroked-male-user"></svg>All Consultants</use></span>
				</a>
			</li> <?php } ?> 
			
			<?php if($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3)
{ ?>
<li class="<?php if($selected=="showreports") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=showreports"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg>Daily Reports</a></li> 

<!--
<li class="<?php if($selected=="showreports") { echo "active"; } else { echo "parent"; } ?>">
				<a href="#">
					<span data-toggle="collapse" href="#sub-item-2"><svg class="glyph stroked chevron-down"><use xlink:href="#stroked-chevron-down"></svg>My Reports       </use></span>
				</a>
				<ul class="children collapse" id="sub-item-2">
					<li>
						<a class="<?php if($selected=="consultantwise") { echo "active"; } else { echo "parent"; } ?>" href="admin.php?action=showcwise">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Applications
						</a>
					</li>
					
					<li>
						<a class="<?php if($selected=="apprcsubeci") { echo "active"; } else { echo "parent"; } ?>" href="admin.php?action=showsubtracker">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Rate Confirmations
						</a>
					</li>
					<li>
						<a class="<?php if($selected=="smwisereport") { echo "active"; } else { echo "parent"; } ?>" href="admin.php?action=showsmwisereport">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Submissions
						</a>
					</li>
					<li>
						<a class="<?php if($selected=="ECI") { echo "active"; } else { echo "parent"; } ?>" href="admin.php?action=interviews">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Interviews
						</a>
					</li> 
					<li>
						<a class="<?php if($selected=="PO") { echo "active"; } else { echo "parent"; } ?>" href="admin.php?action=po">
							<svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>Interviews
						</a>
					</li> 


				</ul> -->
</li> 

<?php } ?> 

<!--			<li class="<?php if($selected=="clientslist") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=clientslist"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg>Clients List</a></li> -->
<?php if($dta['level'] == 1)
{ ?>
			<li class="<?php if($selected=="listusers") { echo "active"; } else { echo "parent"; } ?>"><a href="admin.php?action=listusers"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg>List Users</a></li> <?php } ?>

		</ul>

	</div><!--/.sidebar-->
		
	<?php
}
else
{ echo "<script>
alert('Not Authorised to view this page. !!!');
window.location.href='../login.php';
</script>";  } ?>