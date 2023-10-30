<?php

ob_start();
session_start();
error_reporting(1);
ini_set( 'display_errors', 1 );
ini_set('error_reporting', 1 );
date_default_timezone_set("America/Chicago");

define( "DB_DSN", "mysql:host=localhost;dbname=oejwxwmy_bats" );
define( "DB_USERNAME", "oejwxwmy_bats" );
define( "DB_PASSWORD", "m3+@h0riz0n!" );

if(isset($_POST['submit']))
			{ 

			$lname=$_FILES['file']['name'];
			$lext = strtolower(substr(strrchr($lname, '.'), 1));
			if($lext == "csv" || $rext == "txt")
			{
				$digits = 6;
				$unumber = rand(pow(10, $digits-1), pow(10, $digits)-1);
				$date = date("Y-m-d H:i:s");
				$lfname = basename($_FILES['file']['name'],$lext);
				$file= $listid."-".date("m-d-Y", strtotime($date) )."-".$unumber."-".$lfname.$lext;
				$target = "files/lists/".$file;										
				move_uploaded_file($_FILES["file"]["tmp_name"], $target);
			}
			ini_set('auto_detect_line_endings',TRUE);
			$handle = fopen($target,'r');
while ( ($data = fgetcsv($handle) ) !== FALSE ) {
			
			$col1 = $data[0];
            $col2 = $data[1];
			$col3 = $data[2];
            $col4 = $data[3];
			$col5 = $data[4];
            $col6 = $data[5];
            $col7 = $data[6];
			$col8 = $data[7];
			$col9 = $data[8];
			$col10 = $data[9];
			$col11= $data[10];
			$col12 = $data[11];
			$col13 = $data[12];
			$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
			
			$sql = "INSERT into client (`cid`, `lid`, `uid`, `companyname`, `rname`, `rfname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier`, `status`, `filetarget`) values ('$col1','$col2','$col3','$col4','$col5','$col6','$col7','$col8','$col9','$col10','$col11','$col12','$col13')";
			$insl= $conn->prepare($sql);
            $insl->execute();
			
			}

            echo "upload complete.";
        }
        else
        { ?>
            <form action="#" method="post" target="_blank" enctype="multipart/form-data" name="form" id="form">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
		<div class="form-group">
		<td width="10%" align="left" valign="top"><label>Upload List</label></td>
		<td width="25%" align="left" valign="top"><input name="file" type="file" id="csv">
		<p class="help-block">Upload only in .csv file only.</p></td>
</div> 
</tr> <tr><td><label>&nbsp;&nbsp;&nbsp;</label></td></tr> <tr>

   							<td  align="left" ><button type="submit" name="submit" class="btn btn-primary">Import</button> </td>					
                 </tr>
     <?php   }




?>
