<!DOCTYPE html>
<html class="fa-events-icons-ready"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>Bench ATS - Metahorizon</title>

  <link rel="stylesheet" media="screen" href="application.css">

  <style>
    .breadcrumb{
      margin-left: 30px;
    }
    .alert{
      margin-left: 28px;
    }
    .container{
      margin-left:10px;
      margin-left:20px;
      width: auto !important;
    }
  </style>
  
</head>
<body>

<div class="navbar navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container" style="padding-left: 20px;">
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <a class="brand" href="http://hal.amplifiedsourcing.com/">Bench ATS</a>
      <div class="nav-collapse">
        <ul class="nav">
          <li><a href="http://hal.amplifiedsourcing.com/">Home</a></li>
<!--          <li></li> -->
          <li><a href="http://hal.amplifiedsourcing.com/leads">Jobs</a></li>
        </ul>
        <ul class="nav pull-right">
            <li>
              <form style="margin-bottom:0px;" action="http://hal.amplifiedsourcing.com/leads/search" accept-charset="UTF-8" method="post"><input name="utf8" type="hidden" value="✓"><input type="hidden" name="authenticity_token" value="blSd61taWFuCQ1r0+DUHq+qhywp8/1cRE1kYN1450GPdWP8aaU95aD6PQh5UfunaZelejhZN3LLRbbZPWjMUVQ==">
                <div class="navbar-search">
                  <input type="text" name="q" id="q" class="search-query" style="width:150px;" placeholder="Search leads">
                  <span class="add-on">
                    <a href="http://hal.amplifiedsourcing.com/leads/advance_search"><i class="icon-search icon-white"></i></a>
                  </span>
               </div>
</form>            </li>
              <li><a href="http://hal.amplifiedsourcing.com/people/sign_up">Sign Up</a></li>
            <li><a href="http://hal.amplifiedsourcing.com/people/sign_in">Sign In</a></li>
        </ul>
      </div>
    </div>
  </div>
</div>



<!--
<div style="width:100%">
<div style="float:left;width:400px;">
<table style="width:auto;">

<th><a href="/">Home</a> | </th>
<th><a target="_blank" href="http://jobs.leveragency.com/viewforum.php?f=3">Open Jobs</a> | </th>

-->
<!-- <th> | </th> -->
<!--
</table>
</div>

<div id="user_nav" style="float:right;margin-right:20px;">
    <b><a href="/people/sign_up">Register</a> | <a href="/people/sign_in">sign in</a></b>
</div>
</div>
-->

<div class="container">
 
  <div class="row">
  <ul class="breadcrumb">
        <li>
      <a href="http://hal.amplifiedsourcing.com/">Home</a> <span class="divider">&gt;</span>
    </li>
    <li class="active">Jobs</li>

  </ul>

<!-- span16 --> 
    <div class="span12"><style>
.search_jobs{
float:left;
padding-left:10px;
}
</style>
<h1>Jobs</h1>
<div class="well filter_area">
<!--
 | 
 | 
-->

<div class="search_jobs">L Number: <input type="text" name="lead_id" id="lead_id" style="width:75px;"></div>
<div class="search_jobs">Client Lead: <input type="text" name="client_lead_id" id="client_lead_id" style="width:75px;"></div>
<div class="search_jobs">Text Search: <input type="text" name="searchtype" id="searchtype" value=""></div>
<div class="search_jobs">Job Type: <select name="job_type" id="job_type" style="width:50px;"><option value=""></option><option value="C">C</option>
<option value="FTE">FTE</option>
<option value="C2H">C2H</option></select></div>
<div class="search_jobs"><input type="checkbox" id="close_job"> Include Closed Jobs </div>
<!-- <div class="search_jobs"><input type="checkbox" id="inactive_job"> Include Inactive Jobs </div> -->
<div class="search_jobs"><a id="filter" class="btn btn-success" href="javascript:;">Filter</a></div>
<div style="width:90px;margin-left:10px;float:left;display:none;" class="loading"><img src="./HAL_files/loading_image.gif"> <b>Loading</b></div>
</div>
<input type="hidden" name="direction" id="direction">
<input type="hidden" name="sort" id="sort">

<div id="list" style="clear:both;">
<div style="width:100%;">
  <div style="float:left;">Displaying <b>all 11</b> leads</div>
  <div style="clear:both;float:left;"></div>
</div>
<div style="clear:both;"></div>

