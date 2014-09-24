<?php
/*
 * jQuery File Upload Plugin PHP Example 5.5
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

session_start();

require_once("config/config.inc.php");
require_once("includes/Database.php");
require_once("includes/verify_access.php");
restrict_access($db,array("patron"));

require_once("load.php");

$room_id = $_POST['room_id'];
$type = $_POST['type'];
$rooms = load_rooms($room_id);
$room = $rooms[$room_id];


$upload_handler = new UploadHandler();

// print("<Pre>\n");
// print_r($room);
// print_r($upload_handler);
// exit();

file_put_contents("out.txt","start");

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');



switch ($_SERVER['REQUEST_METHOD']) {
    case 'OPTIONS':
        break;
    case 'HEAD':
    case 'GET':
		file_put_contents("out.txt","trying get");
        $upload_handler->get();
        break;
    case 'POST':
		file_put_contents("out.txt","trying post");
        $upload_handler->post();
        break;
    case 'DELETE':
        $upload_handler->delete();
        break;
    default:
        header('HTTP/1.1 405 Method Not Allowed');
}


 

class UploadHandler
{
    private $options;

    function __construct($options=null) {
		global $room_id;
		
		if(!is_dir(dirname(__FILE__).'/images/rooms/'.$room_id))
		{
			mkdir(dirname(__FILE__).'/images/rooms/'.$room_id);
		}
		
        $this->options = array(
            'script_url' => $this->getFullUrl().'/'.basename(__FILE__),
            'upload_dir' => dirname(__FILE__).'/images/rooms/'.$room_id.'/',
            'upload_url' => $this->getFullUrl().'/images/rooms/'.$room_id.'/',
            'param_name' => 'files',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/.+$/i',
            'max_number_of_files' => null,
            // Set the following option to false to enable non-multipart uploads:
            'discard_aborted_uploads' => true,
            // Set to true to rotate images based on EXIF meta data, if available:
            'orient_image' => false,
            'image_versions' => array(
                'large' => array(
                    'upload_dir' => dirname(__FILE__).'/images/rooms/'.$room_id.'/',
                    'upload_url' => $this->getFullUrl().'/images/rooms/'.$room_id.'/',
                    'max_width' => 800,
                    'max_height' => 600
                ),
                'thumbnail' => array(
                    'upload_dir' => dirname(__FILE__).'/thumbnails/',
                    'upload_url' => $this->getFullUrl().'/thumbnails/',
                    'max_width' => 80,
                    'max_height' => 80
                )
            )
        );
        if ($options) {
            $this->options = array_replace_recursive($this->options, $options);
        }
    }

    function getFullUrl() {
      	return
        		(isset($_SERVER['HTTPS']) ? 'https://' : 'http://').
        		(isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
        		(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
        		(isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] === 443 ||
        		$_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
        		substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }
    
    private function get_file_object($file_name) {
        $file_path = $this->options['upload_dir'].$file_name;
        if (is_file($file_path) && $file_name[0] !== '.') {
            $file = new stdClass();
            $file->name = $file_name;
            $file->size = filesize($file_path);
            $file->url = $this->options['upload_url'].rawurlencode($file->name);
            foreach($this->options['image_versions'] as $version => $options) {
                if (is_file($options['upload_dir'].$file_name)) {
                    $file->{$version.'_url'} = $options['upload_url']
                        .rawurlencode($file->name);
                }
            }
            $file->delete_url = $this->options['script_url']
                .'?file='.rawurlencode($file->name);
            $file->delete_type = 'DELETE';
            return $file;
        }
        return null;
    }
    
    private function get_file_objects() {
        return array_values(array_filter(array_map(
            array($this, 'get_file_object'),
            scandir($this->options['upload_dir'])
        )));
    }

    private function create_scaled_image($file_name, $options) {
        $file_path = $this->options['upload_dir'].$file_name;
        $new_file_path = $options['upload_dir'].$file_name;
        list($img_width, $img_height) = @getimagesize($file_path);
        if (!$img_width || !$img_height) {
            return false;
        }
        $scale = min(
            $options['max_width'] / $img_width,
            $options['max_height'] / $img_height
        );
        if ($scale > 1) {
            $scale = 1;
        }
        $new_width = $img_width * $scale;
        $new_height = $img_height * $scale;
        $new_img = @imagecreatetruecolor($new_width, $new_height);
        switch (strtolower(substr(strrchr($file_name, '.'), 1))) {
            case 'jpg':
            case 'jpeg':
                $src_img = @imagecreatefromjpeg($file_path);
                $write_image = 'imagejpeg';
                break;
            case 'gif':
                @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
                $src_img = @imagecreatefromgif($file_path);
                $write_image = 'imagegif';
                break;
            case 'png':
                @imagecolortransparent($new_img, @imagecolorallocate($new_img, 0, 0, 0));
                @imagealphablending($new_img, false);
                @imagesavealpha($new_img, true);
                $src_img = @imagecreatefrompng($file_path);
                $write_image = 'imagepng';
                break;
            default:
                $src_img = $image_method = null;
        }
        $success = $src_img && @imagecopyresampled(
            $new_img,
            $src_img,
            0, 0, 0, 0,
            $new_width,
            $new_height,
            $img_width,
            $img_height
        ) && $write_image($new_img, $new_file_path);
        // Free up memory (imagedestroy does not delete files):
        @imagedestroy($src_img);
        @imagedestroy($new_img);
        return $success;
    }
    
    private function has_error($uploaded_file, $file, $error) {
        if ($error) {
            return $error;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            return 'acceptFileTypes';
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            return 'maxFileSize';
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            return 'minFileSize';
        }
        if (is_int($this->options['max_number_of_files']) && (
                count($this->get_file_objects()) >= $this->options['max_number_of_files'])
            ) {
            return 'maxNumberOfFiles';
        }
        return $error;
    }
    
    private function trim_file_name($name, $format) {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $file_name = trim(basename(stripslashes($name)), ".\x00..\x20");
        // Add missing file extension for known image types:
        if (strpos($file_name, '.') === false &&
            preg_match('/^image\/(gif|jpe?g|png)/', $format, $matches)) {
            $file_name .= '.'.$matches[1];
        }
        return $file_name;
    }

    private function orient_image($file_path) {
      	$exif = exif_read_data($file_path);
      	$orientation = intval(@$exif['Orientation']);
      	if (!in_array($orientation, array(3, 6, 8))) { 
      	    return false;
      	}
      	$image = @imagecreatefromjpeg($file_path);
      	switch ($orientation) {
        	  case 3:
          	    $image = @imagerotate($image, 180, 0);
          	    break;
        	  case 6:
          	    $image = @imagerotate($image, 270, 0);
          	    break;
        	  case 8:
          	    $image = @imagerotate($image, 90, 0);
          	    break;
          	default:
          	    return false;
      	}
      	$success = imagejpeg($image, $file_path);
      	// Free up memory (imagedestroy does not delete files):
      	@imagedestroy($image);
      	return $success;
    }
    
    private function handle_file_upload($uploaded_file, $name, $size, $format, $error) {
	
        $file = new stdClass();
        $file->name = $this->trim_file_name($name, $format);
        $file->size = intval($size);
        $file->type = $format;
		
        $error = $this->has_error($uploaded_file, $file, $error);
		
		file_put_contents("out.txt",json_encode($error));
		
        if (!$error && $file->name) {
            $file_path = $this->options['upload_dir'].$file->name;
            $append_file = !$this->options['discard_aborted_uploads'] &&
                is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = filesize($file_path);
            if ($file_size === $file->size) {
            		if ($this->options['orient_image']) {
            		    $this->orient_image($file_path);
            		}
                $file->url = $this->options['upload_url'].rawurlencode($file->name);
                foreach($this->options['image_versions'] as $version => $options) {
                    if ($this->create_scaled_image($file->name, $options)) {
                        $file->{$version.'_url'} = $options['upload_url']
                            .rawurlencode($file->name);
                    }
                }
            } else if ($this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $file->delete_url = $this->options['script_url']
                .'?file='.rawurlencode($file->name);
            $file->delete_type = 'DELETE';
        } else {
            $file->error = $error;
        }
		
		file_put_contents("out.txt",json_encode($file));
		
        return $file;
    }
    
    public function get() {
        $file_name = isset($_REQUEST['file']) ?
            basename(stripslashes($_REQUEST['file'])) : null;
        if ($file_name) {
            $info = $this->get_file_object($file_name);
        } else {
            $info = $this->get_file_objects();
        }
        header('Content-type: application/json');
        echo json_encode($info);
    }
    
    public function post() {
		
		global $room,$db,$type;
		
		file_put_contents("out.txt","beginning of post");
		
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete();
        }
		
		// file_put_contents("out.txt","after delete option");
		
        $upload = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : null;
        $info = array();
		file_put_contents("out.txt","multi upload1 === ".implode("|",$upload));
        if ($upload && is_array($upload['tmp_name'])) {
			file_put_contents("out.txt","multi upload2 === ".implode("|",$upload));
            foreach ($upload['tmp_name'] as $index => $uploaded_file) {
				// insert image into database
				file_put_contents("out.txt","here");
				list($width,$height) = @getimagesize($uploaded_file);
				file_put_contents("out.txt","width:" . $width);
				$size = isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index];
				$format = isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index];
				$error = $upload['error'][$index];
				
				file_put_contents("out.txt",json_encode($error));
				
				$fields = array('room_id','type','format','width','height','date_added');
				$values = array($room->id,$type,$format,$width,$height,date('Y-m-d H:i:s'));
				$image_id = $db->insert('room_images',$fields,$values);
				
				switch($format)
				{
					case 'image/jpeg': $extension = "jpg"; break;
					case 'image/png': $extension = "png"; break;
					case 'image/gif': $extension = "gif"; break;
					default: $extension = str_replace("images/","",$room_image->format);
				}
				$name = $type.$room->id."_".$image_id.".".$extension;
				
				file_put_contents("out.txt",$name);
				
				$info[] = $this->handle_file_upload($uploaded_file, $name, $size, $format, $error);
            }
        } elseif ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
            // insert image into database
			file_put_contents("out.txt","trying to insert into db");
			$uploaded_file = $upload['tmp_name'];
			list($width,$height) = @getimagesize($uploaded_file);
			$size = isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index];
			$format = isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index];
			$error = $upload['error'][$index];
			
			$fields = array('room_id','type','format','width','height','date_added');
			$values = array($room->id,$type,$format,$width,$height,date('Y-m-d H:i:s'));
			$image_id = $db->insert('room_images',$fields,$values);
			
			switch($format)
			{
				case 'image/jpeg': $extension = "jpg"; break;
				case 'image/png': $extension = "png"; break;
				case 'image/gif': $extension = "gif"; break;
				default: $extension = str_replace("images/","",$room_image->format);
			}
			$name = $type.$room->id."_".$image_id.".".$extension;
			
			$info[] = $this->handle_file_upload($uploaded_file, $name, $size, $format, $error);
        }
		else
		{
			file_put_contents("out.txt","why here");
		}
        header('Vary: Accept');
        $json = json_encode($info);
        $redirect = isset($_REQUEST['redirect']) ?
            stripslashes($_REQUEST['redirect']) : null;
        if ($redirect) {
            header('Location: '.sprintf($redirect, rawurlencode($json)));
            return;
        }
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
        echo $json;
    }
    
    public function delete() {
        $file_name = isset($_REQUEST['file']) ?
            basename(stripslashes($_REQUEST['file'])) : null;
        $file_path = $this->options['upload_dir'].$file_name;
        $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
        if ($success) {
            foreach($this->options['image_versions'] as $version => $options) {
                $file = $options['upload_dir'].$file_name;
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        header('Content-type: application/json');
        echo json_encode($success);
    }

}

?>
