<?php
/**
 * Single Store Template
 */

get_header();

// Make sure the global $post is initialized for this single store.
the_post();

// Base store fields
$address    = get_field('store_address');
$google     = get_field('store_google_map');
$waze       = get_field('store_waze');
$hours      = get_field('store_hours_repeater');
$contact    = get_field('store_contact');

// Activities repeater (UPDATED STRUCTURE)
// store_activities (repeater)
//   - store_activities_title (text)
//   - store_activities_items (repeater)
//        - store_activities_items_image (image)
//        - store_activities_items_url   (url)
$activities = get_field('store_activities');

// Promotions repeater (UPDATED STRUCTURE)
// store_promotions (repeater)
//   - store_promotions_category (text)
//   - store_promotions_items (repeater)
//        - store_promotions_item_image      (image)
//        - store_promotions_items_caption   (text)
//        - store_promotions_items_url       (url)
$promos_raw = get_field('store_promotions');

// Hero
$hero_url = '';
$hero_alt = '';

if ( function_exists('get_the_post_thumbnail_url') && get_the_ID() ) {
    $hero_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
}

if ( function_exists('get_the_post_thumbnail_id') && get_the_ID() ) {
    $thumb_id = get_the_post_thumbnail_id(get_the_ID());
    $hero_alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
}

if ( empty($hero_alt) && function_exists('get_the_title') ) {
    $hero_alt = get_the_title();
}

// Icon assets
$google_icon = 'https://demo.seco.com.my/shweb/wp-content/uploads/2025/10/store-googlemaps.png';
$waze_icon   = 'https://demo.seco.com.my/shweb/wp-content/uploads/2025/10/store-waze.png';
?>

<style>
/* ===========================================
   RESET + LAYOUT CONTROL
=========================================== */
body.single-store .main-page-wrapper,
body.single-store .wd-content-layout,
body.single-store .content-layout-wrapper,
body.single-store .container.wd-grid-g,
body.single-store .site-content,
body.single-store main[role="main"] {
    max-width:100% !important;
    width:100% !important;
    margin:0 !important;
    padding:0 !important;
    gap:0 !important;
    display:block !important;
    overflow:visible !important;
}

html, body {
    overflow-x:hidden;
}

/* ===========================================
   HERO
=========================================== */
.store-hero-outer {
    position:relative;
    width:100vw;
    margin-left:calc(-50vw + 50%);
    background:#000;
    overflow:hidden;
}
.store-hero-bg {
    width:100%;
    height:380px;
    max-height:70vh;
    background-size:cover;
    background-position:center center;
    background-repeat:no-repeat;
}

/* ===========================================
   MAIN BODY
=========================================== */
.store-body {
    max-width:1300px;
    margin:0 auto;
    padding:48px 24px 80px;
    box-sizing:border-box;
    font-family:inherit;
    color:#000;
}

/* ===========================================
   TITLE + DESCRIPTION
=========================================== */
.store-title {
    font-size:28px;
    line-height:1.3;
    font-weight:700;
    color:#000;
    margin:0 0 24px 0;
    text-align:left;
}
.store-desc-box {
    max-width:100%;
    font-size:15px;
    line-height:1.7;
    color:#000;
    margin:0 0 40px 0;
}

/* ===========================================
   SECTION HEADINGS
=========================================== */
.store-section-heading {
    font-size:17px;
    font-weight:700;
    line-height:1.4;
    color:#000;
    margin:0 0 12px 0;
}

/* consistent spacing below blocks */
.store-address-text,
.store-contact-text,
.store-map-links,
.store-hours-table,
.store-hours-table + div,
.store-activities-wrapper,
.store-promotions-wrapper {
    margin:0 0 32px 0;
}

/* ===========================================
   ADDRESS + CONTACT
=========================================== */
.store-address-text {
    max-width:100%;
    font-size:14px;
    line-height:1.6;
    color:#000;
}

.store-contact-text {
    font-size:14px;
    line-height:1.6;
    color:#000;
}
.store-contact-text a {
    color:#000;
    text-decoration:underline;
    text-underline-offset:2px;
}

