<div id="block-views-whats-new-block" class="block block-views">
  <h2>What's new on discussion</h2>
  <div class="content">
    <div class="view view-whats-new view-id-whats_new view-display-id-block view-dom-id-28360f698bb4a36608fa7870b5ab35e9">
      <div class="view-content">
        <?php

        $firstWhere = [];
        $first = selectContentDesc($conn,'topic',$firstWhere,'id',3);


         ?>
         <?php foreach ($first as $key => $value): ?>
           <?php
             $post = cleans($value['input_title']);

              ?>
           <div class="views-row views-row-1 views-row-odd views-row-first">
             <div class="views-field views-field-field-thumbnail">
               <div class="field-content"><a href="#" target="_blank"><img src="/<?php echo $value['image_1'] ?>" width="510" height="340" alt="" />
             </a>
           </div>
         </div>
             <div class="views-field views-field-title-field">
               <div class="field-content"><a href="/topic/<?php echo $post."-".$value['hash_id'] ?>" target="_blank"><?php echo $value['input_title'] ?></a>
             </div>
           </div>
             <div class="views-field views-field-body">
               <div class="field-content"></div>
             </div>
           </div>
         <?php endforeach; ?>

          <!-- <div class="views-row views-row-1 views-row-odd views-row-first">

            <div class="views-field views-field-field-thumbnail">
              <div class="field-content"><a href="#" target="_blank">
              <img src="/banner1.jpeg" width="510" height="340" alt="" />
            </a>
            </div>
          </div>
            <div class="views-field views-field-title-field">
              <div class="field-content"><a href="#" target="_blank">Title of Discussion</a>
            </div>
          </div>
            <div class="views-field views-field-body">
              <div class="field-content">
            </div>
          </div>
          </div> -->
          <!-- <div class="views-row views-row-1 views-row-odd views-row-first">

            <div class="views-field views-field-field-thumbnail">
              <div class="field-content"><a href="#" target="_blank">
              <img src="/333.jpg" width="510" height="340" alt="" />
            </a>
            </div>
          </div>
            <div class="views-field views-field-title-field">
              <div class="field-content"><a href="#" target="_blank">Title of Discussion</a>
            </div>
          </div>
            <div class="views-field views-field-body">
              <div class="field-content">
            </div>
          </div>
          </div> -->
          </div>
        </div>
      </div>
      </div>


<div id="block-views-featured-task-block-5" class="block block-views">
  <h2>Trending</h2>
  <div class="content">
    <div class="view view-featured-task view-id-featured_task view-display-id-block_5 view-dom-id-5ab7ae050cffc1c9a774703f7a689853">
      <div class="view-content">

        <?php
        $secondWhere = [];
        $second = selectContentDesc($conn,'topic',$secondWhere,'id',3); ?>


        <?php foreach($second as $key => $value): ?>
          <?php    $post = cleans($value['input_title']);
 ?>
          <div class="views-row views-row-1 views-row-even">
            <div class="discuss_featured">
              <div class="discuss_image" style="position: relative;">
                <div class="field field-name-field-group-issue-image field-type-image field-label-hidden">
                  <div class="field-items">
                    <div class="field-item even">
                      <a href="#">
                        <img src="/<?php echo $value['image_1'] ?>" width="510" height="340" alt="" />
                      </a>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="discuss_title">
                  <div class="type-wrapper"><div class="type-label">Activity :</div>
                  <span class="content_type discuss">Discuss</span>
                </div>
                <a href="/topic/<?php echo $post."-".$value['hash_id'] ?>">
                  <?php echo $value['input_title']; ?></a>
                  <span class="discuss caption">
                    "Share your views"
                  </span>
                </div>
              </div>
            </div>
        <?php endforeach; ?>






          <div class="views-row views-row-5 views-row-odd">
            <div class="do_featured">
              <div class="do_image" style="position: relative;">
                <div class="field field-name-field-thumb-image field-type-image field-label-hidden">
                  <div class="field-items">
                    <div class="field-item even"><a href="#"><img src="/banner2.png" width="510" height="340" alt="" /></a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="do_title">
                <div class="type-wrapper">
                  <div class="type-label">Activity :</div>
                  <span class="content_type task">Asset</span>
                </div>
                <a href="#">
                  <div class="field field-name-field-caption field-type-text-long field-label-hidden">
                    <div class="field-items">
                    <div class="field-item even">Efon Grammar School</div>
                  </div>
                </div>
              </a>
            </div>
          </div>
        </div>
          <div class="views-row views-row-6 views-row-even">
            <div class="do_featured">
              <div class="do_image" style="position: relative;">
                <div class="field field-name-field-thumb-image field-type-image field-label-hidden">
                  <div class="field-items">
                    <div class="field-item even"><a href="#"><img src="/banner2.png" width="510" height="340" alt="" /></a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="do_title">
                <div class="type-wrapper">
                  <div class="type-label">Activity :</div>
                  <span class="content_type task">Asset</span>
                </div>
                <a href="#">
                  <div class="field field-name-field-caption field-type-text-long field-label-hidden">
                    <div class="field-items">
                    <div class="field-item even">Efon Grammar School</div>
                  </div>
                </div>
              </a>
            </div>
          </div>
        </div>
        <div class="views-row views-row-4 views-row-even">
          <div class="blog_featured">
            <div class="blog_image" style="position: relative;">
              <a href="#" target="_blank" class="">
                <div class="field field-name-field-blog-thumb-image field-type-image field-label-hidden">
                <div class="field-items">
                  <div class="field-item even">
                    <img class="infocus" src="/banner1.jpeg" width="256" height="167" alt="" />
                  </div>
                </div>
              </div>
            </a>
            <div class="type-wrapper">
              <div class="type-label">Activity :</div>
              <span class="content_type blog">Blog</span>
            </div>
          </div>
          <div class="blog_title">

            <a href="#" target="_blank" class="read_more">Trending Blog Title</a>
          </div>
        </div>
      </div>
      <div class="views-row views-row-5 views-row-odd">
        <div class="blog_featured">
          <div class="blog_image" style="position: relative;">
            <a href="#" target="_blank" class="">
              <div class="field field-name-field-blog-thumb-image field-type-image field-label-hidden">
                <div class="field-items">
                  <div class="field-item even">
                    <img class="infocus" src="/banner3.jpg" width="256" height="167" alt="" />
            </div>
          </div>
        </div>
      </a>
            <div class="type-wrapper"><div class="type-label">Activity :</div>
            <span class="content_type blog">Blog</span>
          </div>
          </div>
          <div class="blog_title">

            <a href="#" target="_blank" class="read_more">Trending Blog Title</a>
          </div>
        </div>
      </div>
      <div class="views-row views-row-6 views-row-even">
        <div class="blog_featured">
          <div class="blog_image" style="position: relative;">
            <a href="#" target="_blank" class="">
              <div class="field field-name-field-blog-thumb-image field-type-image field-label-hidden">
                <div class="field-items">
                <div class="field-item even">
                  <img class="infocus" src="/banner2.png" width="256" height="167" alt="" />
                </div>
              </div>
            </div>
          </a>
            <div class="type-wrapper"><div class="type-label">Activity :</div>
            <span class="content_type blog">Blog</span>
          </div>
          </div>
          <div class="blog_title">

            <a href="#" target="_blank" class="read_more">Trending Blog Title</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
