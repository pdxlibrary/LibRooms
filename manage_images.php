<?php

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("includes/verify_access.php");
restrict_access($db,array("admin"));

require_once("load.php");

$room_id = $_GET['room_id'];
$type = $_GET['type'];

if(isset($_GET['delete_image']))
{
	$delete_image = $_GET['delete_image'];
	$db->update('room_images','active','0',"id like '$delete_image'");
	$select_image = "select * from room_images where id like '$delete_image'";
	$res_image = $db->query($select_image);
	$res_image->fetchInto($image);
	switch($image->format)
	{
		case 'image/jpeg': $extension = "jpg"; break;
		case 'image/png': $extension = "png"; break;
		case 'image/gif': $extension = "gif"; break;
		default: $extension = str_replace("images/","",$room_image->format);
	}
	$name = $image->type.$image->room_id."_".$image->id.".".$extension;
	$image_location = dirname(__FILE__)."\\images\\rooms\\".$image->room_id."\\".$name;
	//print("delete: $image_location<br>\n");
	if(file_exists($image_location))
		unlink($image_location);
	
}

$rooms = load_rooms($room_id);
$room = $rooms[$room_id];



?>

<!DOCTYPE HTML>
<!--
/*
 * jQuery File Upload Plugin Demo 6.0.4
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
-->
<html lang="en">
<head>
<meta charset="utf-8">
<title>File Upload</title>
<!--
    The Bootstrap CSS is not required, but included for the demo.
    However, the FileUpload user interface waits for CSS transition events in several callbacks,
    if a "fade" class is present. These CSS transitions are defined by the Bootstrap CSS.
    If it is not included, either remove the "fade" class from the upload/download templates,
    or define your own "fade" class with CSS transitions.
-->
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/bootstrap-image-gallery.min.css">
<!--[if lt IE 7]><link rel="stylesheet" href="css/bootstrap-ie6.min.css"><![endif]-->
<link rel="stylesheet" href="css/jquery.fileupload-ui.css">
<meta name="description" content="File Upload widget with multiple file selection, drag&amp;drop support, progress bar and preview images for jQuery. Supports cross-domain, chunked and resumable file uploads. Works with any server-side platform (Google App Engine, PHP, Python, Ruby on Rails, Java, etc.) that supports standard HTML form file uploads.">
</head>
<body>

<?php

// print_r($room->images[$type]);
if(isset($room->images[$type]))
{
	foreach($room->images[$type] as $image)
	{
		print("<div style='float:left; padding-bottom:9px; text-align:center'>\n");
		print("<img src='$image->location' width='200' height='200'>");
		print("<br><a href='manage_images.php?type=$type&room_id=$room_id&delete_image=$image->id'><img src='images/icon_remove.gif' border='0' alt='Delete this Image'></a>\n");
		print("</div>\n");
	}
	print("<div style='clear:both' />\n");
}




?>


<hr>
<div class="container">
    <div class="page-header">
        <a href='<?php print($_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']); ?>'><span style='color:green; font-size:24px; float:right;padding:6px 26px 6px 6px;'>Refresh</span><img src='images/refresh.png' style='float:right' border='0'></a>
		<h1>File Uploader</h1>
    </div>
    <blockquote>
        <p>Supports multiple file selection, drag&amp;drop support, progress bar and preview images.</p>
    </blockquote>
	
    <br>
    <form id="fileupload" action="process_image_upload.php" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="room_id" value="<?php print($room_id); ?>">
		<input type="hidden" name="type" value="<?php print($type); ?>">
        <div class="row">
            <div class="span16 fileupload-buttonbar">
                <div class="progressbar fileupload-progressbar fade"><div style="width:0%;"></div></div>
                <span class="btn success fileinput-button">
                    <span>Add files...</span>
                    <input type="file" name="files[]" multiple>
                </span>
                <button type="submit" class="btn primary start">Start upload</button>
                <button type="reset" class="btn info cancel">Cancel upload</button>
                
                <input type="checkbox" class="toggle">
            </div>
        </div>
        <br>
        <div class="row">
            <div class="span16">
                <table class="zebra-striped"><tbody class="files"></tbody></table>
            </div>
        </div>
    </form>
</div>
<!-- gallery-loader is the loading animation container -->
<div id="gallery-loader"></div>
<!-- gallery-modal is the modal dialog used for the image gallery -->
<div id="gallery-modal" class="modal hide fade">
    <div class="modal-header">
        <a href="#" class="close">&times;</a>
        <h3 class="title"></h3>
    </div>
    <div class="modal-body"></div>
    <div class="modal-footer">
        <a class="btn primary next">Next</a>
        <a class="btn info prev">Previous</a>
        <a class="btn success download" target="_blank">Download</a>
    </div>
</div>
<script>

	var fileUploadErrors = {
		maxFileSize: 'File is too big',
		minFileSize: 'File is too small',
		acceptFileTypes: 'Filetype not allowed',
		maxNumberOfFiles: 'Max number of files exceeded',
		uploadedBytes: 'Uploaded bytes exceed file size',
		emptyResult: 'Empty file upload result'
	};

</script>
<script id="template-upload" type="text/html">
{% for (var i=0, files=o.files, l=files.length, file=files[0]; i<l; file=files[++i]) { %}
    <tr class="template-upload fade">
        <td class="preview"><span class="fade"></span></td>
        <td class="name">{%=file.name%}</td>
        <td class="size">{%=o.formatFileSize(file.size)%}</td>
        {% if (file.error) { %}
            <td class="error" colspan="2"><span class="label important">Error</span> {%=fileUploadErrors[file.error] || file.error%}</td>
        {% } else if (o.files.valid && !i) { %}
            <td class="progress"><div class="progressbar"><div style="width:0%;"></div></div></td>
            <td class="start">{% if (!o.options.autoUpload) { %}<button class="btn primary">Start</button>{% } %}</td>
        {% } else { %}
            <td colspan="2"></td>
        {% } %}
        <td class="cancel">{% if (!i) { %}<button class="btn info">Cancel</button>{% } %}</td>
    </tr>
{% } %}
</script>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
<script src="js/jquery.ui.widget.js"></script>
<!-- The Templates and Load Image plugins are included for the FileUpload user interface -->
<script src="js/tmpl.min.js"></script>
<script src="js/load-image.min.js"></script>
<!-- Bootstrap Modal and Image Gallery are not required, but included for the demo -->
<script src="http://twitter.github.com/bootstrap/1.4.0/bootstrap-modal.min.js"></script>

<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
<script src="js/jquery.iframe-transport.js"></script>
<script src="js/jquery.fileupload.js"></script>
<script src="js/jquery.fileupload-ui.js"></script>
<script src="js/application.js"></script>
<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE8+ -->
<!--[if gte IE 8]><script src="cors/jquery.xdr-transport.js"></script><![endif]-->

<script>
jQuery(document).ready(function($) {
	$('#fileupload').bind('fileuploaddone', function (e, data) {
		alert("done with upload");
	});
	$('#fileupload').bind('fileuploadprogress', function (e, data) {var progress = parseInt(data.loaded / data.total * 100, 10); alert(progress);})
});
</script>
</body> 
</html>