/* ===========================================
   MAP ICONS
=========================================== */
.store-map-links {
    display:flex;
    flex-wrap:wrap;
    gap:16px;
    align-items:center;
}
.store-map-links img {
    height:36px;
    width:auto;
    display:block;
    transition:opacity .2s;
}
.store-map-links img:hover {
    opacity:.8;
}

/* ===========================================
   STORE HOURS
=========================================== */
.store-hours-table {
    border-collapse:collapse;
    border:none;
    font-size:14px;
    line-height:1.5;
    color:#000;
    width:auto;
    max-width:800px;
}
.store-hours-table td,
.store-hours-table th {
    border:none;
    padding:4px 16px 4px 0;
    vertical-align:top;
    color:#000;
}
.store-hours-table td:first-child {
    font-weight:600;
    white-space:nowrap;
}

/* ===========================================
   IN-STORE ACTIVITIES
=========================================== */
.store-activities-block-heading,
.store-promotions-block-heading {
    font-size:20px;
    font-weight:700;
    line-height:1.4;
    color:#000;
    margin:48px 0 24px 0;
}

.store-activity-group {
    margin:0 0 40px 0;
}

.store-activity-title {
    font-size:16px;
    font-weight:600;
    line-height:1.4;
    color:#000;
    margin:0 0 16px 0;
}

/* flex row of activity cards */
.store-activity-items-row {
    display:flex;
    flex-wrap:wrap;
    gap:24px;
}

.store-activity-card {
    width:220px;
    max-width:80vw;
    font-size:13px;
    line-height:1.4;
    color:#000;
}

.store-activity-image-wrap {
    width:220px;
    height:220px;
    max-width:80vw;
    max-height:80vw;
    border-radius:4px;
    background:#f5f5f5;
    overflow:hidden;
    position:relative;
}

.store-activity-image-wrap img {
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    border-radius:4px;
}

/* ===========================================
   IN-STORE PROMOTIONS
=========================================== */
.store-promo-category-block {
    margin:0 0 40px 0;
}

.store-promo-category-title {
    font-size:16px;
    font-weight:600;
    line-height:1.4;
    color:#000;
    margin:0 0 16px 0;
}

/* Horizontal scroll for promo items */
.store-promo-items-row {
    display:flex;
    flex-wrap:nowrap;
    overflow-x:auto;
    gap:16px;
    padding-bottom:8px;
    scrollbar-width:thin;
    scrollbar-color:#ccc transparent;
}
.store-promo-items-row::-webkit-scrollbar {
    height:6px;
}
.store-promo-items-row::-webkit-scrollbar-track {
    background:transparent;
}
.store-promo-items-row::-webkit-scrollbar-thumb {
    background:#ccc;
    border-radius:3px;
}

.store-promo-card {
    flex:0 0 auto;
    width:220px;
    max-width:80vw;
    font-size:13px;
    line-height:1.4;
    color:#000;
    font-weight:400;
}

.store-promo-image-wrap {
    position:relative;
    border-radius:4px;
    overflow:hidden;
    background:#f5f5f5;
    margin-bottom:8px;
    width:220px;
    height:220px;
    max-width:80vw;
    max-height:80vw;
}
.store-promo-image-wrap img {
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    border-radius:4px;
}

.store-promo-caption {
    font-size:13px;
    line-height:1.4;
    color:#000;
    font-weight:400;
    white-space:nowrap;
    text-overflow:ellipsis;
    overflow:hidden;
    max-width:100%;
    display:block;
    text-decoration:none;
}

/* make promo card clickable affordance */
.store-promo-card a {
    color:inherit;
    text-decoration:none;
}
.store-promo-card a:hover .store-promo-caption {
    text-decoration:underline;
}

/* ===========================================
   RESPONSIVE
=========================================== */
@media (max-width:768px){
    .store-activities-block-heading,
    .store-promotions-block-heading{
        font-size:18px;
        margin-top:40px;
        margin-bottom:20px;
    }

    .store-activity-title,
    .store-promo-category-title{
        font-size:15px;
    }

    .store-activity-image-wrap,
    .store-promo-image-wrap {
        width:150px;
        height:150px;
        max-width:150px;
        max-height:150px;
    }
}
</style>

