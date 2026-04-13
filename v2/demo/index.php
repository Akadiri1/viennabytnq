<?php
ob_start();

$dummy = "dummy.png";
$fbid = "2213158278782711";
session_start();
// die("Critical Maintenance in progress");
#Define App Path
define("D_PATH", dirname(dirname(__FILE__)));
CONST APP_PATH = D_PATH."/v1";
#load database
#load Controllers(functions)
include D_PATH."/.env/config.php";
#load routes
require APP_PATH."/models/model.php";
require APP_PATH."/controllers/controller.php";
require APP_PATH."/auth/auth_controller/controller.php";
#load routes
// require APP_PATH."/routes/router.php";


$websiteInfo = selectContent($conn, "read_website_info", ['visibility' => 'show']);
$websiteStyle = selectContent($conn, "website_status", ['visibility' => 'show']);
// $fetchFavicon = selectContent($conn, "read_favicon", ['visibility' => 'show']);


// $_SESSION['color'] = "green";

$site_name = $websiteInfo[0]['input_name'];
$site_email = $websiteInfo[0]['input_email'];
$site_phone = $websiteInfo[0]['input_phone_number'];
$site_address = $websiteInfo[0]['input_address'];
$fbLink = $websiteInfo[0]['input_facebook'];
$igLink = $websiteInfo[0]['input_instagram'];
$linkedinLink = $websiteInfo[0]['input_linkedin'];
$twitterLink = $websiteInfo[0]['input_twitter'];
// $behanceLink = $websiteInfo[0]['input_behance'];
// $dribbbleLink = $websiteInfo[0]['input_dribbble'];
$description = $websiteInfo[0]['text_description'];
$logo_directory = $websiteInfo[0]['image_1'];
// $favicon = $fetchFavicon[0]['image_1'];

if($websiteStyle[0]['status'] === "live"){
if (count($websiteStyle) > 0 && $websiteStyle[0]['color'] !="") {
  $style_color = $websiteStyle[0]['color'];
}else{
  // die(count($websiteStyle[0]['color']));
  // unset($style_color);
    }
}


if($websiteStyle[0]['status'] === "demo"){
if (isset($_SESSION['color'])) {
  $style_color = $_SESSION['color'];
}
}


if($websiteStyle[0]['status'] === "demo"){
if (isset($_SESSION['image_select'])) {
  $logo_directory = $_SESSION['image_select'];
}
}

// $style_bgcolor = $style_color;
// $style_bgcolor = "f7f8fa";





include APP_PATH."/routes/router.php";
include APP_PATH."/demo/demo_router/router.php";
include APP_PATH."/ajax/ajax_router/router.php";
include APP_PATH."/payment/payment_router/router.php";
include APP_PATH."/auth/auth_router/router.php";
include APP_PATH."/routes/ajax_router.php";
include APP_PATH."/routes/admin_router.php";

 ?>
