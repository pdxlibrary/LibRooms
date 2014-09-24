<?php

// ini_set("display_errors","1");

define("WURFL_DIR", "/srv/www/library.pdx.edu/php/wurfl/WURFL/");
define("RESOURCES_DIR", "/srv/www/library.pdx.edu/php/wurfl/resources/");

require_once(WURFL_DIR . "Application.php");


$persistenceDir = RESOURCES_DIR . "storage/persistence";
$cacheDir = RESOURCES_DIR . "storage/cache";
$wurflConfig = new WURFL_Configuration_InMemoryConfig();

// flakus - changed wurfl.-2.0.23.zip to wurfl.xml for $wurflConfig->wurflFile

$wurflConfig
        ->wurflFile(RESOURCES_DIR . "wurfl.xml")
        ->wurflPatch(RESOURCES_DIR . "web_browsers_patch.xml")
        ->persistence("file",array(
                                WURFL_Configuration_Config::DIR => $persistenceDir))
        ->cache("file", array(
                            WURFL_Configuration_Config::DIR => $cacheDir,
                            WURFL_Configuration_Config::EXPIRATION => 36000));

$wurflManagerFactory = new WURFL_WURFLManagerFactory($wurflConfig);

$wurflManager = $wurflManagerFactory->create(true);
$wurflInfo = $wurflManager->getWURFLInfo();



/********************************
  Device Detection
********************************/



$requestingDevice = $wurflManager->getDeviceForHttpRequest($_SERVER);


if(isset($_GET['debug']))
{
	print("<h3>WURFL XML INFO</h3>\n");
	print("<ul>\n");
	print("<li><h4>VERSION: $wurflInfo->version</h4></li>\n");
	print("</ul>\n");

	print("Requesting Browser User Agent: <b> ".$_SERVER["HTTP_USER_AGENT"]."</b>\n");
	print("<ul>\n");
	print("<li>ID: $requestingDevice->id</li>\n");
	print("<li>Brand Name: ".$requestingDevice->getCapability("brand_name")."</li>\n");
	print("<li>Model Name: ".$requestingDevice->getCapability("model_name")."</li>\n");
	print("<li>Xhtml Preferred Markup: ".$requestingDevice->getCapability("preferred_markup")."</li>\n");
	print("<li>Resolution Width: ".$requestingDevice->getCapability("resolution_width")."</li>\n");
	print("<li>Resolution Height: ".$requestingDevice->getCapability("resolution_height")."</li>\n");
	print("<li>Mobile Browser: ".$requestingDevice->getCapability("mobile_browser")."</li>\n");
	print("<li>Wireless Device: ".$requestingDevice->getCapability("is_wireless_device")."</li>\n");
	print("</ul>\n");

	$list = $wurflManager->getListOfGroups();
	$allCapabilities = $requestingDevice->getAllCapabilities();
	print("<pre>\n");
	print_r($allCapabilities);
	print_r($list);
	print("</pre>\n");
	exit();
}

?>