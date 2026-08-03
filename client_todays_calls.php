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
        $dateObject->format('Y-m-d') ===
            $requestedDate
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
 * Level 2 and Level 3 users can view only their
 * own assigned calls.
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
    $responseStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

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

                foreach (
                    $scheduledCalls as $row
                ) {
                    $i++;

                    $clid =
                        (int)$row['cl_id'];
                ?>

                    <tr
                        id="call-row-<?php
                        echo $clid;
                        ?>"
                        data-clid="<?php
                        echo $clid;
                        ?>">

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
                                id="call-status-<?php
                                echo $clid;
                                ?>"
                                class="call-status-wrapper"
                                data-clid="<?php
                                echo $clid;
                                ?>">

                                <?php
                                if (
                                    (int)$row['called'] === 0
                                ) {
                                ?>

                                    <button
                                        type="button"
                                        class="btn btn-success btn-xs btnCallModal"
                                        data-toggle="modal"
                                        data-target="#callResponseModal"
                                        data-clid="<?php
                                        echo $clid;
                                        ?>"
                                        data-connected="1"
                                        data-mode="initial">

                                        Yes

                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-danger btn-xs btnCallModal"
                                        data-toggle="modal"
                                        data-target="#callResponseModal"
                                        data-clid="<?php
                                        echo $clid;
                                        ?>"
                                        data-connected="0"
                                        data-mode="initial">

                                        No

                                    </button>

                                <?php
                                } elseif (
                                    (int)$row['connected'] === 1
                                ) {
                                ?>

                                    <span class="label label-success">
                                        Connected
                                    </span>

                                <?php } else { ?>

                                    <span class="label label-danger">
                                        Not Connected
                                    </span>

                                    <br><br>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-xs btnCallModal"
                                        data-toggle="modal"
                                        data-target="#callResponseModal"
                                        data-clid="<?php
                                        echo $clid;
                                        ?>"
                                        data-connected="1"
                                        data-mode="callback">

                                        <span class="glyphicon glyphicon-earphone"></span>

                                        Client Called Back

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

                <?php
                if (empty($scheduledCalls)) {
                ?>

                    <tr>

                        <td
                            colspan="9"
                            class="text-center text-muted">

                            No client calls assigned for the
                            selected date.

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

        <!-- Call Response Modal -->

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
                                name="call_mode"
                                id="call_mode"
                                value="initial">

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
                                    foreach (
                                        $responseTypes as $type
                                    ) {
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

                            <div class="form-group">

                                <label for="comments">
                                    Comments
                                </label>

                                <textarea
                                    class="form-control"
                                    rows="5"
                                    name="comments"
                                    id="comments"></textarea>

                            </div>

                            <div class="form-group">

                                <label for="followup_date">
                                    Next Follow-up Date
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    name="followup_date"
                                    id="followup_date"
                                    min="<?php
                                    echo date('Y-m-d');
                                    ?>">

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
         * Call has not yet been completed.
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
                + ' data-mode="initial">'
                + 'No'
                + '</button>';
        }

        /*
         * Connected.
         */

        if (connected === 1) {

            return ''
                + '<span class="label label-success">'
                + 'Connected'
                + '</span>';
        }

        /*
         * Not Connected.
         */

        return ''
            + '<span class="label label-danger">'
            + 'Not Connected'
            + '</span>'
            + '<br><br>'
            + '<button'
            + ' type="button"'
            + ' class="btn btn-primary btn-xs btnCallModal"'
            + ' data-toggle="modal"'
            + ' data-target="#callResponseModal"'
            + ' data-clid="' + clid + '"'
            + ' data-connected="1"'
            + ' data-mode="callback">'
            + '<span class="glyphicon glyphicon-earphone"></span> '
            + 'Client Called Back'
            + '</button>';
    }

    /*
    |--------------------------------------------------------------------------
    | Update Only the Visible Status Cell
    |--------------------------------------------------------------------------
    */

    function updateVisibleCallRow(
        callData
    ) {
        var clid = parseInt(
            callData.cl_id,
            10
        );

        /*
         * Every status cell has a permanent unique wrapper ID.
         */

        var statusWrapper = $(
            '#call-status-' + clid
        );

        /*
         * Fallback search using data-clid.
         */

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

        /*
         * Briefly highlight the updated row.
         */

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
    | Reload One Call Record from Database
    |--------------------------------------------------------------------------
    */

    function reloadCallRow(clid) {

        return $.ajax({

            url:
                'clientcallcmd.php?action=getcallrow',

            type: 'GET',

            data: {
                clid: clid,

                /*
                 * Prevent browser caching.
                 */

                _: new Date().getTime()
            },

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
                        : 'Unable to refresh the saved call.'
                );

                return;
            }

            if (!updateVisibleCallRow(res)) {

                alert(
                    'The call was saved, but the visible table row could not be found.'
                );
            }

        }).fail(function (xhr) {

            console.log(
                xhr.responseText
            );

            alert(
                'The call was saved, but the table row could not be refreshed.'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Open Call Modal
    |--------------------------------------------------------------------------
    */

    $('#callResponseModal').on(
        'show.bs.modal',
        function (event) {

            var button =
                $(event.relatedTarget);

            /*
             * Use attr instead of jQuery data caching so dynamically
             * inserted callback buttons always return current values.
             */

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

            var callMode =
                button.attr('data-mode') ||
                'initial';

            /*
             * Reset modal.
             */

            $('#callResponseForm')[0]
                .reset();

            $('#clid').val(clid);

            $('#connected').val(
                connectedStatus
            );

            $('#call_mode').val(
                callMode
            );

            $('#btnSaveCall').prop(
                'disabled',
                false
            );

            /*
             * Client Called Back.
             */

            if (callMode === 'callback') {

                $('#connected').val(1);

                $('#responseTypeGroup')
                    .show();

                $('#response_type').prop(
                    'required',
                    true
                );

                $('#callResponseModalTitle')
                    .text(
                        'Client Called Back'
                    );

                $('#comments').attr(
                    'placeholder',
                    'Add details about the client callback...'
                );

                $('#btnSaveCall').text(
                    'Save Callback'
                );

                return;
            }

            /*
             * Initial Not Connected call.
             */

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

                $('#btnSaveCall').text(
                    'Save'
                );

                return;
            }

            /*
             * Initial Connected call.
             */

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

            $('#btnSaveCall').text(
                'Save'
            );
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Save Call or Callback
    |--------------------------------------------------------------------------
    */

    $('#btnSaveCall').on(
        'click',
        function () {

            var button = $(this);

            var callMode =
                $('#call_mode').val();

            var connectedStatus =
                parseInt(
                    $('#connected').val(),
                    10
                );

            var responseType =
                $('#response_type').val();

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

            /*
             * Response type is required for connected calls
             * and client callbacks.
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

            var actionUrl =
                callMode === 'callback'
                    ? 'clientcallcmd.php?action=savecallback'
                    : 'clientcallcmd.php?action=savecall';

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
                            : 'Unable to save the call response.'
                    );

                    button
                        .prop(
                            'disabled',
                            false
                        )
                        .text(
                            callMode ===
                            'callback'
                                ? 'Save Callback'
                                : 'Save'
                        );

                    return;
                }

                var savedClid =
                    res.clid
                        ? parseInt(
                            res.clid,
                            10
                        )
                        : clid;

                /*
                 * Close modal.
                 */

                $('#callResponseModal')
                    .modal('hide');

                /*
                 * Retrieve the latest status from the database
                 * and update only this row.
                 */

                reloadCallRow(
                    savedClid
                );

            }).fail(function (xhr) {

                console.log(
                    xhr.responseText
                );

                alert(
                    'Unable to save the call response.'
                );

                button
                    .prop(
                        'disabled',
                        false
                    )
                    .text(
                        callMode ===
                        'callback'
                            ? 'Save Callback'
                            : 'Save'
                    );
            });
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Reset Modal After Closing
    |--------------------------------------------------------------------------
    */

    $('#callResponseModal').on(
        'hidden.bs.modal',
        function () {

            $('#callResponseForm')[0]
                .reset();

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
        }
    );
});
</script>

<?php
require("includes/footer.php");

$conn = null;
?>