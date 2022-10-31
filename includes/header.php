<?php
require_once("config.php");
if(isset($_SESSION['username']))
{

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Metahorizon BATS - Dashboard</title>
<!-- 
	<script src="js/jquery-1.4.3.min.js"></script> 
 <script src='js/jquery-3.4.1.min.js' type='text/javascript'></script> 
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script>
		!window.jQuery && document.write('<script src="js/jquery-1.4.3.min.js"><\/script>');
</script> -->

<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.easing-1.3.pack.js"></script>
<script type="text/javascript" src="js/jquery.mousewheel-3.0.4.pack.js"></script>
<script type="text/javascript" src="js/jquery.fancybox-1.3.4.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.fancybox-1.3.4.css" media="screen" />

<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<!-- <link rel="stylesheet" href="css/bootstrap-datepicker.css">
<script src="js/bootstrap-datepicker.js"></script> -->

<link href="css/bootstrap.min.css" rel="stylesheet">
<!-- <link href="css/datepicker3.css" rel="stylesheet"> -->
<link href="css/styles.css" rel="stylesheet">
  
<!--Icons-->
<script src="js/lumino.glyphs.js"></script>


<!-- select2 css -->
<!-- <link href='css/select2/dist/css/select2.min.css' rel='stylesheet' type='text/css'> -->

<!--  select2 script -->
<!-- <script src='css/select2/dist/js/select2.min.js'></script> -->
<script type="text/javascript" src="ckeditor/ckeditor.js"></script>
<!--
<script>
function openPopupWin(pageURL)
	{
		//  Opens a popup window.
		//  **********
		var pw = 400;		// pixels - change for your desired popup width
		var ph = 350;		// pixels - change for your popup height
		var left   = (screen.width  - pw)/2;
		var top    = (screen.height - ph)/2;
		var parms = 'modal=no, dialog=no, height='+ ph +', width=' + pw + ',left=' + left + ', top=' + top + ', status=no, location=no, menubar=no, title=no, scrollbars=no,resizable=no';
		var uid = window.open(pageURL, '', parms);
	}
</script> 
<script type="text/javascript" src="js/fancybox/jquery.mousewheel-3.0.4.pack.js"></script>
<script type="text/javascript" src="js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" type="text/css" href="js/fancybox/jquery.fancybox-1.3.4.css" media="screen" /> -->
<script type="text/javascript">
		$(document).ready(function() {
		
			$("#various3").fancybox({
				'width'				: '75%',
				'height'			: '75%',
				'autoScale'			: false,
				'transitionIn'		: 'none',
				'transitionOut'		: 'none',
				'type'				: 'iframe'
			});

		});
</script>
<style>
    body{
        font-family: Arail, sans-serif;
    }
    /* Formatting search box */
    .search-box{
        width: 300px;
        position: relative;
        display: inline-block;
        font-size: 14px;
    }
    .search-box input[type="text"]{
        height: 32px;
        padding: 5px 10px;
        border: 1px solid #CCCCCC;
        font-size: 14px;
    }
    .result{
        position: absolute;        
        z-index: 999;
        top: 100%;
        left: 0;
    }
    .search-box input[type="text"], .result{
        width: 100%;
        box-sizing: border-box;
    }
    /* Formatting result items */
    .result p{
        margin: 0;
        padding: 7px 10px;
        border: 1px solid #CCCCCC;
        border-top: none;
        cursor: pointer;
        background: #f2f2f2;
    }
    .result p:hover{
        background: #f7e6e6;
    }
</style>
<script src="js/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function(){
    $('.search-box input[type="text"]').on("keyup input", function(){
        /* Get input value on change */
        var inputVal = $(this).val();
        var resultDropdown = $(this).siblings(".result");
        if(inputVal.length){
            $.get("backend-search.php", {term: inputVal}).done(function(data){
                // Display the returned data in browser
                resultDropdown.html(data);
            });
        } else{
            resultDropdown.empty();
        }
    });
    
    // Set search input value on click of result item
    $(document).on("click", ".result p", function(){
        $(this).parents(".search-box").find('input[type="text"]').val($(this).text());
        $(this).parent(".result").empty();
    });
});
</script>
<script>
  $( function() {
    $( "#datepicker" ).datepicker();
  } );
 </script>

<script>
  $( function() {
    $( "#datepicker2" ).datepicker();
  } );
</script>

</head>

<?php
}
else
{ echo "<script>
alert('Not Authorised to view this page. !!!');
window.location.href='../login.php';
</script>";  } ?>
