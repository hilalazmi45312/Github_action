<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="robots" content="noindex">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo esc_html( $cr_form_header . ' - ' . get_option( 'ivole_shop_name', get_bloginfo( 'name', 'display' ) ) ); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $cr_form_css; ?>">
  <script>
		var crAjaxURL = "<?php echo esc_url_raw( $cr_form_ajax ); ?>";
	</script>
</head>
<body>
  <div id='app'></div>
  <script>window.form=<?php echo $cr_form_content; ?></script>
  <script src="<?php echo $cr_form_js_vendors; ?>"></script>
  <script src="<?php echo $cr_form_js_bundle; ?>"></script>
</body>
</html>
