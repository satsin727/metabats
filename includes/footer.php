<?php 

$conn=null;

if(isset($_SESSION['username'])) { 	?>

	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/chart.min.js"></script>
	<!--<script src="js/chart-data.js"></script>
	 <script src="js/easypiechart.js"></script>
	<script src="js/easypiechart-data.js"></script> -->
	<script src="js/bootstrap-datepicker.js"></script>
	<script src="js/bootstrap-table.js"></script>
	<script>
		$('#calendar').datepicker({
		});

		!function ($) {
		    $(document).on("click","ul.nav li.parent > a > span.icon", function(){          
		        $(this).find('em:first').toggleClass("glyphicon-minus");      
		    }); 
		    $(".sidebar span.icon").find('em:first').addClass("glyphicon-plus");
		}(window.jQuery);

		$(window).on('resize', function () {
		  if ($(window).width() > 768) $('#sidebar-collapse').collapse('show')
		})
		$(window).on('resize', function () {
		  if ($(window).width() <= 767) $('#sidebar-collapse').collapse('hide')
		})
	</script>
	<script>
	$(document).ready(function() {
		// Close button handler
		$("#close-sidebar").on("click", function() {
			$("#sidebar-collapse").hide();
			$(".main").removeClass("col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2").addClass("col-sm-12 col-lg-12");
		});

		// Optional: Toggle sidebar by clicking the brand name (or use another toggle button)
		$(".navbar-brand").on("click", function(e) {
			e.preventDefault();
			$("#sidebar-collapse").toggle();
			$(".main").toggleClass("col-sm-9 col-sm-offset-3 col-lg-10 col-lg-offset-2 col-sm-12 col-lg-12");
		});
	});
	</script>

</body>

</html> 
<?php
}
else
{ echo "<script>
alert('Not Authorised to view this page. !!!');
window.location.href='../login.php';
</script>";  } ?>