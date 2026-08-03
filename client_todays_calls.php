<?php
require_once("config.php");

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

try {
    $conn = new PDO(
        DB_DSN,
        DB_USERNAME,
        DB_PASSWORD
    );

    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Database connection failed.");
}

/*
|--------------------------------------------------------------------------
| Login Validation
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['id']) ||
    (int)$_SESSION['id'] <= 0
) {
    echo "<script>
        alert('You need to login.');
        window.location='index.php';
    </script>";

    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM users
    WHERE uid = :uid
    LIMIT 1
");

$stmt->execute(array(
    ":uid" => (int)$_SESSION['id']
));

$dta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dta) {
    echo "<script>
        alert('Invalid user session.');
        window.location='index.php';
    </script>";

    exit;
}

/*
|--------------------------------------------------------------------------
| Selected Call Date
|--------------------------------------------------------------------------
*/

$date = date("Y-m-d");

if (!empty($_GET['call_date'])) {

    $requestedDate = trim(
        $_GET['call_date']
    );

    $dateObject = DateTime::createFromFormat(
        'Y-m-d',
        $requestedDate
    );

    if (
        $dateObject &&
        $dateObject->format('Y-m-d') === $requestedDate
    ) {
        $date = $requestedDate;
    }
}

/*
|--------------------------------------------------------------------------
| Call List Query
|--------------------------------------------------------------------------
*/

$where = "
    WHERE s.call_date = :call_date
";

$params = array(
    ':call_date' => $date
);

/*
 * Level 2 and Level 3 users view only their own calls.
 */

if (
    (int)$dta['level'] === 2 ||
    (int)$dta['level'] === 3
) {
    $where .= "
        AND s.uid = :uid
    ";

    $params[':uid'] =
        (int)$_SESSION['id'];
}

$sql = "
    SELECT
        s.cl_id,
        s.cid,
        s.call_date,
        s.called,
        s.connected,
        s.latest_comment,

        c.lid,
        c.companyname,
        c.rname,
        c.rphone,
        c.remail,
        c.domain,

        u.name AS assignedby

    FROM client_call_schedule s

    INNER JOIN clients c
        ON s.cid = c.cid

    LEFT JOIN users u
        ON s.uid = u.uid

    $where

    ORDER BY
        c.companyname ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$scheduledCalls =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Response Types
|--------------------------------------------------------------------------
*/

