<?php
$uriText = $_SERVER['REQUEST_URI'];

$uri = explode("/",$_SERVER['REQUEST_URI']);

$token = NULL;
if(isset($_GET['token'])){
  $token = $_GET['token'];
}


if (explode("?",$uri[1])[0] == "linkedin") {
  include APP_PATH."/views/linkedin.php";
  die();
}

if (count($uri) > 2) {


  if (!empty($_GET) && strpos($uri[2], "?")) {
  $query_string = explode("?",$uri[2])[1];
  }else{
  $query_string = "";
  }


  switch ($uri[1]."/".$uri[2]) {
    // ...existing code...
    case "website/$uri[2]":
      include APP_PATH."/views/viewWebsite.php";
      die();
      break;

    // Route for shopdetails/{id}/{name}/{hash}
    case "shopdetail/$uri[2]":
      include APP_PATH."/views/shop.php";
      die();
      break;

    case "shopdetail/$uri[2]":
      include APP_PATH."/views/shop-detail.php";
      die();
      break;
  }
  



}else{
  if (!empty($_GET) && strpos($uri[1], "?")) {
  $query_string = explode("?",$uri[1])[1];
  }else{
  $query_string = "";
  }

  // $query_string = explode("?",$uri[1])[1];
  switch ($uri[1]) {
    case 'test':
    include APP_PATH."/views/test.php";
    break;

    case 'test?'.$query_string:
    include APP_PATH."/views/test.php";
    break;

    case 'more-about?'.$query_string:
    include APP_PATH."/views/more-about.php";
    break;

       case "shopdetail?".$query_string: 
      include APP_PATH."/views/shop-detail.php";
      die();
      break;

    case '':
    include APP_PATH."/views/home.php";
    break;

    case 'home':
    include APP_PATH."/views/home.php";
    break;

    case 'shop':
      if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
      }
      include APP_PATH."/views/shop.php";
      break;

    case 'pagination':
      if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
      }
      include APP_PATH."/views/includes/ajax/pagination.php";
      break;

      case 'cart':
      include APP_PATH."/views/includes/ajax/cart.php";
      break;

      case 'update-cart':
      include APP_PATH."/views/includes/ajax/update-cart.php";
      break;

      case 'delete-cart':
      include APP_PATH."/views/includes/ajax/delete-cart.php";
      break;

    case 'pagination?'.$query_string:
      if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
      }
      include APP_PATH."/views/includes/ajax/pagination.php";
      break;

    case 'index':
    include APP_PATH."/views/home.php";
    break;


    case 'contact-us':
    include APP_PATH."/views/contact.php";
    break;

    case 'about-us':
    include APP_PATH."/views/about.php";
    break;

    case "services":
    include APP_PATH."/views/services.php";
    break;

    case "service-details?".$query_string:
    include APP_PATH."/views/service-details.php";
    break;

    case "book-appointment":
    include APP_PATH."/views/book-appointment.php";
    break;

    case "areas-we-cover":
    include APP_PATH."/views/areas.php";
    break;

    case "care-worker-application":
    include APP_PATH."/views/caregiver-application.php";
    break;

    case "application-backend":
    include APP_PATH."/views/includes/ajax/caregiver_application.php";
    break;

    case "contact-backend":
    include APP_PATH."/views/includes/ajax/contactus.php";
    break;

    case "services-backend":
    include APP_PATH."/views/includes/ajax/services.php";
    break;

    case "more-about-backend":
    include APP_PATH."/views/includes/ajax/more_about.php";
    break;


    case "privacy-policy":
    include APP_PATH."/views/privacy-policy.php";
    break;

    case "team":
    include APP_PATH."/views/team.php";
    break;

    case "policy":
    include APP_PATH."/views/privacy_and_policy.php";
    break;

    case "view-post?".$query_string:
    include APP_PATH."/views/view-post.php";
    break;

    case "categories":
    include APP_PATH."/views/categories.php";
    break;

    case "view-project?".$query_string:
    include APP_PATH."/views/view-project.php";
    break;
    //
    // case "signup":
    // include APP_PATH."/views/signup.php";
    // break;
    //
    // case "forget-password":
    // include APP_PATH."/views/forget-password.php";
    // break;

    case 'privacy-and-policy':
    include APP_PATH."/views/privacy_and_policy.php";
    break;

    case "confirmRecovery":
    include APP_PATH."/views/confirm_recovery.php";
    break;

    case "shareCampaign?".$query_string:
    include APP_PATH."/views/shareCampaign.php";
    break;

    // case "verify?".$query_string:
    // include APP_PATH."/views/shareCampaign.php";
    // break;
    //
    // case "login?".$query_string:
    // include APP_PATH."/views/login.php";
    // break;
    //
    // case "signup?".$query_string:
    // include APP_PATH."/views/signup.php";
    // break;

    // case "login":
    // include APP_PATH."/views/login.php";
    // break;
    //
    // case "signup":
    // include APP_PATH."/views/signup.php";
    // break;

    case "logout":
    include APP_PATH."/auth/logout.php";
    break;

    case 'dashboard':
    include APP_PATH."/views/dashboard.php";
    break;

    case "404":
    include APP_PATH."/views/404.php";
    break;

    case "myBusinesses":
    include APP_PATH."/views/myBusinesses.php";
    break;

    case "listing?".$query_string:
    include APP_PATH."/views/listing.php";
    break;

    case "crm?".$query_string:
    include APP_PATH."/views/crm.php";
    break;

    case "create-facebook-business":
    include APP_PATH."/views/create-facebook-business.php";
    break;

    case "get-facebook-businesses":
    include APP_PATH."/views/get-facebook-businesses.php";
    break;

    case 'timesheet':
    include APP_PATH."/views/timebook.php";
    break;

    case "tmpDemo":
    include APP_PATH."/views/tmpDemo.php";
    break;
    case "verify?token=$token":
    include APP_PATH."/auth/verify_registration.php";
    break;

    case "forgotPassword":
    include APP_PATH."/auth/forgot_password.php";
    break;

    case "forgotPassword2":
    include APP_PATH."/auth/forgot_password2.php";
    break;

    case "confirmRecovery":
    include APP_PATH."/auth/confirm_recovery.php";
    break;
    case "confirmRecovery":
    include APP_PATH."/auth/confirm_recovery.php";
    break;

    case "login":
    include APP_PATH."/auth/login.php";
    break;
    case "signup":
    include APP_PATH."/auth/signup.php";
    break;
    case "secure":
    include APP_PATH."/auth/secure.php";
    break;
    case "secure?".$query_string:
    include APP_PATH."/auth/secure.php";
    break;
    case "login?".$query_string:
    include APP_PATH."/auth/login.php";
    break;
    case "signup?".$query_string:
    include APP_PATH."/auth/signup.php";
    break;

    case "confirm?token=$token":
    include APP_PATH."/auth/confirm.php";
    break;

    case "changePassword":
    include APP_PATH."/auth/change_password.php";
    break;

 

  }

}










 ?>
