<?php


######### $VARIABLES DEPENDENCIES #######

$site_name = "";
$site_email = "";
$description = "";
$metakeys = "";
$logo_directory = "/images/dummy.png";
$fbid = "359014884692133";
if(isset($page_name)){
if($page_name == "home"){
  include 'seo/home_meta.php';
}elseif ($page_name == "event") {
  include 'seo/event_meta.php';
}elseif ($page_name == "training") {
  include 'seo/training_meta.php';
}elseif ($page_name == "contact") {
  include 'seo/contact_meta.php';
}elseif ($page_name == "project") {
    include 'seo/project_meta.php';
}elseif ($page_name == "blog") {
  include 'seo/blog_meta.php';
}else{
  include 'seo/others_meta.php';
}
}else{
  include 'seo/others_meta.php';
}
include 'seo/fb_head.php';































 ?>
