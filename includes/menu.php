<?php
$conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
$query = "SELECT * FROM users WHERE `sess` = :sess";
$ins = $conn->prepare($query);
$ins->bindValue(":sess", $_SESSION['username'], PDO::PARAM_STR);
$ins->execute();
$dta = $ins->fetch();

$selected = isset($_GET['action']) ? $_GET['action'] : '';
?>

<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="./"><span>Metahorizon</span> BATS</a>
                <ul class="user-menu">
                    <li class="dropdown pull-right">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg> User <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg> Profile</a></li>
                            <li><a href="#"><svg class="glyph stroked gear"><use xlink:href="#stroked-gear"></use></svg> Settings</a></li>
                            <li><a href="admin.php?action=logout"><svg class="glyph stroked cancel"><use xlink:href="#stroked-cancel"></use></svg> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if (isset($_SESSION['username'])) { ?>
    
    <!-- Sidebar Navigation -->
    <div id="sidebar-collapse" class="col-sm-3 col-lg-2 sidebar">
        <button id="close-sidebar" class="btn btn-danger btn-sm" style="position: absolute; top: 10px; right: 10px; z-index: 9999;">
            × Close
        </button>
        <br><br>

        <ul class="nav menu">
            
            <!-- Dashboard / Post Req -->
            <li class="<?php echo ($selected == "postreq") ? "active" : "parent"; ?>">
                <a href="admin.php?action=postreq">
                    <svg class="glyph stroked dashboard-dial"><use xlink:href="#stroked-dashboard-dial"></use></svg> Post Req
                </a>
            </li>
            
            <li class="<?php echo ($selected == "clientassignment") ? "active" : "parent"; ?>">
                <a href="admin.php?action=clientassignment"><svg class="glyph stroked calendar"><use xlink:href="#stroked-calendar"></use></svg> Client Call Schedule</a>
            </li>

            <!-- ========================================== -->
            <!-- CORE WORKFLOW (Quick Access, Zero Extra Clicks) -->
            <!-- ========================================== -->
            <li class="parent" style="background-color: rgba(0,0,0,0.03); border-left: 4px solid #30a5ff;">
                <a href="#" style="font-weight: bold; color: #30a5ff; pointer-events: none;">
                    <svg class="glyph stroked app-window"><use xlink:href="#stroked-app-window"></use></svg> Process
                </a>
            </li>

            <!-- Calling List (Levels 2 & 3) -->
            <?php if ($dta['level'] == 2 || $dta['level'] == 3) { ?>
            <li class="<?php echo ($selected == "callinglist") ? "active" : "parent"; ?>" style="padding-left: 10px;">
                <a href="admin.php?action=callinglist">
                    <svg class="glyph stroked clipboard-with-paper"><use xlink:href="#stroked-clipboard-with-paper"></use></svg> Calling List
                </a>
            </li>
            <?php } ?>

            <!-- My Reqs (Levels 2 & 3) -->
            <?php if ($dta['level'] == 2 || $dta['level'] == 3) { ?>
            <li class="<?php echo ($selected == "showreqs") ? "active" : "parent"; ?>" style="padding-left: 10px;">
                <a href="admin.php?action=showreqs">
                    <svg class="glyph stroked clipboard-with-paper"><use xlink:href="#stroked-clipboard-with-paper"></use></svg> My Reqs
                </a>
            </li>
            <?php } ?>

            <!-- Applications -->
            <li class="<?php echo ($selected == "showapplications") ? "active" : "parent"; ?>" style="padding-left: 10px;">
                <a href="admin.php?action=showapplications">
                    <svg class="glyph stroked app-window"><use xlink:href="#stroked-app-window"></use></svg> Applications
                </a>
            </li>

            <!-- Rate Confirmations -->
            <li class="<?php echo ($selected == "showrc") ? "active" : "parent"; ?>" style="padding-left: 10px;">
                <a href="admin.php?action=showrc">
                    <svg class="glyph stroked checkmark"><use xlink:href="#stroked-checkmark"></use></svg> Rate Confirmations
                </a>
            </li>

            <!-- Submissions -->
            <li class="<?php echo ($selected == "showsub") ? "active" : "parent"; ?>" style="padding-left: 10px;">
                <a href="admin.php?action=showsub">
                    <svg class="glyph stroked upload"><use xlink:href="#stroked-upload"></use></svg> Submissions
                </a>
            </li>

            <!-- Interviews -->
            <li class="<?php echo ($selected == "showeci") ? "active" : "parent"; ?>" style="padding-left: 10px;">
                <a href="admin.php?action=showeci">
                    <svg class="glyph stroked calendar"><use xlink:href="#stroked-calendar"></use></svg> Interviews
                </a>
            </li>

            <!-- ========================================== -->
            <!-- REPORTS & MANAGEMENT (Single Dropdown)      -->
            <!-- ========================================== -->
            <?php 
            $reportActions = ['showteamreqs', 'showallreqs', 'showreports', 'callreports', 'showdailydata', 'showsmdata', 'clientslist'];
            $isReportActive = in_array($selected, $reportActions);
            ?>
            <li class="parent <?php echo $isReportActive ? 'active' : ''; ?>">
                <a data-toggle="collapse" href="#sub-reports-mgmt">
                    <svg class="glyph stroked chevron-down"><use xlink:href="#stroked-chevron-down"></use></svg> Reports & Management
                </a>
                <ul class="children collapse <?php echo $isReportActive ? 'active in' : ''; ?>" id="sub-reports-mgmt">
                    
                    <!-- Team Reqs (Level 2) -->
                    <?php if ($dta['level'] == 2) { ?>
                    <li>
                        <a class="<?php echo ($selected == "showteamreqs") ? "active" : ""; ?>" href="admin.php?action=showteamreqs">
                            <svg class="glyph stroked clipboard-with-paper"><use xlink:href="#stroked-clipboard-with-paper"></use></svg> Team Reqs
                        </a>
                    </li>
                    <?php } ?>

                    <!-- All Reqs -->
                    <li>
                        <a class="<?php echo ($selected == "showallreqs") ? "active" : ""; ?>" href="admin.php?action=showallreqs">
                            <svg class="glyph stroked dashboard-dial"><use xlink:href="#stroked-dashboard-dial"></use></svg> All Reqs
                        </a>
                    </li>

                    <!-- All Clients -->
                    <?php if ($dta['level'] == 1 || $dta['level'] == 2 || $dta['level'] == 3) { ?>
                    <li>
                        <a class="<?php echo ($selected == "clientslist") ? "active" : ""; ?>" href="admin.php?action=clientslist">
                            <svg class="glyph stroked bag"><use xlink:href="#stroked-bag"></use></svg> All Clients
                        </a>
                    </li>
                    <?php } ?>

                    <!-- Reports (Conditional based on user level) -->
                    <?php if ($dta['level'] == 1 || $dta['level'] == 2) { ?>
                    <li>
                        <a class="<?php echo ($selected == "showreports") ? "active" : ""; ?>" href="admin.php?action=showreports">
                            <svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg> All Reports
                        </a>
                    </li>
                    <li>
                        <a class="<?php echo ($selected == "callreports") ? "active" : ""; ?>" href="admin.php?action=callreports">
                            <svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg> Call Reports
                        </a>
                    </li>
                    <?php } else { ?>
                    <li>
                        <a class="<?php echo ($selected == "showreports") ? "active" : ""; ?>" href="admin.php?action=showdailydata">
                            <svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg> Daily Reports
                        </a>
                    </li>
                    <li>
                        <a class="<?php echo ($selected == "showsmdata") ? "active" : ""; ?>" href="admin.php?action=showsmdata">
                            <svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg> SM Snapshot
                        </a>
                    </li>                    
                    <li>
                        <a class="<?php echo ($selected == "smcallreports") ? "active" : ""; ?>" href="admin.php?action=smcallreports">
                            <svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg>SM Call Reports
                        </a>
                    </li>
                    <?php } ?>

                </ul>
            </li>

            <!-- ========================================== -->
            <!-- OTHER SECTIONS                            -->
            <!-- ========================================== -->

            <!-- Updated Hotlist -->
            <li class="<?php echo ($selected == "updatedhotlist") ? "active" : "parent"; ?>">
                <a href="admin.php?action=updatedhotlist"><svg class="glyph stroked star"><use xlink:href="#stroked-star"></use></svg> Updated Hotlist</a>
            </li>

            <!-- My Consultants (Level 3) -->
            <?php if ($dta['level'] == 3) { ?>
            <li class="<?php echo ($selected == "assigned") ? "active" : "parent"; ?>">
                <a href="admin.php?action=assigned"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg> My Consultants</a>
            </li>
            <?php } ?>

            <!-- All Consultants (Levels 1 & 2) -->
            <?php if ($dta['level'] == 1 || $dta['level'] == 2) { ?>
            <li class="<?php echo ($selected == "listconsultants") ? "active" : "parent"; ?>">
                <a href="admin.php?action=listconsultants"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg> All Consultants</a>
            </li>
            <?php } ?>

            <!-- Notice Board -->
            <li class="<?php echo ($selected == "showissues") ? "active" : "parent"; ?>">
                <a href="admin.php?action=listissues&status=1"><svg class="glyph stroked sound"><use xlink:href="#stroked-sound"></use></svg> Notice Board</a>
            </li>

            <!-- List Users (Level 1 Admin Only) -->
            <?php if ($dta['level'] == 1) { ?>
            <li class="<?php echo ($selected == "listusers") ? "active" : "parent"; ?>">
                <a href="admin.php?action=listusers"><svg class="glyph stroked male-user"><use xlink:href="#stroked-male-user"></use></svg> List Users</a>
            </li>
            <?php } ?>

        </ul>
    </div><!-- /.sidebar -->
    
    <?php 
    } else { 
        echo "<script>
        alert('Not Authorised to view this page. !!!');
        window.location.href='../login.php';
        </script>"; 
    } 
    ?>
