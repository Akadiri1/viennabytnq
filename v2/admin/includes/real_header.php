<?php

$totalPrySchools = totalPryCount($conn,'schools');
$totalSchools = totalCount($conn,'schools');
$totalHospitals = totalCount($conn,'hospital');
$totalForum = totalCategoryCount($conn,'topic','visibility','show');

$all = $totalHospitals + $totalSchools;
 ?>

<!DOCTYPE html>
<html data-ng-app="site_stats_display" xml:lang="en"
version="XHTML+RDFa 1.0" dir="ltr"
  xmlns:fb="http://ogp.me/ns/fb#"
  xmlns:og="http://ogp.me/ns#"
  xmlns:article="http://ogp.me/ns/article#"
  xmlns:book="http://ogp.me/ns/book#"
  xmlns:profile="http://ogp.me/ns/profile#"
  xmlns:video="http://ogp.me/ns/video#">


<!--  Mon, 01 Jul 2019 00:35:46 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=utf-8" /><!-- /Added by HTTrack -->
<head>
    <link rel="manifest" href="/manifest.json">
    <!-- <meta name="apple-itunes-app" content="app-id=1423088445"> -->
    <!--[if IE]><![endif]-->
<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<![endif]--><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="/sites/all/themes/Mckodev/Mckodevlogo.ico" type="image/vnd.microsoft.icon" />
<?php if(isset($login_page)){
  include 'seo/login_meta.php';
 } ?>
<?php
$site_name = "Mckodev";
$site_email = "Mckodevdashboard@gmail.com";
$description = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
$metakeys = "Asset Map, Mckodev, Government,Nigeria, State, Citizen Platform, Technology, Growth, Development, Citizen Engagement, Governance";