$responseStmt = $conn->prepare("
    SELECT
        id,
        response_name
    FROM client_response_type
    WHERE status = 1
    ORDER BY response_name ASC
");

$responseStmt->execute();

$responseTypes =
    $responseStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Page Header
|--------------------------------------------------------------------------
*/

require("includes/header.php");
require("includes/menu.php");

echo '<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">';
?>

<div class="panel panel-default">

    <div class="panel-body">

        <div class="container-fluid">

            <h3>Today's Client Calls</h3>

            <!-- AJAX success/error message -->

            <div
                id="callActionMessage"
                style="display:none;">
            </div>

            <form
                method="get"
                class="form-inline">

                <input
                    type="hidden"
                    name="action"
                    value="callinglist">

                <div class="form-group">

                    <label for="call_date_filter">
                        Date
                    </label>

                    <input
                        type="date"
                        id="call_date_filter"
                        name="call_date"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars(
                            $date,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>">

                </div>

                <button
                    type="submit"
                    class="btn btn-primary">

                    Search

                </button>

            </form>

            <br>

            <table
                id="callTable"
                data-toggle="table"
                data-search="true"
                data-pagination="true"
                data-page-size="25"
                data-show-columns="true"
                data-show-toggle="true"
                data-show-refresh="true"
                class="table table-bordered table-hover">

                <thead>

                    <tr>

                        <th data-sortable="true">
                            S.no
                        </th>

                        <th data-sortable="true">
                            Company
                        </th>

                        <th data-sortable="true">
                            Name
                        </th>

                        <th data-sortable="true">
                            Phone
                        </th>

                        <th data-sortable="true">
                            Email
                        </th>

                        <th
                            data-sortable="true"
                            data-visible="false">

                            Domain

                        </th>

                        <th data-sortable="true">
                            Connected
                        </th>

                        <th data-sortable="true">
                            History
                        </th>

                        <th data-field="editaction">
                            Edit
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php
                $i = 0;

                foreach ($scheduledCalls as $row) {

                    $i++;

                    $clid = (int)$row['cl_id'];
                ?>

                    <tr
                        id="call-row-<?php echo $clid; ?>"
                        data-clid="<?php echo $clid; ?>">

                        <td>
                            <?php echo $i; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['companyname'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['rname'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['rphone'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['remail'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $row['domain'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </td>

                        <!-- Call Status -->

                        <td>

                            <div
                                id="call-status-<?php echo $clid; ?>"
                                class="call-status-wrapper"
                                data-clid="<?php echo $clid; ?>">

                                <?php
                                if ((int)$row['called'] === 0) {
                                ?>

                                    <button
                                        type="button"
                                        class="btn btn-success btn-xs btnCallModal"
                                        data-toggle="modal"
                                        data-target="#callResponseModal"
                                        data-clid="<?php echo $clid; ?>"
                                        data-connected="1"
                                        data-current-connected="0"
                                        data-mode="initial">

                                        Yes

                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-danger btn-xs btnCallModal"
                                        data-toggle="modal"
                                        data-target="#callResponseModal"
                                        data-clid="<?php echo $clid; ?>"
                                        data-connected="0"
                                        data-current-connected="0"
                                        data-mode="initial">

                                        No

                                    </button>

                                <?php
                                } else {
                                ?>

                                    <?php
                                    if ((int)$row['connected'] === 1) {
                                    ?>

                                        <span class="label label-success">
                                            Connected
                                        </span>

                                    <?php } else { ?>

                                        <span class="label label-danger">
                                            Not Connected
                                        </span>

                                    <?php } ?>

                                &nbsp;&nbsp;

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-xs btnCallModal"
                                        data-toggle="modal"
                                        data-target="#callResponseModal"
                                        data-clid="<?php echo $clid; ?>"
                                        data-connected="<?php
                                        echo (int)$row['connected'];
                                        ?>"
                                        data-current-connected="<?php
                                        echo (int)$row['connected'];
                                        ?>"
                                        data-mode="activity">

                                        <span class="glyphicon glyphicon-edit"></span>

                                        Update / Add Comment
                                    </button>

                                <?php } ?>

                            </div>

                        </td>

                        <!-- History -->

                        <td align="center">

                            <a
                                href="admin.php?action=clienthistory&amp;cid=<?php
                                echo (int)$row['cid'];
                                ?>"
                                class="btn btn-xs btn-info">

                                History

                            </a>

                        </td>

                        <!-- Edit -->

                        <td>

                            <a
                                href="listcmd.php?do=editcontact&amp;lid=<?php
                                echo (int)$row['lid'];
                                ?>&amp;id=<?php
                                echo (int)$row['cid'];
                                ?>"
                                class="btn btn-xs btn-info">

                                <span class="glyphicon glyphicon-pencil"></span>

                                Edit

                            </a>

                        </td>

                    </tr>

                <?php } ?>

                <?php if (empty($scheduledCalls)) { ?>

                    <tr>

                        <td
                            colspan="9"
                            class="text-center text-muted">

                            No client calls assigned for the selected date.

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

        <!-- Call Response / Update Modal -->

        <div
            class="modal fade"
            id="callResponseModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="callResponseModalTitle">

            <div
                class="modal-dialog"
                role="document">

                <div class="modal-content">

                    <div class="modal-header">

                        <button
                            type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Close">

                            <span aria-hidden="true">
                                &times;
                            </span>

                        </button>

                        <h4
                            class="modal-title"
                            id="callResponseModalTitle">

                            Client Call Response

                        </h4>

                    </div>

                    <form id="callResponseForm">

                        <div class="modal-body">

                            <input
                                type="hidden"
                                name="clid"
                                id="clid">

                            <input
                                type="hidden"
                                name="connected"
                                id="connected"
                                value="1">

                            <input
                                type="hidden"
                                name="current_connected"
                                id="current_connected"
                                value="0">

                            <input
                                type="hidden"
                                name="call_mode"
                                id="call_mode"
                                value="initial">

                            <!-- Activity Type -->

                            <div
                                class="form-group"
                                id="activityTypeGroup"
                                style="display:none;">

                                <label for="activity_type">
                                    Update Type
                                </label>

                                <select
                                    class="form-control"
                                    name="activity_type"
                                    id="activity_type">

                                    <option value="">
                                        -- Select Update Type --
                                    </option>

                                    <option value="comment">
                                        Add Comment
                                    </option>

                                    <option value="callback">
                                        Client Called Back
                                    </option>

                                </select>

                            </div>

                            <!-- Response Type -->

                            <div
                                class="form-group"
                                id="responseTypeGroup">

                                <label for="response_type">
                                    Response Type
                                </label>

                                <select
                                    class="form-control"
                                    name="response_type"
                                    id="response_type">

                                    <option value="">
                                        -- Select Response Type --
                                    </option>

                                    <?php
                                    foreach ($responseTypes as $type) {
                                    ?>

                                        <option
                                            value="<?php
                                            echo (int)$type['id'];
                                            ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                $type['response_name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>

                                        </option>

                                    <?php } ?>

                                </select>

                            </div>

                            <!-- Comments -->

                            <div class="form-group">

                                <label for="comments">
                                    Comments
                                </label>

                                <textarea
                                    class="form-control"
                                    rows="5"
                                    name="comments"
                                    id="comments"></textarea>

                                <small
                                    id="commentHelp"
                                    class="text-muted">

                                    Add details about the call.

                                </small>

                            </div>

                            <!-- Follow-up Date -->

                            <div
                                class="form-group"
                                id="followupDateGroup">

                                <label for="followup_date">
                                    Next Follow-up Date
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    name="followup_date"
                                    id="followup_date"
                                    min="<?php echo date('Y-m-d'); ?>">

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                id="btnSaveCall"
                                class="btn btn-primary">

                                Save

                            </button>

                            <button
                                type="button"
                                class="btn btn-default"
                                data-dismiss="modal">

                                Close

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

<script>
$(document).ready(function () {

    'use strict';

    /*
    |--------------------------------------------------------------------------
    | Display Page Message
    |--------------------------------------------------------------------------
    */

    function showPageMessage(
        message,
        messageType
    ) {
        var alertClass =
            messageType === 'error'
                ? 'alert-danger'
                : 'alert-success';

        $('#callActionMessage')
            .removeClass(
                'alert-success alert-danger'
            )
            .addClass(
                'alert ' + alertClass
            )
            .html(
                $('<div>').text(message).html()
            )
            .stop(true, true)
            .show();

        window.setTimeout(
            function () {
                $('#callActionMessage')
                    .fadeOut();
            },
            4000
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build Status HTML
    |--------------------------------------------------------------------------
    */

    function buildCallStatusHtml(
        clid,
        called,
        connected
    ) {
        clid = parseInt(clid, 10);
        called = parseInt(called, 10);
        connected = parseInt(
            connected,
            10
        );

        /*
         * Call has not been completed.
         */

        if (called === 0) {

            return ''
                + '<button'
                + ' type="button"'
                + ' class="btn btn-success btn-xs btnCallModal"'
                + ' data-toggle="modal"'
                + ' data-target="#callResponseModal"'
                + ' data-clid="' + clid + '"'
                + ' data-connected="1"'
                + ' data-current-connected="0"'
                + ' data-mode="initial">'
                + 'Yes'
                + '</button> '
                + '<button'
                + ' type="button"'
                + ' class="btn btn-danger btn-xs btnCallModal"'
                + ' data-toggle="modal"'
                + ' data-target="#callResponseModal"'
                + ' data-clid="' + clid + '"'
                + ' data-connected="0"'
                + ' data-current-connected="0"'
                + ' data-mode="initial">'
                + 'No'
                + '</button>';
        }

        var statusHtml = '';

        if (connected === 1) {

            statusHtml +=
                '<span class="label label-success">' +
                'Connected' +
                '</span>';

        } else {

            statusHtml +=
                '<span class="label label-danger">' +
                'Not Connected' +
                '</span>';
        }

        statusHtml +=
            '<br><br>' +
            '<button' +
            ' type="button"' +
            ' class="btn btn-primary btn-xs btnCallModal"' +
            ' data-toggle="modal"' +
            ' data-target="#callResponseModal"' +
            ' data-clid="' + clid + '"' +
            ' data-connected="' + connected + '"' +
            ' data-current-connected="' + connected + '"' +
            ' data-mode="activity">' +
            '<span class="glyphicon glyphicon-edit"></span> ' +
            'Update / Add Comment' +
            '</button>';

        return statusHtml;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Visible Row
    |--------------------------------------------------------------------------
    */

    function updateVisibleCallRow(
        callData
    ) {
        var clid = parseInt(
            callData.cl_id,
            10
        );

        var statusWrapper = $(
            '#call-status-' + clid
        );

        if (!statusWrapper.length) {

            statusWrapper = $(
                '.call-status-wrapper[data-clid="' +
                clid +
                '"]'
            ).first();
        }

        if (!statusWrapper.length) {
            return false;
        }

        statusWrapper.html(
            buildCallStatusHtml(
                clid,
                callData.called,
                callData.connected
            )
        );

        var tableRow =
            statusWrapper.closest('tr');

        tableRow.addClass('success');

        window.setTimeout(
            function () {
                tableRow.removeClass(
                    'success'
                );
            },
            1500
        );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Reload One Row from Database
    |--------------------------------------------------------------------------
    */

    function reloadCallRow(clid) {

        return $.ajax({

            url:
                'clientcallcmd.php?action=getcallrow',

            type: 'GET',

            data: {
                clid: clid,
                _: new Date().getTime()
            },

            dataType: 'json',

            cache: false

        }).done(function (res) {

            if (
                !res ||
                res.status !== 'success'
            ) {
                showPageMessage(
                    res && res.message
                        ? res.message
                        : 'Unable to refresh the saved call.',
                    'error'
                );

                return;
            }

            if (!updateVisibleCallRow(res)) {

                showPageMessage(
                    'The record was saved, but the visible row could not be found.',
                    'error'
                );
            }

        }).fail(function (xhr) {

            console.log(
                xhr.responseText
            );

            showPageMessage(
                'The record was saved, but the table row could not be refreshed.',
                'error'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Configure Activity Fields
    |--------------------------------------------------------------------------
    */

    function configureActivityFields(
        activityType
    ) {
        $('#response_type')
            .prop('required', false)
            .val('');

        if (activityType === 'callback') {

            $('#responseTypeGroup').show();

            $('#response_type').prop(
                'required',
                true
            );

            $('#callResponseModalTitle')
                .text('Client Called Back');

            $('#comments').attr(
                'placeholder',
                'Add details about the client callback...'
            );

            $('#commentHelp').text(
                'The callback will be added as a new Connected history entry.'
            );

            $('#btnSaveCall').text(
                'Save Callback'
            );

            return;
        }

        if (activityType === 'comment') {

            $('#responseTypeGroup').hide();

            $('#callResponseModalTitle')
                .text('Add Client Comment');

            $('#comments').attr(
                'placeholder',
                'Example: Email bounced, left voicemail, wrong number...'
            );

            $('#commentHelp').text(
                'This note will not change the current call status.'
            );

            $('#btnSaveCall').text(
                'Save Comment'
            );

            return;
        }

        $('#responseTypeGroup').hide();

        $('#callResponseModalTitle')
            .text('Update / Add Comment');

        $('#comments').attr(
            'placeholder',
            'Select an update type first.'
        );

        $('#commentHelp').text(
            'Select Add Comment or Client Called Back.'
        );

        $('#btnSaveCall').text(
            'Save Update'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Type Change
    |--------------------------------------------------------------------------
    */

    $('#activity_type').on(
        'change',
        function () {

            configureActivityFields(
                $(this).val()
            );
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Open Modal
    |--------------------------------------------------------------------------
    */

    $('#callResponseModal').on(
        'show.bs.modal',
        function (event) {

            var button =
                $(event.relatedTarget);

            var clid = parseInt(
                button.attr('data-clid'),
                10
            );

            var connectedStatus =
                parseInt(
                    button.attr(
                        'data-connected'
                    ),
                    10
                );

            var currentConnected =
                parseInt(
                    button.attr(
                        'data-current-connected'
                    ),
                    10
                );

            var callMode =
                button.attr('data-mode') ||
                'initial';

            $('#callResponseForm')[0]
                .reset();

            $('#clid').val(clid);

            $('#connected').val(
                connectedStatus
            );

            $('#current_connected').val(
                currentConnected
            );

            $('#call_mode').val(
                callMode
            );

            $('#btnSaveCall').prop(
                'disabled',
                false
            );

            /*
             * Existing completed call:
             * Add comment or callback.
             */

            if (callMode === 'activity') {

                $('#activityTypeGroup').show();

                $('#activity_type').val('');

                configureActivityFields('');

                return;
            }

            /*
             * Initial Yes/No response.
             */

            $('#activityTypeGroup').hide();

            if (connectedStatus === 0) {

                $('#responseTypeGroup')
                    .hide();

                $('#response_type')
                    .prop(
                        'required',
                        false
                    )
                    .val('');

                $('#callResponseModalTitle')
                    .text('Not Connected');

                $('#comments').attr(
                    'placeholder',
                    'Add comments about the call attempt...'
                );

                $('#commentHelp').text(
                    'The call will be saved as Not Connected.'
                );

                $('#btnSaveCall').text(
                    'Save'
                );

                return;
            }

            $('#responseTypeGroup').show();

            $('#response_type').prop(
                'required',
                true
            );

            $('#callResponseModalTitle')
                .text(
                    'Client Call Response'
                );

            $('#comments').attr(
                'placeholder',
                'Add comments about the conversation...'
            );

            $('#commentHelp').text(
                'Select the client response and add call details.'
            );

            $('#btnSaveCall').text(
                'Save'
            );
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Save Call, Comment, or Callback
    |--------------------------------------------------------------------------
    */

    $('#btnSaveCall').on(
        'click',
        function () {

            var button = $(this);

            var callMode =
                $('#call_mode').val();

            var activityType =
                $('#activity_type').val();

            var connectedStatus =
                parseInt(
                    $('#connected').val(),
                    10
                );

            var responseType =
                $('#response_type').val();

            var comments =
                $.trim(
                    $('#comments').val()
                );

            var clid =
                parseInt(
                    $('#clid').val(),
                    10
                );

            if (!clid || clid <= 0) {

                alert(
                    'Invalid call record.'
                );

                return;
            }

            var actionUrl = '';

            /*
             * Existing completed call activity.
             */

            if (callMode === 'activity') {

                if (activityType === '') {

                    alert(
                        'Please select an update type.'
                    );

                    return;
                }

                if (
                    activityType === 'comment' &&
                    comments === ''
                ) {

                    alert(
                        'Please enter a comment.'
                    );

                    return;
                }

                if (
                    activityType === 'callback' &&
                    responseType === ''
                ) {

                    alert(
                        'Please select a response type.'
                    );

                    return;
                }

                actionUrl =
                    activityType === 'callback'
                        ? 'clientcallcmd.php?action=savecallback'
                        : 'clientcallcmd.php?action=savecomment';

            } else {

                /*
                 * Initial Yes/No call response.
                 */

                if (
                    connectedStatus === 1 &&
                    responseType === ''
                ) {

                    alert(
                        'Please select a response type.'
                    );

                    return;
                }

                actionUrl =
                    'clientcallcmd.php?action=savecall';
            }

            button
                .prop('disabled', true)
                .text('Saving...');

            $.ajax({

                url: actionUrl,

                type: 'POST',

                data:
                    $('#callResponseForm')
                        .serialize(),

                dataType: 'json',

                cache: false

            }).done(function (res) {

                if (
                    !res ||
                    res.status !== 'success'
                ) {

                    alert(
                        res && res.message
                            ? res.message
                            : 'Unable to save the update.'
                    );

                    button
                        .prop(
                            'disabled',
                            false
                        )
                        .text('Save');

                    return;
                }

                var savedClid =
                    res.clid
                        ? parseInt(
                            res.clid,
                            10
                        )
                        : clid;

                $('#callResponseModal')
                    .modal('hide');

                reloadCallRow(
                    savedClid
                );

                showPageMessage(
                    res.message
                        ? res.message
                        : 'Update saved successfully.',
                    'success'
                );

            }).fail(function (xhr) {

                console.log(
                    xhr.responseText
                );

                alert(
                    'Unable to save the update.'
                );

                button
                    .prop(
                        'disabled',
                        false
                    )
                    .text('Save');
            });
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Reset Modal
    |--------------------------------------------------------------------------
    */

    $('#callResponseModal').on(
        'hidden.bs.modal',
        function () {

            $('#callResponseForm')[0]
                .reset();

            $('#activityTypeGroup')
                .hide();

            $('#responseTypeGroup')
                .show();

            $('#response_type').prop(
                'required',
                false
            );

            $('#btnSaveCall')
                .prop(
                    'disabled',
                    false
                )
                .text('Save');

            $('#callResponseModalTitle')
                .text(
                    'Client Call Response'
                );

            $('#comments').removeAttr(
                'placeholder'
            );

            $('#commentHelp').text(
                'Add details about the call.'
            );
        }
    );
});
</script>

<?php
require("includes/footer.php");

$conn = null;
?>