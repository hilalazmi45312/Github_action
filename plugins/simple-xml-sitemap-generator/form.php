<?php
if (! defined('ABSPATH')) exit; // Exit if accessed directly

/* --------------------------------------------------------------------------------------------------------------------------------------- */
function adminForm_sxmlsg()
{


?>
  <div class="wrap">
    <h2><?php esc_html_e('Simple XML Sitemap Generator', 'simple-xml-sitemap-generator'); ?></h2>

    <p><?php esc_html_e('With this Plugin an XML Sitemap will be generated automatically.', 'simple-xml-sitemap-generator'); ?></p>
    <h3><?php esc_html_e('Installation Manual', 'simple-xml-sitemap-generator'); ?></h3>
    <p><?php esc_html_e('At the first installation you just have to refresh or resave a post or page, and after that your xml file is generated.', 'simple-xml-sitemap-generator'); ?></p>



    <hr />




    <?php
    /*
 <h3>#start#</h3>
$categories = get_categories();
foreach($categories as $category) {
   echo '<div class="col-md-4"><a href="' . get_category_link($category->term_id) . '">' . $category->name . '</a></div>';
}

<h3>#ende#</h3>		 
*/

    /*------nonce field check start ---- */
    if (isset($_REQUEST['submit'])) {

      if (
        ! isset($_POST['nonce_tel'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce_tel'])), 'nonce_tel_field')
      ) {

        //print 'Sorry, your nonce did not verify.';
        exit;
      } else {
        saveForm_quickwhatsapp();
      }
    }
    /*------nonce field check end ---- */





    /*------nonce field reset start ---- */
    if (isset($_REQUEST['submit_post_kat_sxmlsg'])) {
      if (
        ! isset($_POST['nonce_wppostkat'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce_wppostkat'])), 'nonce_wppostkat_field')
      ) {
        echo '##error';
        //print 'Sorry, your nonce did not verify.';
        exit;
      } else {
        //print '##ok';
        saveForm_kat_sxmlsg();
      }
    }
    /*------nonce field reset end ---- */












    showForm_sxmlsg();
  }
  /* --------------------------------------------------------------------------------------------------------------------------------------- */









  /* --------------------------------------------------------------------------------------------------------------------------------------- */
  //reset
  if (isset($_REQUEST['quickwhatsappbutton_reset'])) {
    // Add nonce verification for reset functionality
    if (isset($_POST['reset_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['reset_nonce'])), 'reset_nonce_field')) {
      $resetter = '';
      update_option('quickwhatsapp', sanitize_text_field($resetter));
    }
  }

  function resetForm_quickwhatsapp333333()
  {

    update_option('quickwhatsapp', '');
  }
  /* --------------------------------------------------------------------------------------------------------------------------------------- */




  /* --------------------------------------------------------------------------------------------------------------------------------------- */
  /* greetings */
  function saveForm_kat_sxmlsg()
  {
    // Check if the required field exists before processing
    if (isset($_REQUEST['sxmlsg_wpkat'])) {
      $sxmlsg_wpkat = sanitize_text_field(wp_unslash($_REQUEST['sxmlsg_wpkat']));
      //ECHO "<br />";  
      //ECHO "zum abspeichern bereitlegen: $sxmlsg_wpkat";	 
      //ECHO "<br />"; 

      //ECHO "saved";	
      esc_html_e('new setting saved', 'simple-xml-sitemap-generator');

      update_option('sxmlsg_kategorien', $sxmlsg_wpkat);
    }
  }
  /* --------------------------------------------------------------------------------------------------------------------------------------- */




  /* --------------------------------------------------------------------------------------------------------------------------------------- */
  function showForm_sxmlsg()
  {



    /* #################### WordPress Kategorie add ######################### */


    $sxmlsg_kategorien_view = get_option('sxmlsg_kategorien');


    ?>
    <form method='post'>
      <h3><?php esc_html_e('Add WordPress post category to the Sitemap?', 'simple-xml-sitemap-generator'); ?></h3>

      <?php
      //<option value="Ja" selected="selected">Ja</option>

      echo '<select name="sxmlsg_wpkat" id="sxmlsg_wpkat">';

      if ($sxmlsg_kategorien_view  == 'Nein') {
      ?>
        <option value="Nein" selected="selected"><?php esc_html_e('No', 'simple-xml-sitemap-generator'); ?></option>
        <option value="Ja"><?php esc_html_e('Yes', 'simple-xml-sitemap-generator'); ?></option>
      <?php
      } elseif ($sxmlsg_kategorien_view == 'Ja') {
      ?>
        <option value="Nein"><?php esc_html_e('No', 'simple-xml-sitemap-generator'); ?></option>
        <option value="Ja" selected="selected"><?php esc_html_e('Yes', 'simple-xml-sitemap-generator'); ?></option>
      <?php
      } else {
      ?>
        <option value="Nein"><?php esc_html_e('No', 'simple-xml-sitemap-generator'); ?></option>
        <option value="Ja"><?php esc_html_e('Yes', 'simple-xml-sitemap-generator'); ?></option>
      <?php
      }
      ?>
      </select>



      </label><br />
      <p></p>
      <input type="submit" style="height: 25px; width: 250px" name="submit_post_kat_sxmlsg" value="<?php esc_attr_e('Save', 'simple-xml-sitemap-generator'); ?>">
      <?php wp_nonce_field('nonce_wppostkat_field', 'nonce_wppostkat'); ?>
    </form><br />

    <hr>
    <br />
    <div class="wrap">
      <?php
      $screenshot = '<img src="' . esc_url(plugins_url('images/screenshot-1.png', __FILE__)) . '" width="600">';
      ?>
      <h2><?php esc_html_e('Exclude Post or Pages or WooCommerce Product?', 'simple-xml-sitemap-generator'); ?></h2>
      <p><?php esc_html_e('You would to to prevent certain Posts or Pages or WooCommerce Product from being displayed in the xml sitemap?', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php esc_html_e('1. Create  new Custome Field', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php echo wp_kses_post(__('2. enter the following text <b>sitemap</b> in the <b>Name</b> field', 'simple-xml-sitemap-generator')); ?></p>
      <p><?php echo wp_kses_post(__('3. enter the following text <b>no</b> in the <b>Value</b> field', 'simple-xml-sitemap-generator')); ?></p>
      <p><?php esc_html_e('4. save this post or page or woocommerce product', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php esc_html_e('5. this post / page / or woocommerce product is no longer displayed in your xml sitemap', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php echo wp_kses_post($screenshot); ?></p>
    </div>
    <hr>
    <div class="wrap">
      <?php
      $screenshot = '<img src="' . esc_url(plugins_url('images/screenshot-2.png', __FILE__)) . '" width="600">';
      ?>
      <h2><?php esc_html_e('Set sitemap priority value yourself', 'simple-xml-sitemap-generator'); ?></h2>
      <p><?php esc_html_e('You want to set the priority value yourself in your posts or pages?', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php esc_html_e('1. Create  new Custome Field', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php echo wp_kses_post(__('2. enter the following text <b>sitemapscore</b> in the <b>Name</b> field', 'simple-xml-sitemap-generator')); ?></p>
      <p><?php esc_html_e('3a. now you can add your priority value in the <b>Value</b> field. ', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php esc_html_e('3b. the value must be between 0 and 1 (exp 0.8) ', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php esc_html_e('4. save this post or page or woocommerce product', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php esc_html_e('5. now your post / page / or woocommerce product has a new sitemap priority value', 'simple-xml-sitemap-generator'); ?></p>
      <p><?php echo wp_kses_post($screenshot); ?></p>
    </div>
    <?php
    /* ############################################################## */
    //alt: beforefp: neu quickwhatsapp

    ?>
  </div>
  <div class="wrap">
    <h2><?php esc_html_e('Plugin recommendation', 'simple-xml-sitemap-generator'); ?></h2>
    <p><?php esc_html_e('if you need are looking for a seo plugin for your wordpress, then I recommend this plugin called "WP Smart SEO".', 'simple-xml-sitemap-generator'); ?><br />
      <?php esc_html_e('Download:', 'simple-xml-sitemap-generator'); ?> <a href="<?php echo esc_url('https://de.wordpress.org/plugins/simple-xml-sitemap-generator/'); ?>" target="_blank"><?php echo esc_url('https://de.wordpress.org/plugins/simple-xml-sitemap-generator/'); ?></a></p>

    <p><?php esc_html_e('If you want to install google analytics (Universal and GA4), then I recommend this plugin "Quick Google Analytics".', 'simple-xml-sitemap-generator'); ?><br />
      <?php esc_html_e('Download:', 'simple-xml-sitemap-generator'); ?> <a href="<?php echo esc_url('https://wordpress.org/plugins/quick-google-analytics/'); ?>" target="_blank"><?php echo esc_url('https://wordpress.org/plugins/quick-google-analytics/'); ?></a></p>

    <p><?php esc_html_e('all 3 plugins work perfectly together', 'simple-xml-sitemap-generator'); ?></p>
  </div>
  <div class="wrap">

    <h2><?php esc_html_e('Information', 'simple-xml-sitemap-generator'); ?></h2>
    <p><?php esc_html_e('This is the Simple XML Sitemap Generator Plugin for WordPress - created by', 'simple-xml-sitemap-generator'); ?> Eric-Oliver M&auml;chler von <a href="<?php echo esc_url('http://www.chefblogger.me'); ?>" target="_blank"><?php echo esc_url('www.chefblogger.me'); ?></a></p>


  </div>
<?php
  }
  /* --------------------------------------------------------------------------------------------------------------------------------------- */
?>