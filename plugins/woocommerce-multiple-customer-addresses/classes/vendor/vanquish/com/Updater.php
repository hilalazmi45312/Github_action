<?php
namespace WCMCA\vendor\vanquish\com;

use WCMCA\vendor\vanquish\admin\ActivationPage;

class Updater
{
	private $slug = "woocommerce-multiple-customer-addresses";
	private $script_name = "woocommerce-multiple-customer-addresses";
	private $checker_url = "https://vanquishplugins.com/updater/checker.php";
	private $updater_url = "https://vanquishplugins.com/updater/updater.php";
	private $plugin_id = 16127030;
	
	public function __construct()
	{
		//Update pre-check process
		add_filter( 'pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update') );
		//Download update process
		add_filter( 'upgrader_package_options', array($this, 'upgrader_package_options') );
		add_filter( 'upgrader_pre_download', array($this, 'upgrader_pre_download'), 10, 4 );
		
		//Plugin update info screen -> View Version XXXX details displayed in the Plugins area.
		add_filter( 'plugins_api', array($this,'plugin_api_call'), 10, 3 ); 
	}
	function upgrader_package_options($options)
	{
		if(isset($options["hook_extra"]["plugin"]) && $options["hook_extra"]["plugin"] == $this->slug . '/' . $this->script_name . '.php')
		{
			$params = $this->prepare_request();
			$options["package"] = $this->updater_url."?".http_build_query($params["body"]);
		}
		return $options;
	}
	function upgrader_pre_download($res, $package, $upgrader, $hook_extra)
	{
		$params = $this->prepare_request();
		$package_url = $this->updater_url."?".http_build_query($params["body"]);
		if($package == $package_url)
		{
			$request_string = $this->prepare_request("check_license");
			$request = wp_remote_post( $this->updater_url, $request_string );
			if(is_wp_error($request))
				return $request;
			$res = unserialize( $request['body'] );
			if($res['code'] != 6)
			{
				return new \WP_Error( 'no_package', $res['message'] );
			}
		}
		return false;
	}
	function check_for_plugin_update( $checked_data ) 
	{
		$api_url     = $this->checker_url;
		$plugin_slug = $this->slug;
		$script_name = $this->script_name;

		if ( empty( $checked_data->checked ) || !isset($checked_data->checked[ $plugin_slug . '/' . $script_name . '.php' ])) 
		{
			return $checked_data;
		}

		$request_args = [
			'slug'    => $plugin_slug,
			'version' => $checked_data->checked[ $plugin_slug . '/' . $script_name . '.php' ],
		];

		$request_string = $this->prepare_request( 'basic_check', $request_args );

		// Start checking for an update
		$raw_response = wp_remote_post( $api_url, $request_string );

		if ( ! is_wp_error( $raw_response ) && ( (int) $raw_response['response']['code'] === 200 ) ) 
		{
			$response = unserialize( $raw_response['body'] );
		}
		else
		{
			return $checked_data;
		}

		if ( is_object( $response ) && ! empty( $response ) ) { // Feed the update data into WP updater
			$checked_data->response[ $plugin_slug . '/' . $script_name . '.php' ] = $response;
		}

		return $checked_data;
	}
	
	//Info data displayed in the quick info window
	function plugin_api_call( $def, $action, $args ) 
	{
		$api_url     =  $this->updater_url;
		$plugin_slug =  $this->slug;
		$script_name = $this->script_name;
		
		// Do nothing if this is not about getting plugin information
		if ( $action !== 'plugin_information') 
		{
			return false;
		}

		if ( (string) $args->slug !== (string) $plugin_slug ) 
		{
			// Conserve the value of previous filter of plugins list in alternate API
			return $def;
		}

		// Get the current version
		$plugin_info     = get_site_transient( 'update_plugins' );
		if(!isset($plugin_info->checked) || !isset($plugin_info->checked[ $plugin_slug . '/' . $script_name . '.php' ]))
			return false;
		
		$current_version = $plugin_info->checked[ $plugin_slug . '/' . $script_name . '.php' ];
		$args->version   = $current_version;

		$request_string = $this->prepare_request( $action, $args );

		$request = wp_remote_post( $api_url, $request_string );

			
		if ( is_wp_error( $request ) ) 
		{
			$res = new \WP_Error( 'plugins_api_failed', __( 'An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>' ), $request->get_error_message() );
		} 
		else 
		{
			$res = unserialize( $request['body'] );
			if ( $res === false ) 
			{
				$res = new \WP_Error( 'plugins_api_failed', __( 'An unknown error occurred' ), $request['body'] );
			}
		}

		return $res;
	}

	function prepare_request( $action = "update", $args = array() ) 
	{
		global $wp_version;

		return [
			'body'       => [
				'action'  => $action,
				'request' => serialize( $args ),
				'api-key' => get_option("_".$this->plugin_id."_purchase_code"),
				'domain'  => ActivationPage::giveHost($_SERVER['SERVER_NAME']),
				'slug'    => $this->slug
			],
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ), 
		]; 
		
	}
	
	
}
?>