<?php
/* ===========================================
   PAGE MARKUP
=========================================== */
?>

<?php if ( $hero_url ): ?>
    <section class="store-hero-outer">
        <div
            class="store-hero-bg"
            role="img"
            aria-label="<?php echo esc_attr($hero_alt); ?>"
            style="background-image:url('<?php echo esc_url($hero_url); ?>');">
        </div>
    </section>
<?php endif; ?>

<section class="store-body">

    <h1 class="store-title">
        <?php echo esc_html( get_the_title() ); ?>
    </h1>

    <div class="store-desc-box">
        <?php the_content(); ?>
    </div>

    <!-- Address Section -->
    <h2 class="store-section-heading">Address</h2>

    <div class="store-address-text">
        <?php echo esc_html($address); ?>
    </div>

    <div class="store-map-links">
        <?php if ( $google ): ?>
            <a href="<?php echo esc_url($google); ?>" target="_blank" rel="noopener">
                <img src="<?php echo esc_url($google_icon); ?>" alt="Google Maps" />
            </a>
        <?php endif; ?>

        <?php if ( $waze ): ?>
            <a href="<?php echo esc_url($waze); ?>" target="_blank" rel="noopener">
                <img src="<?php echo esc_url($waze_icon); ?>" alt="Waze" />
            </a>
        <?php endif; ?>
    </div>

    <!-- Contact Section -->
    <?php if ( $contact ): ?>
        <h2 class="store-section-heading">Contact</h2>
        <div class="store-contact-text">
            <a href="tel:<?php echo esc_attr(preg_replace('/\D+/', '', $contact)); ?>">
                <?php echo esc_html($contact); ?>
            </a>
        </div>
    <?php endif; ?>

    <!-- Store Hours -->
    <h2 class="store-section-heading">Store Hours</h2>

    <?php if ( ! empty($hours) && is_array($hours) ): ?>
        <table class="store-hours-table">
            <tbody>
            <?php foreach ( $hours as $row ): ?>
                <tr>
                    <td><?php echo esc_html($row['store_day']); ?></td>
                    <td><?php echo esc_html($row['store_time']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="font-size:14px; color:#555;">
            No hours info yet.
        </div>
    <?php endif; ?>

    <!-- In-Store Activities -->
    <?php if ( ! empty($activities) && is_array($activities) ): ?>
        <div class="store-activities-wrapper">
            <h2 class="store-activities-block-heading">In-Store Activities</h2>

            <?php foreach ( $activities as $activity ): ?>
                <?php
                $act_title = ! empty($activity['store_activities_title'])
                    ? $activity['store_activities_title']
                    : '';

                $act_items = ! empty($activity['store_activities_items']) && is_array($activity['store_activities_items'])
                    ? $activity['store_activities_items']
                    : [];
                ?>

                <?php if ( $act_title || ! empty($act_items) ): ?>
                    <div class="store-activity-group">

                        <?php if ( $act_title ): ?>
                            <div class="store-activity-title">
                                <?php echo esc_html($act_title); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty($act_items) ): ?>
                            <div class="store-activity-items-row">
                                <?php foreach ( $act_items as $act_item ): ?>
                                    <?php
                                    // image can be array, ID, or empty
                                    $img_val = ! empty($act_item['store_activities_items_image'])
                                        ? $act_item['store_activities_items_image']
                                        : null;

                                    $item_url = ! empty($act_item['store_activities_items_url'])
                                        ? $act_item['store_activities_items_url']
                                        : '';

                                    $img_url = '';
                                    $img_alt = $act_title;

                                    if ( is_array($img_val) ) {
                                        if ( ! empty($img_val['url']) ) {
                                            $img_url = $img_val['url'];
                                        }
                                        if ( ! empty($img_val['alt']) ) {
                                            $img_alt = $img_val['alt'];
                                        }
                                    } elseif ( is_numeric($img_val) ) {
                                        $img_url = wp_get_attachment_image_url($img_val, 'full');
                                        $maybe_alt = get_post_meta($img_val, '_wp_attachment_image_alt', true);
                                        if ( ! empty($maybe_alt) ) {
                                            $img_alt = $maybe_alt;
                                        }
                                    } elseif ( is_string($img_val) ) {
                                        $img_url = $img_val;
                                    }
                                    ?>

                                    <?php if ( $img_url ): ?>
                                        <div class="store-activity-card">
                                            <div class="store-activity-image-wrap">
                                                <?php if ( $item_url ): ?>
                                                    <a href="<?php echo esc_url($item_url); ?>" target="_blank" rel="noopener">
                                                        <img src="<?php echo esc_url($img_url); ?>"
                                                             alt="<?php echo esc_attr($img_alt); ?>" />
                                                    </a>
                                                <?php else: ?>
                                                    <img src="<?php echo esc_url($img_url); ?>"
                                                         alt="<?php echo esc_attr($img_alt); ?>" />
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- In-Store Promotions -->
    <?php if ( ! empty($promos_raw) && is_array($promos_raw) ): ?>
        <div class="store-promotions-wrapper">
            <h2 class="store-promotions-block-heading">In-Store Promotions</h2>

            <?php foreach ( $promos_raw as $promo_cat ): ?>
                <?php
                $cat_title = ! empty($promo_cat['store_promotions_category'])
                    ? $promo_cat['store_promotions_category']
                    : '';

                $items = ! empty($promo_cat['store_promotions_items'])
                    && is_array($promo_cat['store_promotions_items'])
                    ? $promo_cat['store_promotions_items']
                    : [];
                ?>

                <?php if ( $cat_title || ! empty($items) ): ?>
                    <div class="store-promo-category-block">

                        <?php if ( $cat_title ): ?>
                            <div class="store-promo-category-title">
                                <?php echo esc_html($cat_title); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty($items) ): ?>
                            <div class="store-promo-items-row">
                                <?php foreach ( $items as $item ): ?>
                                    <?php
                                    $img_arr = ! empty($item['store_promotions_item_image'])
                                        ? $item['store_promotions_item_image']
                                        : null;

                                    $caption = ! empty($item['store_promotions_items_caption'])
                                        ? $item['store_promotions_items_caption']
                                        : '';

                                    $promo_url = ! empty($item['store_promotions_items_url'])
                                        ? $item['store_promotions_items_url']
                                        : '';

                                    $img_url = '';
                                    $img_alt = $caption;

                                    if ( is_array($img_arr) ) {
                                        if ( ! empty($img_arr['url']) ) {
                                            $img_url = $img_arr['url'];
                                        }
                                        if ( ! empty($img_arr['alt']) ) {
                                            $img_alt = $img_arr['alt'];
                                        }
                                    } elseif ( is_numeric($img_arr) ) {
                                        $img_url = wp_get_attachment_image_url($img_arr, 'full');
                                        $maybe_alt = get_post_meta($img_arr, '_wp_attachment_image_alt', true);
                                        if ( ! empty($maybe_alt) ) {
                                            $img_alt = $maybe_alt;
                                        }
                                    } elseif ( is_string($img_arr) ) {
                                        $img_url = $img_arr;
                                    }

                                    // build card markup (wrap in link if URL set)
                                    ?>
                                    <div class="store-promo-card">
                                        <?php if ( $promo_url ): ?>
                                            <a href="<?php echo esc_url($promo_url); ?>" target="_blank" rel="noopener">
                                                <div class="store-promo-image-wrap">
                                                    <?php if ( $img_url ): ?>
                                                        <img src="<?php echo esc_url($img_url); ?>"
                                                             alt="<?php echo esc_attr($img_alt); ?>" />
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ( $caption ): ?>
                                                    <span class="store-promo-caption">
                                                        <?php echo esc_html($caption); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="store-promo-image-wrap">
                                                <?php if ( $img_url ): ?>
                                                    <img src="<?php echo esc_url($img_url); ?>"
                                                         alt="<?php echo esc_attr($img_alt); ?>" />
                                                <?php endif; ?>
                                            </div>
                                            <?php if ( $caption ): ?>
                                                <span class="store-promo-caption">
                                                    <?php echo esc_html($caption); ?>
                                                </span>
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
