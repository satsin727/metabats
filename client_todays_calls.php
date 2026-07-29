<?php
session_start();
require_once("config.php");

$conn = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

if (isset($_SESSION['id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE uid = :uid LIMIT 1");
    $stmt->execute([":uid" => $_SESSION['id']]);
    
    $dta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stmt->rowCount() > 0) {
        $date = date("Y-m-d");

        if (isset($_GET['call_date']) && $_GET['call_date'] != "") {
            $date = $_GET['call_date'];
        }

        $where = " WHERE s.call_date = :call_date ";

        $params = [':call_date' => $date];

        if ($dta['level'] == 2 || $dta['level'] == 3) {
            $where .= " AND s.uid = :uid ";
            $params[':uid'] = $_SESSION['id']; // $uid is already set from $_SESSION['id']
        }

        $sql = "
            SELECT
                s.cl_id, s.cid, s.call_date, s.called, s.connected,
                c.companyname, c.rname, c.rphone, c.remail, c.domain,
                u.name AS assignedby
            FROM client_call_schedule s
            INNER JOIN clients c ON s.cid = c.cid
            LEFT JOIN users u ON s.uid = u.uid
            $where
            ORDER BY c.companyname
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $responseStmt = $conn->prepare("
            SELECT id, response_name
            FROM client_response_type
            WHERE status = 1
            ORDER BY response_name
        ");
        $responseStmt->execute();
        $responseTypes = $responseStmt->fetchAll(PDO::FETCH_ASSOC);

        require("includes/header.php");
        require("includes/menu.php");
        
        echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';
        ?>

        <div class="panel panel-default">
            <div class="panel-body">
                <div class="container-fluid">
                    <h3>Today's Client Calls</h3>

                    <form method="get" class="form-inline">
                        <input type="hidden" name="action" value="callinglist">

                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="call_date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                        </div>
                        <button class="btn btn-primary">Search</button>
                    </form>
                    <br>
                    
                    <table id="callTable" data-toggle="table" data-search="true" data-pagination="true" data-page-size="25" data-show-columns="true" data-show-toggle="true" data-show-refresh="true" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th data-sortable="true">S.no</th>
                                <th data-sortable="true">Company</th>
                                <th data-sortable="true">Name</th>
                                <th data-sortable="true">Phone</th>
                                <th data-sortable="true">Email</th>
                                <th data-sortable="true" data-visible="false">Domain</th>
                                <th data-sortable="true">Connected</th>
                                <th data-sortable="true">History</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 

                            $i=0;
                                                        
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { 
                                $i++;
                                
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo htmlspecialchars($row['companyname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['rname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['rphone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['remail']); ?></td>
                                    <td><?php echo htmlspecialchars($row['domain']); ?></td>
                                    <td>
                                        <?php if($row['called'] == 0) { ?>
                                            <button 
                                                type="button" 
                                                class="btn btn-success btn-xs btnYes" 
                                                data-toggle="modal" 
                                                data-target="#callResponseModal" 
                                                data-clid="<?php echo $row['cl_id']; ?>">
                                                Yes
                                            </button>

                                            <button 
                                                type="button" 
                                                class="btn btn-danger btn-xs btnNo" 
                                                data-clid="<?php echo $row['cl_id']; ?>">
                                                No
                                            </button>
                                        <?php } else { ?>
                                            <?php if($row['connected'] == 1) { ?>
                                                <span class="label label-success">Connected</span>
                                            <?php } else { ?>
                                                <span class="label label-danger">Not Connected</span>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td align="center">
                                        <a href="admin.php?action=clienthistory&cid=<?php echo $row['cid']; ?>" class="btn btn-xs btn-info">History</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal -->
                <div class="modal fade" id="callResponseModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title">Client Call Response</h4>
                            </div>

                            <form id="callResponseForm">
                                <div class="modal-body">
                                    <input type="hidden" name="clid" id="clid">        
                                    
                                    <div class="form-group">
                                        <label>Response Type</label>
                                        <select class="form-control" name="response_type" id="response_type" required>
                                            <option value="">-- Select Response Type --</option>
                                            <?php foreach($responseTypes as $type){ ?>
                                                <option value="<?php echo $type['id']; ?>">
                                                    <?php echo htmlspecialchars($type['response_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Comments</label>
                                        <textarea class="form-control" rows="5" name="comments" id="comments"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Next Follow-up Date</label>
                                        <input type="date" class="form-control" name="followup_date" id="followup_date">
                                    </div>
                                </div> <!-- end modal-body -->

                                <div class="modal-footer">
                                    <button type="button" id="btnSaveCall" class="btn btn-primary">Save</button>
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> <!-- End Modal -->

            </div> <!-- end panel-body -->
        </div> <!-- end panel -->
        </div> <!-- Closing div for main content wrapper -->

        <?php
    } else {
        echo "<div class='alert alert-danger'>No client calls assigned for the selected date.</div>";
    }
} else {
    echo "<script>alert('You need to login.'); window.location='index.php';</script>";
}

require("includes/footer.php"); 
?>

<script>
$(document).ready(function() {
    
    // Pass the clid to the hidden input when the modal is opened
    $('#callResponseModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#clid').val(button.data('clid'));
        
        // Reset form fields when opening
        $('#callResponseForm')[0].reset();
    });

    // Handle the AJAX save
    $('#btnSaveCall').click(function(){
        var btn = $(this);
        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'clientcallcmd.php?action=savecall',
            type: 'POST',
            data: $('#callResponseForm').serialize(),
            dataType: 'json',
            success: function(res){
                if(res.status == "success"){
                    alert("Call response saved successfully.");
                    $('#callResponseModal').modal('hide');
                    location.reload();
                } else {
                    alert(res.message);
                    btn.prop('disabled', false).text('Save');
                }
            },
            error: function(){
                alert("Unable to save call response.");
                btn.prop('disabled', false).text('Save');
            }
        });
    });

        // Handle the "No" (Not Connected) button click
    $('.btnNo').click(function() {
        var btn = $(this);
        var clid = btn.data('clid');

        // Optional: Ask for confirmation before updating
        if(confirm("Mark this call as Not Connected?")) {
            btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'clientcallcmd.php?action=notconnected',
                type: 'POST',
                data: { 
                    clid: clid,
                    connected: 0 
                },
                dataType: 'json',
                success: function(res){
                    if(res.status == "success"){
                        // Reload the page to reflect the new status
                        location.reload(); 
                    } else {
                        alert(res.message);
                        btn.prop('disabled', false).text('No');
                    }
                },
                error: function(){
                    alert("Unable to update call status.");
                    btn.prop('disabled', false).text('No');
                }
            });
        }
    });


});
</script>