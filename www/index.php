<?php

header('P3P: CP="CAO PSA OUR"');

ob_start();

session_start();
// die("Critical Maintenance in progress");
#Define App Path
define("D_PATH", dirname(dirname(__FILE__)));
CONST APP_PATH = D_PATH."/v1";


#load config
include D_PATH."/.env/config.php";
#load database
require APP_PATH."/models/model.php";
#load Controllers(functions)
require APP_PATH."/controllers/controller.php";

#load auth Controllers(functions)
require APP_PATH."/auth/auth_controller/controller.php";
#load routes
// require APP_PATH."/routes/router.php";

$websiteInfo = selectContent($conn, "read_website_info", ['visibility' => 'show']);
$officeHours = selectContent($conn, "panel_office_hours", ['visibility' => 'show']);
// $websiteStyle = selectContent($conn, "website_status", ['visibility' => 'show']);

$_SESSION['color'] = "green";
// $_SESSION['debug'] = true;
//
$site_name = $websiteInfo[0]['input_name'];
$site_email = $websiteInfo[0]['input_email'];
$site_email_2 = $websiteInfo[0]['input_email_2'];
$site_email_from = $websiteInfo[0]['input_email_from'];
$site_email_smtp_host = $websiteInfo[0]['input_email_smtp_host'];
$site_email_smtp_secure_type = $websiteInfo[0]['input_email_smtp_secure_type'];
$site_email_smtp_port = $websiteInfo[0]['input_email_smtp_port'];
$site_email_password = $websiteInfo[0]['input_email_password'];
$site_phone = $websiteInfo[0]['input_phone_number'];
$site_phone_1 = $websiteInfo[0]['input_phone_number_1'];
$site_address = $websiteInfo[0]['input_address'];
$fbLink = $websiteInfo[0]['input_facebook'];
$igLink = $websiteInfo[0]['input_instagram'];
$linkedinLink = $websiteInfo[0]['input_linkedin'];
$twitterLink = $websiteInfo[0]['input_twitter'];
$description = $websiteInfo[0]['text_description'];
$logo_directory = $websiteInfo[0]['image_1'];
$domain = $_SERVER['HTTP_HOST'];

// die(var_dump($domain));
//
// if($websiteStyle[0]['status'] === "live"){
// if (count($websiteStyle) > 0 && $websiteStyle[0]['color'] !="") {
//   $style_color = $websiteStyle[0]['color'];
// }else{
//   // die(count($websiteStyle[0]['color']));
//   // unset($style_color);
//     }
// }
//
//
// if($websiteStyle[0]['status'] === "demo"){
// if (isset($_SESSION['color'])) {
//   $style_color = $_SESSION['color'];
// }
// }
//
//
// if($websiteStyle[0]['status'] === "demo"){
// if (isset($_SESSION['image_select'])) {
//   $logo_directory = $_SESSION['image_select'];
// }
// }


$fbid = "2213158278782711";




#load routes
include APP_PATH."/ajax/ajax_router/router.php";
// include APP_PATH."/routes/ajax_router.php";
// include APP_PATH."/payment/payment_router/router.php";
// include APP_PATH."/auth/auth_router/router.php";
// include APP_PATH."/routes/admin_router.php";
include APP_PATH."/routes/router.php";


#load auth Controllers(functions)
// require APP_PATH."/auth_controller/controller.php";

 ?>
