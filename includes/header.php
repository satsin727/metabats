<?php
require_once("config.php");
if(isset($_SESSION['username']))
{
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Metahorizon BATS - Dashboard</title>

<!-- jQuery & Plugins -->
<script src="js/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery.easing-1.3.pack.js"></script>
<script type="text/javascript" src="js/jquery.mousewheel-3.0.4.pack.js"></script>
<script type="text/javascript" src="js/jquery.fancybox-1.3.4.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.fancybox-1.3.4.css" media="screen" />

<script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>

<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">
<link rel="stylesheet" href="css/accessibility.css">
 
<!-- Icons -->
<script src="js/lumino.glyphs.js"></script>
<script type="text/javascript" src="ckeditor/ckeditor.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        $("#various3").fancybox({
            'width'             : '75%',
            'height'            : '75%',
            'autoScale'         : false,
            'transitionIn'      : 'none',
            'transitionOut'     : 'none',
            'type'              : 'iframe'
        });
    });
</script>

<style>
    /* WCAG AA Compliant Typography & Base Styles */
    body {
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 16px;
        line-height: 1.5;
        color: #212529;
        background-color: #f8f9fa;
    }

    /* High-visibility Focus States for Accessibility */
    a:focus, button:focus, input:focus {
        outline: 3px solid #005fcc !important;
        outline-offset: 2px !important;
    }

    /* Modernized Search Box */
    .search-box {
        width: 300px;
        position: relative;
        display: inline-block;
        font-size: 16px;
    }
    .search-box input[type="text"]{
        height: 38px;
        padding: 6px 12px;
        border: 2px solid #495057;
        border-radius: 4px;
        font-size: 16px;
        color: #212529;
        background-color: #ffffff;
    }
    .result {
        position: absolute;        
        z-index: 999;
        top: 100%;
        left: 0;
        background: #ffffff;
        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    }
    .search-box input[type="text"], .result{
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Accessible Result Items */
    .result p {
        margin: 0;
        padding: 10px 14px;
        border: 1px solid #ced4da;
        border-top: none;
        cursor: pointer;
        background: #ffffff;
        color: #212529;
        font-size: 15px;
    }
    .result p:hover, .result p:focus {
        background: #e9ecef;
        color: #000000;
        text-decoration: underline;
    }
</style>

<!-- Datepicker Initialization -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
  $(function() {
    $( "#datepicker" ).datepicker();
    $( "#datepicker2" ).datepicker();
  });
</script>

</head>
<body>
<?php
}
else
{ 
    echo "<script>
    alert('Not Authorised to view this page. !!!');
    window.location.href='../login.php';
    </script>"; 
} 
?>