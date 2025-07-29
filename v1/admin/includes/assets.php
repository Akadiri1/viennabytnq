<?php
$totalHospital = totalCount($conn,'hospital');
$totalSchools = totalCount($conn,'schools');
$totalPrimarySchools = totalCategoryCount($conn,'schools','category','primary');

$totalSecondarySchools = totalCategoryCount($conn,'schools','category','secondary');
 ?>
<div id="block-new-home-page-performance-dashboard" class="block block-new-home-page">

  <h2>Asset Category</h2>

  <div class="content">
    <div class="block-title">
      <span>Real Time Updates on Current Status of Mckodev State Assets  </span>
    </div>
    <div class="performance-block">
      <div class="other-schemes">
        <div class="scheme-block">
          <div class="scheme-view">
            <div class="scheme-icon">
              <img src="/trans_image/JanAushadhiKendra.png" alt="scheme" />
            </div>


            <div class="title-text">Health Care</div>
            <a href="/health-care/hospitals">
            <div class="scheme-overlay">
              <div class="title-text">No. of Hospitals</div>
              <span class="Count"><?php echo $totalHospital ?></span>
              <div class="title-text">Click for Details</div>
            </div>
            </a>
          </div>
        </div>
        <div class="scheme-block">
          <div class="scheme-view">
            <div class="scheme-icon">
              <img src="/trans_image/Scholarship.png" alt="scheme" />
            </div>
            <!-- <p><span class="Count">2000</span></p> -->
            <div class="title-text">Primary Education</div>
              <a href="/primary-education/schools">
            <div class="scheme-overlay">
              <div class="title-text">No. of Primary Schools</div>
              <span class="Count"><?php echo $totalPrimarySchools ?></span>
              <div class="title-text">Click for Details</div>
            </div>
          </a>
          </div>
        </div>
        <div class="scheme-block">
          <div class="scheme-view">
            <div class="scheme-icon">
              <img src="/trans_image/Scholarship.png" alt="scheme" />
            </div>
            <!-- <p><span class="Count">2000</span></p> -->
            <div class="title-text">Secondary Education</div>
              <a href="/secondary-education/schools">
            <div class="scheme-overlay">
              <div class="title-text">No. of Secondary Schools</div>
              <span class="Count"><?php echo $totalSecondarySchools ?></span>
              <div class="title-text">Click for Details</div>
            </div>
          </a>
          </div>
        </div>
        <div class="scheme-block">
          <div class="scheme-view">
            <div class="scheme-icon">
              <img src="/trans_image/Atal-TinkeringLAb.png" alt="scheme" />
            </div>
            <!-- <p><span class="Count">3000</span></p> -->
            <div class="title-text">Agriculture</div>
            <div class="scheme-overlay">
              <div class="title-text">No. of Hospitals</div>
              <span class="Count">8,878</span>
            </div>
          </div>
        </div>
        <div class="scheme-block">
          <div class="scheme-view">
            <div class="scheme-icon">
              <img src="/trans_image/Atal-TinkeringLAb.png" alt="scheme" />
            </div>
            <!-- <p><span class="Count">8,878</span></p> -->
            <div class="title-text">Commerce</div>
            <div class="scheme-overlay">
              <div class="title-text">No. of Facilities</div>
              <span class="Count">8,878</span>
            </div>
          </div>
        </div>

      </div>
      <!-- <div class="disclaimer-perform"><span>This is only a representative section and not an exhaustive list of government schemes and programs</span></div>
      <div class="more-btn"> <a href="https://transformingindia.mygov.in/performance-dashboard/" target="_blank">View Extended Dashboard</a></div> -->
    </div>
  </div>
  </div>
