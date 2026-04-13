<?php
$uri = explode("/",$_SERVER['REQUEST_URI']);


$success = NULL;
if(isset($_GET['success'])){
  $success = $_GET['success'];
}

$wn = NULL;
if(isset($_GET['wn'])){
  $wn = $_GET['wn'];
}
$rd = NULL;
if(isset($_GET['rd'])){
  $rd = $_GET['rd'];
}

$id = NULL;
if(isset($_GET['id'])){
  $id = $_GET['id'];
}
$err = NULL;
if(isset($_GET['err'])){
  $err = $_GET['err'];
}

$data = NULL;
if(isset($_GET['data'])){
  $data = $_GET['data'];
}

$location = NULL;
if(isset($_GET['location'])){
  $location = $_GET['location'];
}
if(count($uri) > 2){

  if($uri[1] == "add"){

    $placeholder = $uri[2];

        switch ($uri[1]."/".$uri[2]){

          case "add/$placeholder":
          include APP_PATH."/v1/admin/admin_add_all.php";
          break;
            // case "project/$placeholder":
            // include APP_PATH."/v1/demo_public_views/details.php";
            // break;
            ///////Public Routes////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
          }
        die;

      }elseif ($uri[1] == "create") {
        $placeholder = $uri[2];

            switch ($uri[1]."/".$uri[2]){

              case "create/$placeholder":
              include APP_PATH."/v1/admin/admin_add_select.php";
              break;
                // case "project/$placeholder":
                // include APP_PATH."/v1/demo_public_views/details.php";
                // break;
                ///////Public Routes////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
              }
            die;
      }elseif ($uri[1] == "read") {
        // code...
            die;
      }elseif ($uri[1] == "manage") {
        $placeholder = $uri[2];

            switch ($uri[1]."/".$uri[2]){

              case "manage/$placeholder":
              include APP_PATH."/v1/admin/manage_all.php";
              break;
                // case "project/$placeholder":
                // include APP_PATH."/v1/demo_public_views/details.php";
                // break;
                ///////Public Routes////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
              }
            die;
      }
    }



switch ($uri[1]) {



  case 'admin-add-category':
  include APP_PATH."/v1/admin/create_category.php";
  break;
  case 'admin-add-blog':
  include APP_PATH."/v1/admin/create_blog.php";
  break;
  case 'admin-add-post':
  include APP_PATH."/v1/admin/create_post.php";
  break;
  case 'admin-add-polls':
  include APP_PATH."/v1/admin/create_polls.php";
  break;
  case 'admin-view-post':
  include APP_PATH."/v1/admin/manage_post.php";
  break;
  case 'admin-view-blog':
  include APP_PATH."/v1/admin/manage_blog.php";
  break;
  case 'admin-view-polls':
  include APP_PATH."/v1/admin/manage_polls.php";
  break;
  case "admin-edit-content?id=$id&data=$data&location=$location":
  include APP_PATH."/v1/admin/edit_content.php";
  break;
  case "admin-edit-hospital?id=$id&data=$data&location=$location":
  include APP_PATH."/v1/admin/edit_hospital.php";
  break;
  case "admin-edit-school?id=$id&data=$data&location=$location":
  include APP_PATH."/v1/admin/edit_school.php";
  break;

  case 'add-hospital':
  include APP_PATH."/v1/admin/add_hospital_asset.php";
  break;

  case 'add-school':
  include APP_PATH."/v1/admin/add_school_asset.php";
  break;

  case 'admin-view-category':
  include APP_PATH."/v1/admin/manage_category.php";
  break;

  case 'admin-view-hospital':
  include APP_PATH."/v1/admin/manage_hospital.php";
  break;

  case 'admin-add-slider':
  include APP_PATH."/v1/admin/create_slider.php";
  break;
  case 'admin-view-slider':
  include APP_PATH."/v1/admin/manage_slider.php";
  break;

  case 'admin-edit-slider':
  include APP_PATH."/v1/admin/manage_slider.php";
  break;

  case 'admin-view-admin':
  include APP_PATH."/v1/admin/manage_admin.php";
  break;

  case 'admin-view-users':
  include APP_PATH."/v1/admin/manage_users.php";
  break;

  case 'admin-view-school':
  include APP_PATH."/v1/admin/manage_school.php";
  break;

  case 'admin-add-topic':
  include APP_PATH."/v1/admin/create_post.php";
  break;
  case "change-image?id=$id&data=$data&location=$location":
  include APP_PATH."/v1/admin/change_image.php";
  break;

  // case 'admin-signup':
  // include APP_PATH."/v1/admin/admin_register.php";
  // break;
  //
  //
  // case "admin-signup?err=$err":
  // include APP_PATH."/v1/admin/admin_register.php";
  // break;
  //
  // case "admin-signup?wn=$wn":
  // include APP_PATH."/v1/admin/admin_register.php";
  // break;
  //
  // case 'admin-logout':
  // include APP_PATH."/v1/admin/admin_logout.php";
  // break;
  //
  // case 'admin-login':
  // include APP_PATH."/v1/admin/admin_login.php";
  // break;
  // case "admin-login?rd=$rd":
  // include APP_PATH."/v1/admin/admin_login.php";
  // break;
  // case "admin":
  // include APP_PATH."/v1/admin/admin_home.php";
  // break;
  // case "admin-login?wn=$wn":
  // include APP_PATH."/v1/admin/admin_login.php";
  // break;
  // case "admin-login?err=$err":
  // include APP_PATH."/v1/admin/admin_login.php";
  // break;
  // case "admin-login?wn=$wn&rd=$rd":
  // include APP_PATH."/v1/admin/admin_login.php";
  // break;
  // case "admin-login?success=$success":
  // include APP_PATH."/v1/admin/admin_login.php";
  // break;





  // default:
  //   // code...
  //   break;
}

 ?>
