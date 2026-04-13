<?php
ob_start();
//session_start();
$level_check = ['MASTER'];
// SELECT table_name FROM information_schema.tables;
// SELECT table_name FROM information_schema.tables WHERE ;
include 'includes/header.php';

// $arr['name'] = "Book";



$where = [];

$content = selectContent($conn,'admin',$where);
?>

<!-- [ Header ] end -->
<div class="container">
  <div class="wrapper">
    <div class="content">
      <div class="content">
        <!-- [ breadcrumb ] start -->

        <!-- [ breadcrumb ] end -->
        <div class="main-body">
          <div class="page-wrapper">
            <!-- page start -->


            <div class="page-header">
              <div class="page-block">
                <div class="row align-items-center">
                  <div class="col-md-12">
                    <div class="page-header-title">
                      <h5>Admin</h5>
                    </div>
                    <ul class="breadcrumb">
                      <li class="breadcrumb-item"><a href="/admin"><i class="feather icon-home"></i></a></li>
                      <li class="breadcrumb-item"><a href="#!">View Site Admin</a></li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>


            <div class="row">
              <!-- Zero config table start -->
              <div class="col-sm-12">
                <div class="card">
                  <div class="card-header">
                    <h5>View Admin</h5>
                  </div>

                  <div class="card-body">
                    <div class="dt-responsive table-responsive">
                      <table id="simpletable" class="table table-striped table-bordered nowrap">
                        <thead>
                          <tr>
                            <th>Info</th>
                            <th>Set Level</th>
                            <th>suspend/verify</th>
                            <th>Delete</th>
                            <th>Date Registered</th>
                          </tr>
                        </thead>
                        <tbody>


                          <?php foreach ($content as $key => $value) {
                            if($value['level'] !== "MASTER"){

                            ?>
                            <tr>
                              <td>
                                Name: <?php echo $value['firstname']." ".$value['lastname'] ?> <br>
                                Email: <?php echo $value['email']?> <br>

                               </td>
                              <td> <p><?php echo "Current Level: ". $value['level']; ?></p>
                                <br>
                                <a href="/updateContent.php?id=<?php echo $value['id'] ?>&level=3&data=admin"><button type="button" class="btn btn-primary btn-sm"><i class="feather icon-user"></i>3</button></a>
                                <a href="/updateContent.php?id=<?php echo $value['id'] ?>&level=2&data=admin"><button type="button" class="btn btn-success btn-sm"><i class="feather icon-user"></i>2</button></a> <br>
                                <a href="/updateContent.php?id=<?php echo $value['id'] ?>&level=1&data=admin"><button type="button" class="btn btn-warning btn-sm"><i class="feather icon-user"></i>1</button></a>
                               </td>
                              <td><p>Admin Status: <?php if($value['user_status'] == 1){ echo "Active"; }elseif ($value['user_status'] == NULL) {
                                echo "Not Verified";
                              }else{
                                echo "Suspended";
                              } ?></p>

                                <a href="/updateContent.php?id=<?php echo $value['id'] ?>&user_status=1&verification=1&data=admin"><button type="button" class="btn btn-success btn-sm"><i class="feather icon-user-check"></i>Verify</button></a> <br>
                                <a href="/updateContent.php?id=<?php echo $value['id'] ?>&user_status=2&data=admin"><button type="button" class="btn btn-danger btn-sm"><i class="feather icon-user-x"></i>Suspend</button></a>
                              </td>

                              <td><a href="/deleteContent.php?id=<?php echo $value['id'] ?>&data=admin"><button type="button" class="btn btn-danger btn-sm"><i class="feather feather icon-trash-2"></i>Delete</button></a></td>
                              <td><?php  echo $value['date_created']; ?></td>



                            </tr>
                          <?php } } ?>
                        </tbody>
                        <tfoot>
                          <tr>
                            <th>Info</th><th>Set Level</th>
                            <th>Suspend/Verify</th>
                            <th>Delete</th>
                            <th>Date Registered</th>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>



            <!-- END OF CONTENT -->

          </div>
        </div>
      </div>
    </div>
  </div>



  <!-- Required Js -->
  <script src="/da/assets/js/vendor-all.min.js"></script>
  <script src="/da/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
  <script src="/da/assets/js/pcoded.min.js"></script>

  <!-- prism Js -->
  <script src="/da/assets/plugins/prism/js/prism.min.js"></script>
  <script src="/da/assets/js/horizontal-menu.js"></script>
  <script type="text/javascript">
  // Collapse menu
  (function() {
    if ($('#layout-sidenav').hasClass('sidenav-horizontal') || window.layoutHelpers.isSmallScreen()) {
      return;
    }
    try {
      window.layoutHelpers.setCollapsed(
        localStorage.getItem('layoutCollapsed') === 'true',
        false
      );
    } catch (e) {}
  })();
  $(function() {
    // Initialize sidenav
    $('#layout-sidenav').each(function() {
      new SideNav(this, {
        orientation: $(this).hasClass('sidenav-horizontal') ? 'horizontal' : 'vertical'
      });
    });

    // Initialize sidenav togglers
    $('body').on('click', '.layout-sidenav-toggle', function(e) {
      e.preventDefault();
      window.layoutHelpers.toggleCollapsed();
      if (!window.layoutHelpers.isSmallScreen()) {
        try {
          localStorage.setItem('layoutCollapsed', String(window.layoutHelpers.isCollapsed()));
        } catch (e) {}
      }
    });
  });
  $(document).ready(function() {
    $("#pcoded").pcodedmenu({
      MenuTrigger: 'hover',
      SubMenuTrigger: 'hover',
      themelayout: 'horizontal',
    });
  });
  </script>
  <script type="text/javascript">
  // layout types
  $('.layout-type > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.layout-type > a').removeClass('active');
    $('.navbar').removeClassPrefix('navbar-image-');
    $(this).addClass('active');
    $('head').append('<link rel="stylesheet" class="layout-css" href="">');
    if (temp == "menu-dark") {
      $('.navbar').removeClassPrefix('menu-');
      $('.navbar').removeClass('navbar-dark');
    }
    if (temp == "menu-light") {
      $('.navbar').removeClassPrefix('menu-');
      $('.navbar').removeClass('navbar-dark');
      $('.navbar').addClass(temp);
    }
    if (temp == "reset") {
      location.reload();
    }
    if (temp == "dark") {
      $('.navbar').removeClassPrefix('menu-');
      $('.navbar').addClass('navbar-dark');
      $('.layout-css').attr("href", "/da/assets/css/layouts/dark.css");
    } else {
      $('.layout-css').attr("href", "");
    }
  });
  // Header Color
  $('.header-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.header-color > a').removeClass('active');
    $(this).addClass('active');
    if (temp == "header-default") {
      $('.header').removeClassPrefix('header-');
    } else {
      $('.header').removeClassPrefix('header-');
      $('.header').addClass(temp);
    }
  });
  // rtl layouts
  $('#theme-rtl').change(function() {
    $('head').append('<link rel="stylesheet" class="rtl-css" href="">');
    if ($(this).is(":checked")) {
      $('.rtl-css').attr("href", "/da/assets/css/layouts/rtl.css");
      $('html').attr("dir", "rtl");
    } else {
      $('.rtl-css').attr("href", "");
      $('html').removeAttr("dir");
    }
  });
  // Menu Color
  $('.navbar-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.navbar-color > a').removeClass('active');
    $('.navbar').addClass('brand-dark');
    $('.navbar').removeClassPrefix('menu-');
    $(this).addClass('active');
    if (temp == "navbar-default") {
      $('.navbar').removeClassPrefix('navbar-');
      $('.navbar').removeClassPrefix('brand-dark');
    } else {
      $('.navbar').removeClassPrefix('navbar-');
      $('.navbar').addClass(temp);
    }
  });
  // Active Color
  $('.active-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.active-color > a').removeClass('active');
    $(this).addClass('active');
    if (temp == "active-default") {
      $('.navbar').removeClassPrefix('active-');
    } else {
      $('.navbar').removeClassPrefix('active-');
      $('.navbar').addClass(temp);
    }
  });

  // Menu Icon Color
  $('#icon-colored').change(function() {
    if ($(this).is(":checked")) {
      $('.navbar').addClass('icon-colored');
    } else {
      $('.navbar').removeClass('icon-colored');
    }
  });

  // title Color
  $('.title-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.title-color > a').removeClass('active');
    $(this).addClass('active');
    if (temp == "title-default") {
      $('.navbar').removeClassPrefix('title-');
    } else {
      $('.navbar').removeClassPrefix('title-');
      $('.navbar').addClass(temp);
    }
  });
  // Menu Dropdown icon
  function drpicon(temp) {
    if (temp == "style1") {
      $('.navbar').removeClassPrefix('drp-icon-');
    } else {
      $('.navbar').removeClassPrefix('drp-icon-');
      $('.navbar').addClass('drp-icon-' + temp);
    }
  }
  // Menu subitem icon
  function menuitemicon(temp) {
    if (temp == "style1") {
      $('.navbar').removeClassPrefix('menu-item-icon-');
    } else {
      $('.navbar').removeClassPrefix('menu-item-icon-');
      $('.navbar').addClass('menu-item-icon-' + temp);
    }
  }

  $.fn.removeClassPrefix = function(prefix) {
    this.each(function(i, it) {
      var classes = it.className.split(" ").map(function(item) {
        return item.indexOf(prefix) === 0 ? "" : item;
      });
      it.className = classes.join(" ");
    });
    return this;
  };
  </script>

  <!-- <div class="footer-fab">
    <div class="b-bg">
      <i class="fas fa-question"></i>
    </div>
    <div class="fab-hover">
      <ul class="list-unstyled">
        <li><a href="#"  data-text="Send Report" class="btn btn-icon btn-rounded btn-info m-0"><i class="fas fa-info-circle fa-ban"></i></a></li>
      </ul>
    </div>
  </div> -->
  <!-- modal-window-effects Js -->
  <script src="/da/assets/plugins/modal-window-effects/js/classie.js"></script>
  <script src="/da/assets/plugins/modal-window-effects/js/modalEffects.js"></script>
  <script src="/da/assets/plugins/ekko-lightbox/js/ekko-lightbox.min.js"></script>
  <script src="/da/assets/plugins/lightbox2-master/js/lightbox.min.js"></script>
  <script src="/da/assets/js/pages/ac-lightbox.js"></script>
  <!-- Tables -->
  <script src="/da/assets/plugins/data-tables/js/datatables.min.js"></script>
  <script src="/da/assets/js/pages/data-basic-custom.js"></script>
  <!--  -->
  <script type="text/javascript" src="/map/getmap.js"></script>
  <script src="/da/assets/js/analytics.js"></script>

</body>



</html>
