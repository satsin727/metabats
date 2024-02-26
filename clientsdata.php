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


if($dta['level'] == 1 || $dta['level'] == 2)
{

    $query = "select distinct domain, count(*) as contact from clients group by domain asc";
    $ins= $conn->prepare($query);
    $ins->execute();
    $data = $ins->fetchAll();

?> 
<div class="col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 main">			
                <div class="row">
                    <ol class="breadcrumb">
                        <li><a href="#"><svg class="glyph stroked home"><use xlink:href="#stroked-home"></use></svg></a></li>
                        <li class="active">Dashboard</li>
                    </ol>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <table data-toggle="table"  data-show-refresh="true" data-show-toggle="true" data-show-columns="true" data-search="true" data-select-item-name="toolbar1" data-pagination="true" data-sort-name="uid" data-sort-order="asc">
                                    <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true">S.no</th>
                                        <th data-field="domain"  data-sortable="true">Domain</th>
                                        <th data-field="contacts" data-sortable="true">Contacts</th>
                                    </tr>
                                    </thead>
                                <tbody>
        <?php
        $i=1;
        
        foreach( $data as $row) { 


            ?>
            <tr>
               <td data-order="<?php echo $i; ?>"> <?php echo $i; $i=$i+1;  ?></td>
               <td data-search="<?php echo $row['domain']; ?>"> <?php echo $row['domain']; ?></td>
               <td data-search="<?php echo $row['contact']; ?>"> <?php echo $row['contact']; ?></td>

            </tr>
            <?php
        }
        ?>
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>



	</div>	<!--/.main-->


<?php
}
else
{
	echo "<script>
alert('You Need to be Admin/Manager to view this page.');
window.location.href='admin.php';
</script>"; 
}
$conn = null;
require("includes/footer.php"); 
}
else
{ echo "<script>
alert('Not Authorised to view this page, Not a valid session. Your IP address has been recorded for review. Please Log-in again to view this page !!!');
window.location.href='login.php';
</script>";   }

?>