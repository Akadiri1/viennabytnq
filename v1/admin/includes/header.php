<?php if(!isset($_SESSION['admin_id'])){
  header("Location:/admin-login");
  die;
}

$whereAdmin['hash_id'] = $_SESSION['admin_id'];
$adminDetails = selectContent($conn,"admin",$whereAdmin);


$whereTable['TABLE_TYPE'] = "BASE TABLE";
$whereTable['TABLE_SCHEMA'] = "mckodevc_demo";
$table_name['table_name'] = "table_name";
$tables = selectTableContent($conn,'information_schema.tables',$table_name,$whereTable);
// die(var_dump($tables));

if($adminDetails[0]['user_status'] == 2){
  header("Location:/admin-login?err=".base64url_encode("Your Account Has Been Suspended"));
}

// die($adminDetails[0]['level']);
if(!in_array($adminDetails[0]['level'],$level_check)){
  unset($_SESSION['admin_id']);
  header("Location:/admin-login?err=".base64url_encode("<p>You were logged out because your account cannot visit the page you visited</p>"));
}

 ?>

<!DOCTYPE html>
<html lang="en">


<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
<head>
  <title>Admin Data Management Console</title>
  <!-- HTML5 Shim and Respond.js IE10 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 10]>
  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
  <!-- Meta -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="description" content="Mckodev Admin Data Management Console" />
  <meta name="keywords" content="">


  <!-- Favicon icon -->
  <link rel="icon" href="/logo.png" type="image/x-icon">
  <!-- fontawesome icon -->
  <link rel="stylesheet" href="/da/assets/fonts/fontawesome/css/fontawesome-all.min.css">
  <link rel="stylesheet" href="/da/assets/fonts/material/css/materialdesignicons.min.css">
  <!-- animation css -->
  <link rel="stylesheet" href="/da/assets/plugins/animation/css/animate.min.css">
  <!-- prism css -->
  <link rel="stylesheet" href="/da/assets/plugins/prism/css/prism.min.css">
  <!-- vendor css -->
  <link rel="stylesheet" href="/da/assets/css/style.css">

<!-- table -->
    <link rel="stylesheet" href="/da/assets/plugins/data-tables/css/datatables.min.css">
  <!-- Modal window -->
  <link rel="stylesheet" href="/da/assets/plugins/modal-window-effects/css/md-modal.css">
<!-- Light Box -->
<link rel="stylesheet" href="/da/assets/plugins/ekko-lightbox/css/ekko-lightbox.min.css">
<link rel="stylesheet" href="/da/assets/plugins/lightbox2-master/css/lightbox.min.css">


  <!-- <script type="text/javascript" src="/map/viewer.js"></script> -->
  <style media="screen">
    .modal-backdrop{
    z-index:3000 ;
    }
    .modal{
    z-index:4000 ;
    }
  </style>
  <script src="/ajax/ajax.js"></script>


</head>

