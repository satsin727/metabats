				$filename = $_FILES["file"]['name'];
	$tmpfilename = $_FILES["file"]['tmp_name'];
	$sExistingFileName 	= basename($filename);
	$aFilename    		= explode(".",$sExistingFileName);
	$sExtName    		= array_pop($aFilename);
	$sPostFileName       = $aFilename[0];
	$sDirPath          = './files/lists/';
	$sNewFileName      = $sPostFileName.'.'.$sExtName;
	$sFileOrigPath     = $sDirPath.$sNewFileName;
	if(move_uploaded_file($tmpfilename, $sFileOrigPath))
	{
	   $sNewFile      = $sNewFileName;
	}
	
	//Read the file from the directory	
	if (($getfile = fopen($sFileOrigPath, "r")) !== FALSE) //$sFileOrigPath: Path of directory where CSV file is uploaded
	{
		$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
		$data = fgetcsv($getfile, 1000, ","); //fgetcsv � Gets line from file pointer and parse for CSV fields
		while (($data = fgetcsv($getfile, 1000, ",")) !== FALSE) 
		{
			$iNum = count($data);
			$sResult = $data;
			$sCSVData = implode(",", $sResult);
			$aCSVData = explode(",", $sCSVData);
			
			$companyname = $aCSVData[0];//Values of first column in excel sheet
			$rname = $aCSVData[1];//Values of second column in excel sheet
			$remail = $aCaSVData[2];//Values of first column in excel sheet
			$rphone = $aCSVData[3];//Values of second column in excel sheet
			$rlocation = $aCaSVData[4];//Values of second column in excel sheet
			$rtimezon = $aCaSVData[5];//Values of first column in excel sheet
			$tier = $aCSVData[6];//Values of second column in excel sheet
			$rfname = $aCaSVData[7];//Values of second column in excel sheet
		
			//Add a PDO insert statement to add data into the database table
			$sql = "INSERT into clients (`cid`, `lid`, `uid`, `companyname`, `rname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier`, `rfname`) values (Null, '$listid', '$userid', '$companyname','$rname','$remail','$rphone','rlocation','$rtimezon','$tier','$rfname')";
	        $insl= $conn->prepare($sql);
			$insl->execute();
		}
	}

		/*	$filename=$_FILES["file"]["tmp_name"];
 
 
		 if($_FILES["file"]["size"] > 0)
		 {
 
		  	$file = fopen($filename, "r");
		  	$conn = new PDO( DB_DSN, DB_USERNAME, DB_PASSWORD );
	         while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE)
	         {	         
	           $sql = "INSERT into clients (`cid`, `lid`, `uid`, `companyname`, `rname`, `remail`, `rphone`, `rlocation`, `rtimezon`, `tier`, `rfname`) values (Null, '$listid', '$userid', '$emapData[1]','$emapData[2]','$emapData[3]','$emapData[4]','$emapData[5]','$emapData[6]','$emapData[7]','$emapData[8]')";
	         			$insl= $conn->prepare($sql);
						$insl->execute();
	         }
	         fclose($file);
	         //throws a message if data successfully imported to mysql database from excel file
	         echo "<script type=\"text/javascript\">
						alert(\"CSV File has been successfully Imported.\");
						window.location = \"index.php\"
					</script>";
 
 
		 }*/