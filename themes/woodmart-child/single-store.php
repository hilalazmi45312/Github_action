<?php
/**
 * Single Store Template
 */

get_header();
the_post();

// Base store fields
$address = get_field('store_address');
$google  = get_field('store_google_map');
$waze    = get_field('store_waze');
$hours   = get_field('store_hours_repeater');
$contact = get_field('store_contact');

// In-Store Activities
// store_activities (repeater)
//   - store_activities_title (text)
//   - store_activities_items (repeater)
//        - store_activities_items_image (image)
//        - store_activities_items_info  (text/textarea)   <-- right-side text (not linked)
//        - store_activities_items_url   (url)             <-- used ONLY by "View more" button
$activities = get_field('store_activities');

// In-Store Promotions (existing)
$promos_raw = get_field('store_promotions');

// Hero
$hero_url = '';
$hero_alt = '';
if (function_exists('get_the_post_thumbnail_url') && get_the_ID()) {
    $hero_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
}
if (function_exists('get_the_post_thumbnail_id') && get_the_ID()) {
    $thumb_id = get_the_post_thumbnail_id(get_the_ID());
    $hero_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
}
if (empty($hero_alt) && function_exists('get_the_title')) {
    $hero_alt = get_the_title();
}

// Icon assets
$google_icon = 'https://demo.seco.com.my/shweb/wp-content/uploads/2025/10/store-googlemaps.png';
$waze_icon   = 'https://demo.seco.com.my/shweb/wp-content/uploads/2025/10/store-waze.png';
?>

<style>
/* RESET + LAYOUT */
body.single-store .main-page-wrapper,
body.single-store .wd-content-layout,
body.single-store .content-layout-wrapper,
body.single-store .container.wd-grid-g,
body.single-store .site-content,
body.single-store main[role="main"] { max-width:100%!important;width:100%!important;margin:0!important;padding:0!important;gap:0!important;display:block!important;overflow:visible!important;}
html,body{overflow-x:hidden}