<table class="table table-striped table-condensed table-bordered nowrap">
	<thead>
		<tr>
				</tr><tr>
					<th align="left"><a class="title current desc" data-sort="updated_at" data-direction="asc" href="javascript:;">Last Update</a></th>
					<th align="left"><a class="title" data-sort="qlty_overall" data-direction="asc" href="javascript:;">Rating</a></th>
					<th align="left"><a class="title" data-sort="id" data-direction="asc" href="javascript:;">L#</a></th>
					<th align="left">Title</th>
					<th align="left"><div style="width: 60px;"><div style="float:left;">In Play </div><a class="rating_popup" style="float: right;" href="http://hal.amplifiedsourcing.com/leads#inplay_info"><i class="icon-info-sign"></i></a></div></th>
					<th>Odds</th>
					
					
						<th align="left" style="width:50px;">Submit Candidate</th>
					<th align="left">Contact</th>
					<th>Comment</th>
				</tr>
		
	</thead>

	<!---  Code for get leads in orders -->


	<!-- end code for get leads array -->

			<tbody><tr style="height:20px;"><td colspan="24" style="font-weight:bold;font-size:16px;">Active Leads</td></tr>
				<tr>
				<td class="">05/07/20</td>
				<td class="rating_B rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">B</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3723">L-3723</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3723">120/hr+exp SAP ME / MII Architect. 5 Months.   Contract. Minneapolis, MN. (L-3723) (B)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3723">
            1/1/0
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3723">Submit</a></td>
				<td>
							<a href="mailto:tim.bauer@amplifiedsourcing.com?subject=Inquiry%20on%20L-3723">Tim</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3723/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="">05/07/20</td>
				<td class="rating_A rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">A</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3690">L-3690</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3690"> Senior AWS DevOps Engineer. 6+ Months.  25% Telecommute C2H. Madison, WI et al. (L-3690) (A)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3690">
            23/15/0
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3690">Submit</a></td>
				<td>
							<a href="mailto:jan.graves@amplifiedsourcing.com?subject=Inquiry%20on%20L-3690">Jan</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3690/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="rating_C">04/30/20</td>
				<td class="rating_B rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">B</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3721">L-3721</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3721">74/hr PM. 12+ Months.   Contract. Madison, WI. (L-3721) (B)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3721">
            14/9/0
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3721">Submit</a></td>
				<td>
							<a href="mailto:jan.graves@amplifiedsourcing.com?subject=Inquiry%20on%20L-3721">Jan</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3721/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
			<tr style="height:20px;"><td colspan="24" style="font-weight:bold;font-size:16px;">Waiting for feedback (In Play)</td></tr>
				<tr>
				<td class="">05/07/20</td>
				<td class="rating_A rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">A</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3707">L-3707</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3707">120/hr High Performance Trading System Developer (C#, C++, .NET). 12 Months.  25% Telecommute Contract. Chicago, IL et al. (L-3707) (A)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3707">
            25/22/6
					</a>
				</td>
				<td class="rating_A">
					55 %
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3707">Submit</a></td>
				<td>
							<a href="mailto:tim.bauer@amplifiedsourcing.com?subject=Inquiry%20on%20L-3707">Tim</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3707/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="">05/07/20</td>
				<td class="rating_A rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">A</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3720">L-3720</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3720"> Big Data Dev/Lead (Hadoop). 6+ Months.  25% Telecommute Contract. Batavia, IL. (L-3720) (A)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3720">
            18/7/2
					</a>
				</td>
				<td class="rating_B">
					25 %
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3720">Submit</a></td>
				<td>
							<a href="mailto:tim.bauer@amplifiedsourcing.com?subject=Inquiry%20on%20L-3720">Tim</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3720/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="rating_C">04/30/20</td>
				<td class="rating_A rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">A</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3683">L-3683</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3683">90/hr C++ Developer (Medical/Regulated). 6-12 Months.  20% Telecommute Contract. Minneapolis, MN. (L-3683) (A)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3683">
            13/7/3
					</a>
				</td>
				<td class="">
					12 %
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3683">Submit</a></td>
				<td>
							<a href="mailto:tim.bauer@amplifiedsourcing.com?subject=Inquiry%20on%20L-3683">Tim</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3683/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="">05/07/20</td>
				<td class="rating_A rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">A</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3709">L-3709</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3709">120/hr Trading System Front End Developer (JS, React). 12 Months.  25% Telecommute Contract. Chicago, IL et al. (L-3709) (A)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3709">
            17/13/1
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3709">Submit</a></td>
				<td>
							<a href="mailto:tim.bauer@amplifiedsourcing.com?subject=Inquiry%20on%20L-3709">Tim</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3709/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="rating_C">04/30/20</td>
				<td class="rating_B rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">B</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3719">L-3719</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3719">69/hr PM. 12+ Months.   Contract. Madison, WI. (L-3719) (B)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3719">
            7/4/2
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3719">Submit</a></td>
				<td>
							<a href="mailto:jan.graves@amplifiedsourcing.com?subject=Inquiry%20on%20L-3719">Jan</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3719/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="rating_C">04/08/20</td>
				<td class="rating_B rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">B</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3708">L-3708</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3708">64/hr Oracle DBA. 24+ Months.   Contract. Madison, WI. (L-3708) (B)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3708">
            9/8/1
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3708">Submit</a></td>
				<td>
							<a href="mailto:jan.graves@amplifiedsourcing.com?subject=Inquiry%20on%20L-3708">Jan</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3708/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
			<tr style="height:20px;"><td colspan="24" style="font-weight:bold;font-size:16px;">Waiting for feedback (No Pipe)</td></tr>
				<tr>
				<td class="">05/07/20</td>
				<td class="rating_C rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">C</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3714">L-3714</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3714">69.50/hr SAP Technical (Highjump). 7 Months.  25% Telecommute Contract. Nashville, TN. (L-3714) (C)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3714">
            5/3/0
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3714">Submit</a></td>
				<td>
							<a href="mailto:tim.bauer@amplifiedsourcing.com?subject=Inquiry%20on%20L-3714">Tim</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3714/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>
				<tr>
				<td class="rating_C">04/06/20</td>
				<td class="rating_B rating_center"><a class="rating_popup" style="text-decoration: none; color: black" href="http://hal.amplifiedsourcing.com/leads#rating_info">B</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3704">L-3704</a></td>
				<td><a href="http://hal.amplifiedsourcing.com/leads/3704">64/hr Part-Time SQL DBA. 12 Months.  100% Telecommute Contract. Madison, WI. (L-3704) (B)</a></td>
				<td>
					<a href="http://hal.amplifiedsourcing.com/pool/3704">
            17/13/0
					</a>
				</td>
				<td class="">
					-
				</td>
					<td><a class="btn btn-small btn_very_small btn-primary" href="http://hal.amplifiedsourcing.com/submit/3704">Submit</a></td>
				<td>
							<a href="mailto:jan.graves@amplifiedsourcing.com?subject=Inquiry%20on%20L-3704">Jan</a>
				</td>
				<td>
					<a class="fancybox" href="http://hal.amplifiedsourcing.com/comments/3704/list?model=lead"><i class="icon-list"></i></a>
				</td>
			</tr>

</tbody></table>
<div id="rating_block" style="display:none">
  <div id="rating_info">
    <img src="./HAL_files/rating_popup.jpg">
  </div>
</div>
<div id="inplay_block" style="display:none">
  <div style="width:auto;height:auto;overflow: auto;position:relative;"><div id="inplay_info" class="well">
    <strong>In Play. What the numbers mean.</strong>
    <ul>
      <li>1st Number - Total number of candidates submitted (all statuses except passive pings)</li>
      <li>2nd Number - Total number of candidates still active (all status except those that start with 'x' like tabled)</li>
      <li>3rd Number - Total number of candidates 'In Play' (either being screened by Amplified/Agent/Client or Submitted to Agent/Client).</li>
    </ul>
  </div></div>
</div>

</div>

<br>

<script>
//<![CDATA[


$('.fancybox').fancybox();




//]]>
</script></div>
  </div>
</div>

<!--
<div style="clear:both;margin-top:20px;">
<p class="notice"></p>
<span style="color:red;font-size:12px;"></span>
       <p class="alert"></p>
</div>
-->
 


<div id="fancybox-tmp" style="padding: 50px;"></div><div id="fancybox-loading" style="display: none;"><div style="top: -440px;"></div></div><div id="fancybox-overlay" style="background-color: rgb(119, 119, 119); opacity: 0.7; cursor: pointer; height: 769px; display: none;"></div><div id="fancybox-wrap" style="width: 520px; height: auto; top: 294px; left: 394px; display: none;"><div id="fancybox-outer"><div class="fancybox-bg" id="fancybox-bg-n"></div><div class="fancybox-bg" id="fancybox-bg-ne"></div><div class="fancybox-bg" id="fancybox-bg-e"></div><div class="fancybox-bg" id="fancybox-bg-se"></div><div class="fancybox-bg" id="fancybox-bg-s"></div><div class="fancybox-bg" id="fancybox-bg-sw"></div><div class="fancybox-bg" id="fancybox-bg-w"></div><div class="fancybox-bg" id="fancybox-bg-nw"></div><div id="fancybox-content" style="border-width: 10px; width: 500px; height: auto;"></div><a id="fancybox-close" style="display: none;"></a><div id="fancybox-title" style="display: none;"></div><a href="javascript:;" id="fancybox-left" style="display: none;"><span class="fancy-ico" id="fancybox-left-ico"></span></a><a href="javascript:;" id="fancybox-right" style="display: none;"><span class="fancy-ico" id="fancybox-right-ico"></span></a></div></div></body></html>