$logo_directory = "Mckodevlogo.png";
$fbid = "661846110999021";
if(isset($page_name)){
if($page_name == "home"){
  include 'seo/home_meta.php';
}elseif ($page_name == "polls") {
  include 'seo/polls_meta.php';
}elseif ($page_name == "forum") {
  include 'seo/forum_meta.php';
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
<!-- <meta name="description" content="Mckodev asset dashboard " />
<meta name="keywords" content="Asset Map, Mckodev, Government,Nigeria, State, Citizen Platform, Technology, Growth, Development, Citizen Engagement, Governance" />
<meta name="generator" content="Mckodev" />
<link rel="canonical" href="/index.html" />
<link rel="shortlink" href="/index.html" />
<meta property="fb:app_id" content="1463671287222985" />
<meta property="og:url" content="https://Mckodev/" />
<meta property="og:site_name" content="Mckodev State Govenrnment" />
<meta property="og:type" content="article" />
<meta property="og:title" content="Mckodev Asset dashboard " />
<meta property="og:description" content="Mckodev Asset dashboard " />
<meta property="og:image" content="/sites/default/files/master_image/mygov_fb_banner.jpg" />
<meta property="og:image:secure_url" content="/Mckodevlogo.png" />
<meta property="og:image:type" content="image/jpeg" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="627" />
<meta name="twitter:site" content="@Mckodev" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site:id" content="@Mckodev" />
<meta name="twitter:creator" content="@Mckodev" />
<meta name="twitter:url" content="https://Mckodev/" />
<meta name="twitter:title" content="Mckodev asset dashboard " />
<meta name="twitter:description" content="Mckodev asset dashboard " />
<meta name="twitter:image:src" content="/Mckodevlogo.png" />
<meta name="twitter:image:width" content="232" />
<meta name="twitter:image:height" content="128" /> -->
  <!-- <script type="text/JavaScript" src="/https://www.Mckodev State Govenrnment/threeyears.js"></script> -->




    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="MobileOptimized" content="width" />
    <meta name="HandheldFriendly" content="true" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mckodev Asset Map</title>
  <!--[if lte IE 9]>
	<script src="/sites/all/themes/Mckodev/css/ie9-and-lower.css"></script>
  <![endif]-->



  <script type="text/javascript" src="/map/viewer.js"></script>
  <link rel="stylesheet" href="/da/assets/fonts/fontawesome/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="/da/assets/fonts/themify/themify.css">
  <!-- animation css -->
  <link rel="stylesheet" href="/da/assets/plugins/animation/css/animate.min.css">
  <!-- prism css -->
  <link rel="stylesheet" href="/da/assets/plugins/prism/css/prism.min.css">
  <!-- vendor css -->
  <link rel="stylesheet" href="/da/assets/css/style.css">


  <!-- Modal window -->
  <link rel="stylesheet" href="/da/assets/plugins/modal-window-effects/css/md-modal.css">
  <!-- Light Box -->
  <link rel="stylesheet" href="/da/assets/plugins/ekko-lightbox/css/ekko-lightbox.min.css">
  <link rel="stylesheet" href="/da/assets/plugins/lightbox2-master/css/lightbox.min.css">

  <style type="text/css" media="all">
@import url("/modules/system/system.base3918.css?ptygmd");
@import url("/modules/system/system.menus3918.css?ptygmd");
@import url("/modules/system/system.messages3918.css?ptygmd");
@import url("/modules/system/system.theme3918.css?ptygmd");
</style>
<style type="text/css" media="all">
@import url("/profiles/panopoly/modules/contrib/jquery_update/replace/ui/themes/base/minified/jquery.ui.core.min3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-jquery-ui-theme3918.css?ptygmd");
@import url("/profiles/panopoly/modules/contrib/jquery_update/replace/ui/themes/base/minified/jquery.ui.accordion.min3918.css?ptygmd");
</style>
<style type="text/css" media="all">
@import url("/sites/all/modules/contrib/ldap/ldap_user/ldap_user3918.css?ptygmd");
@import url("/sites/all/modules/custom/activities_logs/activities_logs3918.css?ptygmd");
@import url("/modules/comment/comment3918.css?ptygmd");
@import url("/profiles/panopoly/modules/contrib/date/date_api/date3918.css?ptygmd");
@import url("/profiles/panopoly/modules/contrib/date/date_popup/themes/datepicker.1.73918.css?ptygmd");
@import url("/modules/field/theme/field3918.css?ptygmd");
@import url("/sites/all/modules/contrib/field_hidden/field_hidden3918.css?ptygmd");
@import url("/modules/node/node3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_admin/panopoly-admin3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-fonts3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-dropbutton3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_magic/css/panopoly-magic3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_magic/css/panopoly-modal3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_theme/css/panopoly-featured3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_theme/css/panopoly-accordian3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_widgets/panopoly-widgets3918.css?ptygmd");
@import url("/profiles/panopoly/modules/panopoly/panopoly_wysiwyg/panopoly-wysiwyg3918.css?ptygmd");
@import url("/modules/poll/poll3918.css?ptygmd");
@import url("/modules/user/user3918.css?ptygmd");
@import url("/sites/all/modules/contrib/extlink/extlink3918.css?ptygmd");
@import url("/profiles/panopoly/modules/contrib/views/css/views3918.css?ptygmd");
@import url("/profiles/panopoly/modules/contrib/caption_filter/caption-filter3918.css?ptygmd");
</style>
<style type="text/css" media="all">
@import url("/profiles/panopoly/modules/contrib/ctools/css/ctools3918.css?ptygmd");
@import url("/sites/all/modules/contrib/ldap/ldap_servers/ldap_servers.admin3918.css?ptygmd");
@import url("/sites/all/modules/contrib/lightbox2/css/lightbox3918.css?ptygmd");
@import url("/sites/all/modules/contrib/likedislike/templates/likedislike3918.css?ptygmd");
@import url("/sites/all/modules/contrib/messageclose/css/messageclose3918.css?ptygmd");
@import url("/profiles/panopoly/modules/contrib/panels/css/panels3918.css?ptygmd");
@import url("/sites/all/modules/contrib/flexslider/assets/css/flexslider_img3918.css?ptygmd");
@import url("/sites/all/modules/custom/common_utils/css/dark3918.css?ptygmd");
@import url("/modules/locale/locale3918.css?ptygmd");
@import url("/sites/all/modules/contrib/text_resize/text_resize3918.css?ptygmd");
</style>
<style type="text/css" media="all">
@import url("/sites/all/themes/Mckodev/css/front_style3918.css?ptygmd");
@import url("/sites/all/themes/Mckodev/css/style_responsive3918.css?ptygmd");
@import url("/sites/all/themes/Mckodev/css/skeleton3918.css?ptygmd");
@import url("/sites/all/themes/Mckodev/css/jquery.mCustomScrollbar.min3918.css?ptygmd");
</style>
  <link rel="stylesheet" href="/da/assets/fonts/fontawesome/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="/dc/assets/css/font-awesome.min.css" type="text/css">
<style media="screen">
li span{
  z-index:5000;
}
.cont {
width: 93%;
max-width: 250px;
min-width: 250px;
text-align: center;
margin: 4% auto;
padding: 10px;
background: #111;
color: #EEE;
border-radius: 5px;
border: thin solid #444;
overflow: hidden;
}
div.stars {
width: 100%;
display: inline-block;
}
 input.star { display: none; }
 label.star {
 float: right;
 padding: 10px;
 font-size: 25px;
 color: #444;
 transition: all .2s;
 }
 input.star:checked ~ label.star:before {
 content: '\f005';
 color: #FD4;
 transition: all .25s;
 }
 input.star-5:checked ~ label.star:before {
 color: #FE7;
 text-shadow: 0 0 20px #952;
 }
         input.star-1:checked ~ label.star:before { color: #F62; }
  label.star:hover { transform: rotate(-15deg) scale(1.3); }



  label.star:before {
  content: '\f006';
  font-family: FontAwesome;
  }
</style>
<?php if(isset($forum_page)){ ?>
     <link href="/frm/css/custom.css" rel="stylesheet">

     <style media="screen">
       .content{
         background-color:none;
       }
     </style>

<?php } ?>

<style media="screen">

#qt:hover{
  background-color: #F4F4F4;
}
/* width */
::-webkit-scrollbar {
width: 10px;
}

/* Track */
::-webkit-scrollbar-track {
background: #f1f1f1;
}

/* Handle */
::-webkit-scrollbar-thumb {
background: #888;
}

/* Handle on hover */
::-webkit-scrollbar-thumb:hover {
background: #2C498D;
}
</style>
<!--[if lte IE 8]>
<link type="text/css" rel="stylesheet" href="//www.Mckodev State Govenrnment/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-fonts-ie-open-sans.css?ptygmd" media="all" />
<![endif]-->

<!--[if lte IE 8]>
<link type="text/css" rel="stylesheet" href="//www.Mckodev State Govenrnment/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-fonts-ie-open-sans-bold.css?ptygmd" media="all" />
<![endif]-->

<!--[if lte IE 8]>
<link type="text/css" rel="stylesheet" href="//www.Mckodev State Govenrnment/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-fonts-ie-open-sans-italic.css?ptygmd" media="all" />
<![endif]-->

<!--[if lte IE 8]>
<link type="text/css" rel="stylesheet" href="//www.Mckodev State Govenrnment/profiles/panopoly/modules/panopoly/panopoly_core/css/panopoly-fonts-ie-open-sans-bold-italic.css?ptygmd" media="all" />
<![endif]-->
<style type="text/css" media="all">
@import url("/profiles/panopoly/modules/panopoly/panopoly_images/panopoly-images3918.css?ptygmd");
</style>
  	<noscript>
		<style type="text/css">
		.noscriptmsg{background:#2e4b90;color:#fff;}
		.noscriptmsg{text-align:center;}
        .head_right{display:none;}
		.overview-video{display:none;}
		.header-nav{display:block !important;float:right;background:#2e4b90;}
		</style>
	</noscript>
  <!--[if (gte IE 6)&(lte IE 8)]>
    <script src="/sites/all/themes/Mckodev/js/selectivizr-min.js"></script>
  <![endif]-->
  <!--[if lt IE 9]>
    <script src="/sites/all/themes/Mckodev/js/html5-respond.js"></script>
  <![endif]-->

<link type="text/css" rel="stylesheet" media="print" href="/sites/all/themes/Mckodev/css/print.css">
<style media="screen">
.modal-backdrop{
  z-index:3000 ;
}
.modal{
  z-index:4000 ;
}
</style>
</head>
<?php if (isset($map)): ?>
  <body class="html front not-logged-in one-sidebar sidebar-first page-homepage ten columns region-content i18n-en featured footer-columns" onload="viewer.load('viewerDiv', '/map/untitled.xml', '/map/untitled.offline.xml.js', '', {x:0, y:0, zoom:40, controls:'none'})"  >
<?php else: ?>
  <body  class="html front not-logged-in one-sidebar sidebar-first page-homepage ten columns region-content i18n-en featured footer-columns" >
<?php endif; ?>
<noscript>
    <div class="noscriptmsg">
      You don't have javascript enabled. Please Enabled javascript for better performance.
    </div>
</noscript>
<?php if(isset($login_page)){

include 'seo/login_script.php';

}
  ?>





<div class = "site-wrapper">
<div id="fb-root"></div>

    <!--<script type="text/javascript" src="/https://www.Mckodev State Govenrnment/overlay/overlay.js"></script>-->
<script type="text/javascript" src="/overlay/overlay.js"></script>
<div class="top_wrapper" style="z-index:1000">
<section class="wrapper section-top-wrapper">
  <div class="container container-top">
    <div style="font-size: 13px;text-transform: uppercase;color: #686868;display: inline-block;padding: 10px 0 10px 40px;" class=""> <a href="#" title="GOVERNMENT OF Mckodev" target="_blank">Mckodev ASSET DASHBOARD</a> </div>

    <div class="top-right clearfix">
        <div id="sizer" class="head_user">
          <!-- <a class="skip_content" href="#section1" title="Skip to main content" alt="Skip to main content" ><strong>Skip to main content</strong></a> -->
                    <div class="lang-box">

                                                <div class="user_accessibility">
                            <a href="/javascript:void(0)" class="access_icon"></a>
                            <div class="access-type">
                              <a href="/javascript:;" class="changer" title="Decrease Text" id="text_resize_decrease"><sup>-</sup>A</a> <a href="/javascript:;" class="changer" id="text_resize_reset" title="Reset Text" >A</a> <a href="/javascript:;" class="changer" id="text_resize_increase" title="Increase Text"><sup>+</sup>A</a><div id="text_resize_clear"></div><div class="color-switcher">
<ul class="colorchanger">
<li class="cp_white_to_black">
		<a class="color-dark" title=" White/Black" href="#">White to Black</a>
	</li>
<li class="cp_standard">
		<a class="color-standard" title=" Standard" href="#">Standard</a>
	</li>
</ul>
</div>
                            </div>
                        </div>
                        <div class="site_share">
                            <a href="/javascript:void(0)" class="share_icon">Follow us</a>
                            <div class="share-list">
                              <div class="social-area"><a href="https://twitter.com/Mckodev" target="_blank" title="MyGov Twitter(External Site that opens in a new window)"><i class="twitter-icon"></i></a> </div><div class="social-area"><a href="https://facebook.com/Mckodev/" target="_blank" title="Mckodev Facebook(External Site that opens in a new window)"><i class="fb-icon"></i></a> </div><div class="social-area"><a href="http://youtube.com/Mckodev" target="_blank" title="Mckodev Youtube(External Site that opens in a new window)"><i class="youtube-icon"></i></a> </div><div class="social-area"><a href="https://www.instagram.com/Mckodev" target="_blank" title="Mckodev Instagram(External Site that opens in a new window)"><i class="insta-icon"></i></a> </div>                            </div>
                        </div>

                <?php if (isset($_SESSION['id'])): ?>
                <?php
                $whereAdmin['hash_id'] = $_SESSION['id'];
                $adminInfo = selectContent($conn,'users',$whereAdmin);
                 ?>
                  <div class="region region-header">
<div id="block-common-utils-user-settings-block" class="block block-common-utils">


<div class="content">
<div id="notification_user_menu" class="notification_user" style="overflow: hidden;display:none">
<div class="grid_3 fl alpha">
  <ul>
    <!-- <li>
<a class="capital" title="Edit Profile" href="/user/59361511/edit/main">Edit Profile</a>
    </li> -->
    <!-- <li>
<a class="capital" title="My Activity Points" href="/user/59361511">My Activity Points</a>
    </li> -->
    <!-- <li>
    <a class="capital" title="Notifications" href="/notification/list">Notifications</a>
    </li> -->
    <li class="no-bdr">
  <a class="capital" title="Log Out" href="/logout">Log Out</a>
    </li>
  </ul>
  </div>
  </div>  </div>
</div>
<div id="block-common-utils-user-head-custom-block" class="block block-common-utils">


<div class="content">
<div id="block-views-my-details-block" class="block block-views">
<div class="content">
<div class="view-content">
  <span class="views-field views-field-name"><span class="field-content" title="Mayowa Banji"><?php echo ucwords($adminInfo[0]['firstname']." ".$adminInfo[0]['lastname']); ?></span></span>
  <span class="views-field views-field-field-user-picture">
      <div class="content">
          <img src="/dummy.png" alt="user picture" class="profile-image" height="82">
      </div>
  </span>
</div>
</div>
</div>  </div>
</div>
</div>
                <?php else: ?>
                  <div class="login-reg-block">
<a href="/javascript:void(0)" class="login-reg-icon"></a>
<div class="login-link-wrapper">
<a href="/login?rd=<?php echo base64url_encode($_SERVER['REQUEST_URI']); ?>" class="ac-login">Login</a><a href="/register" class="ac-register">Register</a></div>
</div>
                <?php endif; ?>

                </div>
      </div>
    </div>

  </div>
</section>

<!--/.sectioon-top-wrapper-->

<section class="wrapper section-header-wrapper">
  <div class="container container-header">
    <div class="header-logo">

				<a href="/index"
					title="Home" rel="home">
					<img width="50" height="50" src="/Mckodevlogo.png" alt="Home" title="Home">
				</a>
	</div>
    <div class="header-main-flyout-menu">
        <div class="res_menu">
            <img src="/sites/all/themes/Mckodev/front_assets/images/menu_icon.png" alt="Menu">
        </div>
        <div class="flyout-menu-wrapper">
            <div class="container">
                <div class="nav-header">
                    <span>MENU</span>
                    <div class="nav-menu-close">Close</div>
                </div>
                <div class="menu-container">
                    <div class="header-nav-main menu-row">
                        <span class="nav-main-title">ACTIVITIES </span>
                        <div class="menu-res">
                          <h2 class="element-invisible">Main Menu</h2><ul id="main-menu" class="links clearfix">

<li class="menu-17461"><a href="/assets" title="Online and Onground Tasks" class="menu-main-do">Assets</a></li>
<li class="menu-17481"><a href="/discussion" title="Group-centric and national themes" class="menu-main-discuss">Forum</a></li>
<li class="menu-28821"><a href="/ratings" title="Make your opinion count" class="menu-main-poll-survey">Policy Opinion</a></li>
<li class="menu-50251"><a href="/blogs" title="Updates, Experiences and MyGov Impact" class="menu-main-blog">Blog</a></li>
<!-- <li class="menu-17501 last"><a href="home.html" title="Dialogue with decision makers" class="menu-main-talk">Talk</a></li> -->
</ul>                        </div>
                    </div>
                    <div class="header-nav-sub-domain menu-row">
                        <span class="sub-domain-title">ASSETS </span>
                        <div class="menu-res">
                            <ul class="sub-sites-header">

        <li><a title="Name" alt="Name" href="#" target="_blank">Health Care</a></li>
        <li><a title="Name" alt="Name" href="#" target="_blank">Education</a></li>
        <li><a title="Name" alt="Name" href="#" target="_blank">Agriculture</a></li>
        <li><a title="Name" alt="Name" href="#" target="_blank">Commerce</a></li>

      </ul>                        </div>
                    </div>

                    <div class="header-nav-states menu-row">
                        <span class="nav-states-title">LGA </span>
                        <div class="menu-res">
                          <div class="mygov-states">LGA <div class="states"></div></div><div class="mygov-states-inner">
                    <ul>
                        <li>
                          <a href = "#" target = "_blank">
                            <span>Ado Mckodev</span>
                          </a>
                        </li>
                        <li>
                          <a href = "#" title = "Name" target = "_blank">
                            <span>Ikere Mckodev</span>
                          </a>
                        </li>
                        <li>
                          <a href = "#" title = "Name" target = "_blank">
                            <span>Ikole Mckodev</span>
                          </a>
                        </li>

                    </ul>
    </div>                        </div>
                    </div>
                    <!-- <div class="header-nav-app menu-row">
                        <span class="nav-app-title">MOBILE APP </span>
                        <div class="menu-res">
                          <div class="mygov_apps_qr"><a href="/mygovapp.html"> <img src="/sites/all/themes/Mckodev/images/qr-code.png"></a></div><div class="mygov-apps-inner"><ul><li><a title="MyGov Android App" href="https://play.google.com/store/apps/details?id=in.mygov.mobile&amp;hl=en" title="MyGov Android App" alt="MyGov Android App" target="_blank"><img src="/sites/all/themes/Mckodev/images/google-play.png"></a></li>
    <li><a title="MyGov iOS App" href="https://itunes.apple.com/in/app/mygov-india-म-र-सरक-र/id1423088445?mt=8" title="MyGov iOS App" alt="MyGov iOS App" target="_blank"><img src="/sites/all/themes/Mckodev/images/app-store.png"></a></li></ul><span class="hint-text">Click to Download</span></div><div class="mygov_app_hint-text">Scan to <span>Download</span> MyGov App for <span>iOS</span> and <span>Android</span></div>                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
    <div class="header-search-block">
        <div class='search_mygov_box'><div class='the_box'><input class='search_title_box' placeholder='Type search keyword' id='search_title' title='Search Title' ng-model='search_title'><a id="search_link_btn" href="#">Search</a></div><span title='Global Search by Title' class='search_toggle'></span></div>    </div>
    <div class="header-push-notification">
          <span title="Notification">Notification</span><div class="notification-container"><ul class="push_notification_list">
            <li class="push_row"><a href="#"><div class="push_container"><div class="push_image"><img src="/rest/s3fs-public/mygov_156172385852221771.jpg"></div><div class="push_title">TRAI invites your inputs on enhancing communication corridor for public security in Indian Railways!</div></div></a></li>

        </ul></div>    </div>
    <!--<div class="header-message">
    <img src="/front_assets/images/sign.png" alt="" title="">
    </div>-->

  </div>
</section>
</div>

<div class="sticky-menu-main" style="z-index:2000">
        <ul class="sticky-login">
        <li><a href="/login?rd=<?php echo base64url_encode($_SERVER['REQUEST_URI']); ?>">Login</a></li>
    </ul>
      <h2 class="element-invisible">Sticky Menu</h2><ul id="main-menu" class="links clearfix">
        <!-- <li class="menu-50241 first"><a href="/groups/index.html" title="Topics of your interest" class="menu-main-group">Groups</a></li> -->
<li class="menu-17461"><a href="/assets" title="Online and Onground Tasks" class="menu-main-do">Assets</a></li>
<li class="menu-17481"><a href="/discussion" title="Group-centric and national themes" class="menu-main-discuss">forum</a></li>
<li class="menu-28821"><a href="/ratings" title="Make your opinion count" class="menu-main-poll-survey">Policy Opinion</a></li>
<li class="menu-50251"><a href="/blogs" title="Updates, Experiences and Government Impact" class="menu-main-blog">Blog</a></li>
<!-- <li class="menu-17501 last"><a href="home.html" title="Dialogue with decision makers" class="menu-main-talk">Talk</a></li> -->
</ul></div>
<!--/.section-header-wrapper-->
<?php if (isset($home)): ?>
  <div class="slider-wrapper">
  		<div id="featured">
  	    <div class="region region-featured">
      <div id="block-views-homepage-slider-block" class="block block-views">


    <div class="content">
      <div class="view view-homepage-slider view-id-homepage_slider view-display-id-block view-dom-id-0f5b7c51ce7159e6462dcdc1bae8d8f9">



        <div class="view-content">
        <div  id="flexslider-1" class="flexslider">
    <ul class="slides">



  <li>
    <div class="views-field views-field-field-banner-image">        <div class="field-content"><p><img src="/222.jpg" id="_qbPquqmcc0B1LvrS" onclick="javascript:window.open('#')" style="cursor:pointer" onload="javascript:(function(){if(typeof _done == 'undefined' || !_done){this.setAttribute('src', this.getAttribute('src')+'?'+Math.floor((Math.random() * 100) + 1)); _done=true;}}).call(this)" /></p>
  </div>  </div>
    <div class="home-slider-caption">        <div class="home-slider-text"></div>  </div></li>
  <li>
    <div class="views-field views-field-field-banner-image">        <div class="field-content"><p><img src="banner2.png" id="_qbPquqmcc0B1LvrS" onclick="javascript:window.open('#')" style="cursor:pointer" onload="javascript:(function(){if(typeof _done == 'undefined' || !_done){this.setAttribute('src', this.getAttribute('src')+'?'+Math.floor((Math.random() * 100) + 1)); _done=true;}}).call(this)" /></p>
  </div>  </div>
    <div class="home-slider-caption">        <div class="home-slider-text"></div>  </div></li>
  <li>
    <div class="views-field views-field-field-banner-image">        <div class="field-content"><p><img src="333.jpg" id="_qbPquqmcc0B1LvrS" onclick="javascript:window.open('#')" style="cursor:pointer" onload="javascript:(function(){if(typeof _done == 'undefined' || !_done){this.setAttribute('src', this.getAttribute('src')+'?'+Math.floor((Math.random() * 100) + 1)); _done=true;}}).call(this)" /></p>
  </div>  </div>
    <div class="home-slider-caption">        <div class="home-slider-text"></div>  </div></li>
  <li>
    <div class="views-field views-field-field-banner-image">        <div class="field-content"><p><img src="banner1.jpeg" id="_qbPquqmcc0B1LvrS" onclick="javascript:window.open('#')" style="cursor:pointer" onload="javascript:(function(){if(typeof _done == 'undefined' || !_done){this.setAttribute('src', this.getAttribute('src')+'?'+Math.floor((Math.random() * 100) + 1)); _done=true;}}).call(this)" /></p>
  </div>  </div>
    <div class="home-slider-caption">        <div class="home-slider-text"></div>  </div></li>
  <li>
    <div class="views-field views-field-field-banner-image">        <div class="field-content"><p><img src="banner3.jpg" id="_qbPquqmcc0B1LvrS" onclick="javascript:window.open('#')" style="cursor:pointer" onload="javascript:(function(){if(typeof _done == 'undefined' || !_done){this.setAttribute('src', this.getAttribute('src')+'?'+Math.floor((Math.random() * 100) + 1)); _done=true;}}).call(this)" /></p>
  </div>  </div>
    <div class="home-slider-caption">        <div class="home-slider-text"></div>  </div></li>

  </ul></div>
      </div>






  </div>  </div>
  </div>
    </div>
  	</div>
  	</div>
<?php endif; ?>


<div class="main-activities-status">
    <div class="container">
        <div class="region region-footer-firstcolumn">
    <nav id="block-menu-menu-content-menu"
	class="block block-menu" 	role="navigation">

      <div class="content_menu_title">
  <h2 >Features</h2>
      <div  class="menu-desc">
    	<p>Be an active partner in development of Mckodev State.
					 Participate in Forum, Policy Opinion, Assets Reports. Contribute Now!</p>
    </div></div>
        <div class="content" >
    <ul class="menu clearfix">
      <li class="first leaf groups" data-menu-parent="menu-content-menu-1"><a href="/index" title="Homepage" accesskey="g" class="menu-content-menu-1">Home</a><span>Homepage</span></li>
<li class="leaf home do" data-menu-parent="menu-content-menu-1"><span title='/assets' class='count_span do'><?php echo $all; ?></span><a href="/assets" title="State Assets and Current State Reports" accesskey="d" class="menu-content-menu-1">Assets</a><span>State Assets and Current State Reports</span></li>
<li class="leaf home discuss" data-menu-parent="menu-content-menu-1"><span title='Discussion around Mckodev State related topics' class='count_span discuss'><?php echo $totalForum; ?></span><a href="/discussion" title="Group-centric and national themes" class="menu-content-menu-1">Forum</a><span>Discussion around Mckodev State related topics</span></li>
<li class="leaf home poll" data-menu-parent="menu-content-menu-1"><a href="/ratings" title="Make your opinion count,Rate New Government Policies" class="menu-content-menu-1">Policy Poll</a><span>Rate New Government Policies</span></li>
<li class="leaf blog home blog" data-menu-parent="menu-content-menu-1"><a href="/blogs" title="Updates, Experiences and Mckodev State Impact" class="blog menu-content-menu-1" target="_blank">Blog</a><span>Updates, Experiences and Mckodev State Impact</span></li>
<li class="last leaf home talk" data-menu-parent="menu-content-menu-1"><a href="#" title="Report Asset Status" class="menu-content-menu-1">Report</a><span>Report Asset Status</span></li>
</ul>  </div>
</nav>
  </div>
    </div>
</div>