/* HERO */
.store-hero-outer{position:relative;width:100vw;margin-left:calc(-50vw + 50%);background:#000;overflow:hidden;}
.store-hero-bg{width:100%;height:380px;max-height:70vh;background-size:cover;background-position:center;background-repeat:no-repeat;}

/* MAIN */
.store-body{max-width:1300px;margin:0 auto;padding:48px 24px 80px;box-sizing:border-box;color:#000}

/* HEADINGS */
.store-title{font-size:28px;line-height:1.3;font-weight:700;color:#000;margin:0 0 24px;text-align:left}
.store-desc-box{font-size:15px;line-height:1.7;color:#000;margin:0 0 40px}
.store-section-heading{font-size:17px;font-weight:700;line-height:1.4;color:#000;margin:0 0 12px}
.store-address-text,.store-contact-text,.store-map-links,.store-hours-table,.store-hours-table+div,.store-activities-wrapper,.store-promotions-wrapper{margin:0 0 32px}

/* ADDRESS + CONTACT */
.store-address-text{font-size:14px;line-height:1.6}
.store-contact-text{font-size:14px;line-height:1.6}
.store-contact-text a{color:#000;text-decoration:underline;text-underline-offset:2px}

/* MAP ICONS */
.store-map-links{display:flex;flex-wrap:wrap;gap:16px;align-items:center}
.store-map-links img{height:36px;width:auto;display:block;transition:opacity .2s}
.store-map-links img:hover{opacity:.8}

/* STORE HOURS */
.store-hours-table{border-collapse:collapse;border:none;font-size:14px;line-height:1.5;color:#000;width:auto;max-width:800px}
.store-hours-table td,.store-hours-table th{border:none;padding:4px 16px 4px 0;vertical-align:top}
.store-hours-table td:first-child{font-weight:600;white-space:nowrap}

/* IN-STORE ACTIVITIES (image left, text right, single row per item) */
.store-activities-block-heading{font-size:20px;font-weight:700;line-height:1.4;color:#000;margin:48px 0 24px}
.store-activity-group{margin:0 0 24px}
.store-activity-title{font-size:16px;font-weight:600;line-height:1.4;color:#000;margin:0 0 12px}
.store-activity-items-row{display:block}

/* Row container */
.store-activity-card{
  display:flex;
  align-items:flex-start;    /* TOP align text as requested */
  gap:24px;
  width:100%;
  margin:0 0 16px;
}

/* Left image */
.store-activity-image-wrap{
  flex:0 0 220px;
  width:220px;
  height:220px;
  border-radius:4px;
  background:#f5f5f5;
  overflow:hidden;
  position:relative;
}
.store-activity-image-wrap img{width:100%;height:100%;object-fit:cover;display:block;border-radius:4px}

/* Right column (text + CTA) */
.store-activity-content{flex:1 1 auto;min-width:0}
.store-activity-info{
  font-size:15px;
  line-height:1.6;
  color:#000;
  font-weight:500;
  margin:0 0 10px;
  white-space:pre-line; /* keep line breaks if textarea used */
}
/* CTA button (the ONLY clickable element) */
.store-activity-cta{
  display:inline-block;
  font-size:14px;
  line-height:1;
  padding:10px 14px;
  border-radius:4px;
  background:#111;
  color:#fff;
  text-decoration:none;
  transition:opacity .15s ease-in-out;
}
.store-activity-cta:hover{opacity:.9}

/* IN-STORE PROMOTIONS (existing) */
.store-promotions-block-heading{font-size:20px;font-weight:700;line-height:1.4;color:#000;margin:48px 0 24px}
.store-promo-category-block{margin:0 0 40px}
.store-promo-category-title{font-size:16px;font-weight:600;line-height:1.4;color:#000;margin:0 0 16px}
.store-promo-items-row{display:flex;flex-wrap:nowrap;overflow-x:auto;gap:16px;padding-bottom:8px;scrollbar-width:thin;scrollbar-color:#ccc transparent}
.store-promo-items-row::-webkit-scrollbar{height:6px}
.store-promo-items-row::-webkit-scrollbar-track{background:transparent}
.store-promo-items-row::-webkit-scrollbar-thumb{background:#ccc;border-radius:3px}
.store-promo-card{flex:0 0 auto;width:220px;max-width:80vw;font-size:13px;line-height:1.4;color:#000}
.store-promo-image-wrap{position:relative;border-radius:4px;overflow:hidden;background:#f5f5f5;margin-bottom:8px;width:220px;height:220px;max-width:80vw;max-height:80vw}
.store-promo-image-wrap img{width:100%;height:100%;object-fit:cover;display:block;border-radius:4px}
.store-promo-caption{font-size:13px;line-height:1.4;color:#000;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;display:block}

/* RESPONSIVE */
@media (max-width:768px){
  .store-activities-block-heading,.store-promotions-block-heading{font-size:18px;margin-top:40px;margin-bottom:20px}
  .store-activity-title,.store-promo-category-title{font-size:15px}
  .store-activity-image-wrap{flex-basis:150px;width:150px;height:150px}
  .store-promo-image-wrap{width:150px;height:150px;max-width:150px;max-height:150px}
}
</style>

<?php /* ======================= PAGE MARKUP ======================= */ ?>

<?php if ($hero_url): ?>
<section class="store-hero-outer">
  <div class="store-hero-bg" role="img" aria-label="<?php echo esc_attr($hero_alt); ?>" style="background-image:url('<?php echo esc_url($hero_url); ?>');"></div>
</section>
<?php endif; ?>

<section class="store-body">

  <h1 class="store-title"><?php echo esc_html(get_the_title()); ?></h1>

  <div class="store-desc-box"><?php the_content(); ?></div>

  <!-- Address -->
  <h2 class="store-section-heading">Address</h2>
  <div class="store-address-text"><?php echo esc_html($address); ?></div>

  <div class="store-map-links">
    <?php if ($google): ?>
      <a href="<?php echo esc_url($google); ?>" target="_blank" rel="noopener">
        <img src="<?php echo esc_url($google_icon); ?>" alt="Google Maps">
      </a>
    <?php endif; ?>
    <?php if ($waze): ?>
      <a href="<?php echo esc_url($waze); ?>" target="_blank" rel="noopener">
        <img src="<?php echo esc_url($waze_icon); ?>" alt="Waze">
      </a>
    <?php endif; ?>
  </div>

  <!-- Contact -->
  <?php if ($contact): ?>
    <h2 class="store-section-heading">Contact</h2>
    <div class="store-contact-text">
      <a href="tel:<?php echo esc_attr(preg_replace('/\D+/', '', $contact)); ?>"><?php echo esc_html($contact); ?></a>
    </div>
  <?php endif; ?>

  <!-- Store Hours -->
  <h2 class="store-section-heading">Store Hours</h2>
  <?php if (!empty($hours) && is_array($hours)): ?>
    <table class="store-hours-table"><tbody>
      <?php foreach ($hours as $row): ?>
        <tr>
          <td><?php echo esc_html($row['store_day'] ?? ''); ?></td>
          <td><?php echo esc_html($row['store_time'] ?? ''); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table>
  <?php else: ?>
    <div style="font-size:14px;color:#555;">No hours info yet.</div>
  <?php endif; ?>

  <!-- In-Store Activities -->
<?php if ( ! empty($activities) && is_array($activities) ): ?>
    <div class="store-activities-wrapper">
        <h2 class="store-activities-block-heading">In-Store Activities</h2>

        <?php foreach ( $activities as $activity ): ?>
            <?php
            $act_title = !empty($activity['store_activities_title']) ? $activity['store_activities_title'] : '';
            $act_items = !empty($activity['store_activities_items']) && is_array($activity['store_activities_items'])
                        ? $activity['store_activities_items'] : [];
            ?>

            <?php if ($act_title || !empty($act_items)): ?>
                <div class="store-activity-group">

                    <?php if ($act_title): ?>
                        <div class="store-activity-title"><?php echo esc_html($act_title); ?></div>
                    <?php endif; ?>

                    <?php foreach ($act_items as $act_item): ?>
                        <?php
                        $img_val = $act_item['store_activities_items_image'] ?? '';
                        $item_url = $act_item['store_activities_items_url'] ?? '';
                        $info     = $act_item['store_activities_items_info'] ?? '';

                        // Image handling
                        $img_url = '';
                        $img_alt = $act_title;

                        if (is_array($img_val)) {
                            $img_url = $img_val['url'] ?? '';
                            $img_alt = $img_val['alt'] ?? $img_alt;
                        } elseif (is_numeric($img_val)) {
                            $img_url = wp_get_attachment_image_url($img_val, 'full');
                            $maybe_alt = get_post_meta($img_val, '_wp_attachment_image_alt', true);
                            if ($maybe_alt) $img_alt = $maybe_alt;
                        } elseif (is_string($img_val)) {
                            $img_url = $img_val;
                        }
                        ?>

                        <?php if ($img_url): ?>
                        <div class="store-activity-card">

                            <div class="store-activity-image-wrap">
                                <?php if ($item_url): ?>
                                    <a href="<?php echo esc_url($item_url); ?>" target="_blank" rel="noopener">
                                        <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                                    </a>
                                <?php else: ?>
                                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                                <?php endif; ?>
                            </div>

                           
                            <div style="flex:1; display:flex; flex-direction:column; justify-content:flex-start;">

                                <?php if ($info): ?>
                                    <div style="font-size:14px; line-height:1.5; margin-bottom:10px;">
                                        <?php echo esc_html($info); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($item_url): ?>
                                    <div class="wd-button-wrapper text-left">
                                        <a class="btn btn-style_default btn-shape-round btn-size-default btn-color-primary btn-icon-pos-right"
                                           href="<?php echo esc_url($item_url); ?>" target="_blank" rel="noopener">
                                            <span class="wd-btn-text">View More</span>
                                        </a>
                                    </div>
                                <?php endif; ?>

                            </div>

                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


  <!-- In-Store Promotions (unchanged) -->
  <?php if (!empty($promos_raw) && is_array($promos_raw)): ?>
    <div class="store-promotions-wrapper">
      <h2 class="store-promotions-block-heading">In-Store Promotions</h2>

      <?php foreach ($promos_raw as $promo_cat): ?>
        <?php
          $cat_title = $promo_cat['store_promotions_category'] ?? '';
          $items     = (isset($promo_cat['store_promotions_items']) && is_array($promo_cat['store_promotions_items'])) ? $promo_cat['store_promotions_items'] : [];
        ?>
        <?php if ($cat_title || !empty($items)): ?>
          <div class="store-promo-category-block">

            <?php if ($cat_title): ?>
              <div class="store-promo-category-title"><?php echo esc_html($cat_title); ?></div>
            <?php endif; ?>

            <?php if (!empty($items)): ?>
              <div class="store-promo-items-row">
                <?php foreach ($items as $item): ?>
                  <?php
                    $img_arr   = $item['store_promotions_item_image'] ?? null;
                    $caption   = $item['store_promotions_items_caption'] ?? '';
                    $promo_url = $item['store_promotions_items_url'] ?? '';

                    $img_url = '';
                    $img_alt = $caption;
                    if (is_array($img_arr)) {
                      $img_url = $img_arr['url'] ?? '';
                      if (!empty($img_arr['alt'])) $img_alt = $img_arr['alt'];
                    } elseif (is_numeric($img_arr)) {
                      $img_url  = wp_get_attachment_image_url($img_arr, 'full');
                      $maybe_alt = get_post_meta($img_arr, '_wp_attachment_image_alt', true);
                      if ($maybe_alt) $img_alt = $maybe_alt;
                    } elseif (is_string($img_arr)) {
                      $img_url = $img_arr;
                    }
                  ?>
                  <div class="store-promo-card">
                    <?php if ($promo_url): ?>
                      <a href="<?php echo esc_url($promo_url); ?>" target="_blank" rel="noopener">
                        <div class="store-promo-image-wrap">
                          <?php if ($img_url): ?>
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                          <?php endif; ?>
                        </div>
                        <?php if ($caption): ?>
                          <span class="store-promo-caption"><?php echo esc_html($caption); ?></span>
                        <?php endif; ?>
                      </a>
                    <?php else: ?>
                      <div class="store-promo-image-wrap">
                        <?php if ($img_url): ?>
                          <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                        <?php endif; ?>
                      </div>
                      <?php if ($caption): ?>
                        <span class="store-promo-caption"><?php echo esc_html($caption); ?></span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</section>

<?php
get_footer();
?>