<body class="">
	<!-- [ Pre-loader ] start -->
	<div class="loader-bg">
		<div class="loader-track">
			<div class="loader-fill"></div>
		</div>
	</div>
	<!-- [ Pre-loader ] End -->
	<!-- [ navigation menu ] start -->
	<nav class="navbar menupos-fixed menu-light brand-red icon-colored">
		<div class="navbar-wrapper ">
			<div class="navbar-brand header-logo" style="/*background-color:white;*/">
				<a href="/admin" class="b-brand">
					<!-- <div class="b-bg">
						<i class="fas fa-bolt"></i>
					</div>
					<span class="b-title">Flash Able</span> -->
					<!-- <img src="/logo.png" width="50" height="50" alt="" class="logo images"> -->
					<!-- <img src="/logo.gif" width="50" height="50" alt="" class="logo-thumb images"> -->
          <span class="text-white logo images">ADMC</span>
          <!-- <span class="text-white logo-thumb images">ADMC</span> -->

				</a>
				<a class="mobile-menu" id="mobile-collapse" href="#!"><span></span></a>
			</div>
			<div class="navbar-content scroll-div   " >



				<ul class="nav navbar ">
					<li class="nav-item menu-caption" style="padding: 25px 15px 15px 10px;">
            	<img src="/logo.png" width="50" height="50" alt="" class="logo images">
						<!-- <label>Navigation</label> -->
					</li>
        <li data-username="sample page" class="nav-item"><a href="/admin" class="nav-link"><span class="micon"><i class="feather icon-home"></i></span><span class="mtext">Dashboard</span></a></li>

          <li class="nav-item menu-caption">
            <label>Content Management</label>
          </li>
          <?php foreach ($tables as $key => $value): ?>
            <?php
              $remains = explode("_",$value['table_name']);

             if ($remains[0] == "panel"): ?>
             <?php
             array_shift($remains);

               $remains = ucwords(implode(" ",$remains)); ?>
             <li data-username="<?php echo $remains ?>" class="nav-item hasmenu">
               <a href="#!" class="nav-link"><span class="micon"><i class="feather icon-menu"></i></span><span class="mtext"><?php echo $remains ?></span></a>
               <ul class="submenu">

                 <li class=""><a href="/add/<?php echo str_replace(" ","_",strtolower($remains)); ?>" class="" >Add <?php echo $remains ?></a></li>
                 <li class=""><a href="/manage/<?php echo str_replace(" ","_",strtolower($remains)); ?>" class="" >Manage <?php echo $remains ?></a></li>
               </ul>
             </li>


            <?php endif; ?>

          <?php endforeach; ?>
          <li class="nav-item menu-caption">
            <label>Category Management</label>
          </li>

          <?php foreach ($tables as $key => $value): ?>
            <?php
              $remains = explode("_",$value['table_name']);

             if ($remains[0] == "selection"): ?>
             <?php
             array_shift($remains);

               $remains = ucwords(implode(" ",$remains)); ?>
             <li data-username="<?php echo $remains ?>" class="nav-item hasmenu">
               <a href="#!" class="nav-link"><span class="micon"><i class="feather icon-bookmark"></i></span><span class="mtext"><?php echo $remains ?></span></a>
               <ul class="submenu">

                 <li class=""><a href="/create/<?php echo str_replace(" ","_",strtolower($remains)); ?>" class="" >Add <?php echo $remains ?></a></li>
                 <li class=""><a href="/manage/<?php echo str_replace(" ","_",strtolower($remains)); ?>" class="" >Manage <?php echo $remains ?></a></li>
               </ul>
             </li>


            <?php endif; ?>

          <?php endforeach; ?>





					<li class="nav-item menu-caption">
						<label>Data Management</label>
					</li>
          <?php foreach ($tables as $key => $value): ?>
            <?php
              $remains = explode("_",$value['table_name']);

             if ($remains[0] == "read"): ?>
             <?php
             array_shift($remains);

               $remains = ucwords(implode(" ",$remains)); ?>
               <li data-username="<?php echo $remains ?>">
                 <a href="/read/<?php echo str_replace(" ","_",strtolower($remains)); ?>" class="nav-link"><span class="micon"><i class="feather icon-box"></i></span><span class="mtext"><?php echo $remains ?></span></a>

               </li>


            <?php endif; ?>

          <?php endforeach; ?>

					<li class="nav-item menu-caption">
						<label>Users Management</label>
					</li>
          <li data-username="widget statistic data chart" class="nav-item hasmenu">
            <a href="#!" class="nav-link"><span class="micon"><i class="feather icon-users"></i></span><span class="mtext">Users</span></a>
            <ul class="submenu">
              <li class=""><a href="widget-statistic.html" class="">Statistic</a></li>
              <li class=""><a href="widget-data.html" class="">Data</a></li>
              <li class=""><a href="widget-chart.html" class="">Chart</a></li>
            </ul>
          </li>



				</ul>

				<!-- <div class="card text-center">
					<div class="card-block">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						<i class="feather icon-sunset f-40"></i>
						<h6 class="mt-3">Upgrade to pro</h6>
						<p>upgrade for get full themes and 30min support</p>
						<a href="#!"  class="btn btn-gradient-primary btn-sm text-white m-0">Upgrade</a>
					</div>
				</div> -->



			</div>

		</div>
	</nav>
	<!-- [ navigation menu ] end -->



	<!-- [ Header ] start -->
	<header class="navbar header navbar-expand-lg navbar-light headerpos-fixed ">

			<div class="m-header" style="background: linear-gradient(45deg, #b31d1d, #ff0000)">
				<a class="mobile-menu" id="mobile-collapse1" href="#!"><span></span></a>
				<a href="/admin" class="b-brand">
					<!-- <div class="b-bg">
						<i class="fas fa-bolt"></i>
					</div>
					<span class="b-title">Flash Able</span> -->
					<!-- <img src="/logo.png" width="50" height="50" alt="" class=""> -->
          <h6 class="text-white">ADMIN DATA MANAGEMENT CONSOLE</h6>

				</a>
			</div>
			<a class="mobile-menu" id="mobile-header" href="#!">
				<i class="feather icon-more-horizontal"></i>
			</a>
			<div class="collapse navbar-collapse">
				<a href="#!" class="mob-toggler"></a>
				<ul class="navbar-nav mr-auto">
					<li class="nav-item">
						<div class="main-search open">
							<div class="input-group">
								<input type="text" id="m-search" class="form-control" placeholder="Search . . .">
								<a href="#!" class="input-group-append search-close">
									<i class="feather icon-x input-group-text"></i>
								</a>
								<span class="input-group-append search-btn btn btn-primary">
									<i class="feather icon-search input-group-text"></i>
								</span>
							</div>
						</div>
					</li>
				</ul>
				<ul class="navbar-nav ml-auto">
					<li>
						<div class="dropdown">
							<a class="dropdown-toggle" href="#" data-toggle="dropdown"><i class="icon feather icon-bell"></i></a>
							<div class="dropdown-menu dropdown-menu-right notification">
								<div class="noti-head">
									<h6 class="d-inline-block m-b-0">Notifications</h6>
									<div class="float-right">
										<a href="#!" class="m-r-10">mark as read</a>
										<a href="#!">clear all</a>
									</div>
								</div>
								<ul class="noti-body">
									<li class="n-title">
										<p class="m-b-0">NEW</p>
									</li>
									<li class="notification">
										<div class="media">
											<img class="img-radius" src="../assets/images/user/avatar-1.jpg" alt="Generic placeholder image">
											<div class="media-body">
												<p><strong>John Doe</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>5 min</span></p>
												<p>New ticket Added</p>
											</div>
										</div>
									</li>
									<li class="n-title">
										<p class="m-b-0">EARLIER</p>
									</li>
									<li class="notification">
										<div class="media">
											<img class="img-radius" src="../assets/images/user/avatar-2.jpg" alt="Generic placeholder image">
											<div class="media-body">
												<p><strong>Joseph William</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>10 min</span></p>
												<p>Prchace New Theme and make payment</p>
											</div>
										</div>
									</li>
									<li class="notification">
										<div class="media">
											<img class="img-radius" src="../assets/images/user/avatar-3.jpg" alt="Generic placeholder image">
											<div class="media-body">
												<p><strong>Sara Soudein</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>12 min</span></p>
												<p>currently login</p>
											</div>
										</div>
									</li>
									<li class="notification">
										<div class="media">
											<img class="img-radius" src="../assets/images/user/avatar-1.jpg" alt="Generic placeholder image">
											<div class="media-body">
												<p><strong>Joseph William</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>30 min</span></p>
												<p>Prchace New Theme and make payment</p>
											</div>
										</div>
									</li>
									<li class="notification">
										<div class="media">
											<img class="img-radius" src="../assets/images/user/avatar-3.jpg" alt="Generic placeholder image">
											<div class="media-body">
												<p><strong>Sara Soudein</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>1 hour</span></p>
												<p>currently login</p>
											</div>
										</div>
									</li>
									<li class="notification">
										<div class="media">
											<img class="img-radius" src="../assets/images/user/avatar-1.jpg" alt="Generic placeholder image">
											<div class="media-body">
												<p><strong>Joseph William</strong><span class="n-time text-muted"><i class="icon feather icon-clock m-r-10"></i>2 hour</span></p>
												<p>Prchace New Theme and make payment</p>
											</div>
										</div>
									</li>
								</ul>
								<div class="noti-footer">
									<a href="#!">show all</a>
								</div>
							</div>
						</div>
					</li>
					<li><a href="#!" class="displayChatbox"><i class="icon feather icon-mail"></i></a></li>
					<li>
						<div class="dropdown drp-user">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<i class="icon feather icon-settings"></i>
							</a>
							<div class="dropdown-menu dropdown-menu-right profile-notification">
								<div class="pro-head">
									<img src="../assets/images/user/avatar-1.jpg" class="img-radius" alt="User-Profile-Image">
									<span>John Doe</span>
									<a href="auth-signin.html" class="dud-logout" title="Logout">
										<i class="feather icon-log-out"></i>
									</a>
								</div>
								<ul class="pro-body">
									<li><a href="#!" class="dropdown-item"><i class="feather icon-settings"></i> Settings</a></li>
									<li><a href="#!" class="dropdown-item"><i class="feather icon-user"></i> Profile</a></li>
									<li><a href="message.html" class="dropdown-item"><i class="feather icon-mail"></i> My Messages</a></li>
									<li><a href="auth-signin.html" class="dropdown-item"><i class="feather icon-lock"></i> Lock Screen</a></li>
								</ul>
							</div>
						</div>
					</li>
				</ul>
			</div>

	</header>
