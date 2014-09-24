<?php

	if(isset($_GET['logoff']))
	{
		unset($_SESSION['LibRooms']);
		$_SESSION['LibRooms']['FlashMessage'] = "You are now logged out";
		header("location: ./");
		exit();
	}
	
	//print_r($_SESSION);
	
	/*	WORDPRESS HEADER */
	print(file_get_contents("http://library.pdx.edu/includes/psu_library_header_wp_responsive.inc.php?page_title=$page_title&breadcrumb_trail=$breadcrumb_trail"));

?>
<!-- wordpress theme adjustments -->
<style>
#page {margin-top:0;}
.thin_header {display:none;}
#psulib_header_background {height:160px;}
</style>

<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.css" />
<link rel="stylesheet" href="<?php print(THEME); ?>mobile.css" />
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="http://code.jquery.com/mobile/1.3.2/jquery.mobile-1.3.2.min.js"></script>
<script>
jQuery(function($){
  $("a").attr("rel","external");
});
</script>
<?php
if(basename($_SERVER['PHP_SELF']) == 'confirm_reservation2.php') {
?>
	
	<link href="photoswipe.css" type="text/css" rel="stylesheet" />
	<style type="text/css">
		div.gallery-row:after { clear: both; content: "."; display: block; height: 0; visibility: hidden; }
		div.gallery-item { float: left; width: 33.333333%; }
		div.gallery-item a { display: block; margin: 5px; border: 1px solid #3c3c3c; }
		div.gallery-item img { display: block; width: 100%; height: auto; }
		#Gallery1 .ui-content, #Gallery2 .ui-content { overflow: hidden; }
		.ui-loader {visibility:hidden;};
	</style>
	
	<script type="text/javascript" src="lib/jquery-1.6.1.min.js"></script>
	<script type="text/javascript" src="lib/jquery.mobile-1.0a4.1/jquery.mobile-1.0a4.1.js"></script>
	
	<script type="text/javascript" src="lib/simple-inheritance.min.js"></script>
	<script type="text/javascript" src="lib/jquery.animate-enhanced.min.js"></script>
	
	<script type="text/javascript" src="code-photoswipe-jQuery-1.0.11.min.js"></script>
<?php
}
?>
<script>
$( document ).ready(function() {
<?php
	if(basename($_SERVER['PHP_SELF']) == 'confirm_reservation2.php') {
?>
	$('div.gallery-page').live('pageshow', function(e){
					
				// Re-initialize with the photos for the current page
				$("div.gallery a", e.target).photoSwipe();
				return true;
				
			})
<?php
	}
	if(isset($_SESSION["LibRooms']['FlashMessage"]) && strcmp($_SESSION["LibRooms']['FlashMessage"],''))
	{
?>
		$("#flash_message").html("<div class='ui-overlay-shadow ui-body-e ui-corner-all' style='width: 90%; display: block; opacity: .95; position: absolute; padding: 0 5%; text-align: center; z-index: 9999; border-radius: 0; display:block;'><h3><?php print($_SESSION["LibRooms']['FlashMessage"]); ?></h3></div>");
		$("#flash_message").show().delay(6000).fadeOut(2000);
<?php
		// clear the message after it displays
		$_SESSION["LibRooms']['FlashMessage"] = "";
	}
?>

<?php
	if(isset($_SESSION["LibRooms']['FlashError"]) && strcmp($_SESSION["LibRooms']['FlashError"],''))
	{
?>
		$("#flash_error").html("<div class='ui-overlay-shadow ui-body-e ui-corner-all' style='width: 90%; display: block; opacity: .95; position: absolute; padding: 0 5%; text-align: center; color:red; z-index: 9999; border-radius: 0; display:block;'><h3><?php print($_SESSION["LibRooms']['FlashError"]); ?></h3></div>");
		$("#flash_error").show().delay(6000).fadeOut(2000);
<?php
		// clear the message after it displays
		$_SESSION["LibRooms']['FlashError"] = "";
	}
?>
});
</script>


<style>
a {text-decoration:none;}
a:hover {text-decoration:underline;}
body {font-size:13px;}
.ui-btn-inner {font-size: 13px;}
.ui-bar-a {background: #396b9e !important;}
.ui-body-a {background: transparent !important;}
.ui-bar-a a.ui-link {color: #FFF;}
.ui-bar-a a.ui-link:visited {color: #FFF;}
.overdue {background-color:red}
</style>

<title><?php print($page_title); ?></title>
</head>
<body style="" class="ui-mobile-viewport ui-overlay-a" cz-shortcut-listen="true">
	<div id="flash_message" style="display:none"></div><div id="flash_error" style="display:none"></div>
	<div data-role="page" id="page" data-theme="a" tabindex="0" class="ui-page ui-body-a ui-page-active">
	<div role="banner">
		<style>
			div.thin_header {
				margin: 0;
				padding: 0 0 0 40px;
				min-width: 260px;
				height: 45px;
				background: url("<?php print(THEME); ?>images/psu-logo-mobile.gif") transparent no-repeat;
				border-bottom: 1px solid #666666;
				overflow: hidden;
			}

			div.thin_header a.btn {
				background: url("http://library.pdx.edu/m/images/btn_psu_accent_green.png") no-repeat 0 8px;
				width: 50px;
				font-size: 1em;
				text-align: center;
				border: 0;
				font-weight: normal;
				color:#FFF;
			}

			div.thin_header a {
				float: left;
				border: 0;
				color:#FFF;
				font-size: 1.3em;
				margin: 0 5px;
				padding: 0 5px 0 10px;
				border-left: 1px dotted #999999;
			}

			div.thin_header * {
			height: 45px;
			line-height: 45px;
			}

			a, li a {
			text-decoration: none;
			}

			body {
			color: #222222;
			font-family: "Helvetica Neue", "Helvetica", "Arial", sans-serif;
			background: url("http://library.pdx.edu/m/images/bg_thin_header.png") repeat-x #F1EEEC !important;
			margin: 0;
			padding: 0;
			min-width: 310px;
			}
			
			.ui-fullsize .ui-btn-inner, .ui-fullsize .ui-btn-inner {font-size:13px;}
			
			#footer {text-align:center; font-size:12px;}
			</style>

			<div class="thin_header">
			<a href="#" data-rel="back" class="btn" data-ajax="false" style="color:#FFF">Back</a>
			<a href="/m" accesskey="1" class="btn" data-ajax="false" style="color:#FFF">Home</a>
			<a href="/m" accesskey="1" data-ajax="false" style="color:#FFF">Library</a></div>
		</div>
		<div data-role="header" class="ui-header ui-bar-a" role="banner">
			<a href="./" data-icon="search" data-ajax="false">Find a Room</a>
			<h1><?php //print($page_title); ?></h1>
			<a href="account.php" data-icon="gear" data-ajax="false">Account</a>
		</div>
		<div data-role="content" data-theme="a" class="ui-content ui-body-a" role="main">
		
		

		
