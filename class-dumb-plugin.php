<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package DumbPlugin
 * @license GPL-2.0+
 * @link    https://github.com/whatadewitt/dumb-plugin
 * @version 0.0.2
 */
class DumbPlugin {

	/**
	* Refers to a single instance of this class.
	*
	* @var    object
	*/
	protected static $instance = null;

	/**
	* Refers to the slug of the plugin screen.
	*
	* @var    string
	*/
	protected $plugin_screen_slug = null;

	/**
	* Refers to the plugin file name plugin.
	*
	* @var    string
	*/
	// TODO: Update to the base plugin file...
	private $plugin_file = 'plugin-dumb.php';

	/**
	* Refers to the Github repo of the plugin.
	* ex: https://github.com/ShawONEX/{$repo}
	*
	* @var    string
	*/
	private $repo = 'dumb-plugin';

	/**
	* Refers to the access token for Github API
	*
	* @var    string
	*/
	// not needed because this is a public repo...
	private $access_token = null;

	/**
	* Refers to the Github Result
	*
	* @var    string
	*/
	// TODO: Update to the github access token for the project
	private $api_result = null;

	/**
	* Creates or returns an instance of this class.
	*
	* @since     0.0.1
	* @return    DumbPlugin    A single instance of this class.
	*/
	public function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	* Initializes the plugin by setting localization, filters, and administration functions.
	*
	* @since    0.0.1
	*/
	private function __construct() {
		/*
		 * Github specific update functions
		 * DO NOT REMOVE
		 */
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_plugin_transient' ) );
    add_filter( 'plugins_api', array( $this, 'set_plugin_info' ), 10, 3 );
    add_filter( 'upgrader_post_install', array( $this, 'plugin_post_install' ), 10, 3 );
		
		// update message for plugins
		$folder = basename( dirname( __FILE__ ) );
		add_filter ( "in_plugin_update_message-{$folder}/{$plugin_file}", array( $this, 'set_plugin_update_message' ) );

		add_filter( 'the_content', array( $this, 'dumb_filter' ) );
	}

	public function set_plugin_update_message() {
    $output = '<strong>Please update the update flow detailed here for updating this plugin.</strong>';
    return print $output;
	}

	private function get_repo_release_info() {
		// Only do this once
		if ( !empty( $this->api_result ) ) {
			return;
		}

		// Query the GitHub API
		$url = "https://api.github.com/repos/ShawONEX/{$repo}/releases";

		// We need the access token for private repos
		if ( !empty( $accessToken ) ) {
			$url = add_query_arg( array( 'access_token' => $accessToken ), $url );
		}

		// Get the results
		$this->api_result = wp_remote_retrieve_body( wp_remote_get( $url ) );
		if ( !empty( $this->api_result ) ) {
			$this->api_result = @json_decode( $this->api_result );
		}

		// Use only the latest release
		if ( is_array( $this->api_result ) ) {
			$this->api_result = $this->api_result[0];
		}
	}

	public function set_plugin_transient($transient) {
		// If we have checked the plugin data before, don't re-check
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get plugin & GitHub release information
		$this->get_repo_release_info();

		// Check the versions if we need to do an update
		$doUpdate = version_compare( $this->api_result->tag_name, $transient->checked[$this->slug] );

		// Update the transient to include our updated plugin data
		if ( $doUpdate == 1 ) {
			// commenting out for now, as we want to avoid running through
			// the automatic update process.

			// $package = $this->api_result->zipball_url;

			// // Include the access token for private GitHub repos
			// if ( !empty( $this->accessToken ) ) {
			// 	$package = add_query_arg( array( "access_token" => $this->accessToken ), $package );
			// }

			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->new_version = $this->api_result->tag_name;
			$obj->url = $this->pluginData['PluginURI'];
			$obj->package = false; // $package;
			$transient->response[$this->slug] = $obj;
		}

		return $transient;
	}

	public function set_plugin_info( $false, $action, $response ) {
		// Get plugin & GitHub release information
		$this->get_repo_release_info();

		// If nothing is found, do nothing
		if ( 
			empty( $response->slug ) 
			|| $response->slug != $this->slug 
		) {
			return false;
		}

		// Add our plugin information
		$response->last_updated = $this->api_result->published_at;
		$response->slug = $this->slug;
		$response->plugin_name  = $this->pluginData['Name'];
		$response->version = $this->api_result->tag_name;
		$response->author = $this->pluginData['AuthorName'];
		$response->homepage = $this->pluginData['PluginURI'];

		// This is our release download zip file
		$downloadLink = false; // $this->api_result->zipball_url;

		// Include the access token for private GitHub repos
		// removed for now...
		// if ( !empty( $this->accessToken ) ) {
		// 		$downloadLink = add_query_arg(
		// 			array( 'access_token' => $this->accessToken ),
		// 			$downloadLink
		// 		);
		// }
		// $response->download_link = $downloadLink;

		// We're going to parse the GitHub markdown release notes, include the parser
		require_once( plugin_dir_path( __FILE__ ) . 'lib/Parsedown.php' );

		// Create tabs in the lightbox
		$response->sections = array(
			'description' => $this->pluginData['Description'],
			'changelog' => class_exists( 'Parsedown' )
				? Parsedown::instance()->parse( $this->api_result->body )
				: $this->api_result->body
		);

		// Gets the required version of WP if available
		$matches = null;
		preg_match( "/requires:\s([\d\.]+)/i", $this->api_result->body, $matches );
		if ( !empty( $matches ) ) {
			if ( is_array( $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$response->requires = $matches[1];
				}
			}
		}

		// Gets the tested version of WP if available
		$matches = null;
		preg_match( "/tested:\s([\d\.]+)/i", $this->api_result->body, $matches );
		if ( !empty( $matches ) ) {
			if ( is_array( $matches ) ) {
				if ( count( $matches ) > 1 ) {
					$response->tested = $matches[1];
				}
			}
		}

		return $response;
	}

	// Perform additional actions to successfully install our plugin
	// probably unnecessary at the moment, but here in case we make a change
	public function plugin_post_install( $true, $hook_extra, $result ) {
		$was_activated = is_plugin_active( $this->slug );

		// Since we are hosted in GitHub, our plugin folder would have a dirname of
		// reponame-tagname change it to our original one:
		global $wp_filesystem;
		$plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( $this->slug );
		$wp_filesystem->move( $result['destination'], $plugin_folder );
		$result['destination'] = $plugin_folder;

		// Re-activate plugin if needed
		if ( $was_activated ) {
			$activate = activate_plugin( $this->slug );
		}

		return $result;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 */
	public static function activate( $network_wide ) {
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
	 * @since    0.0.1
	 */
  public static function deactivate( $network_wide ) {
	}
	
	public function dumb_filter( $content ) {
		return preg_replace('/[^\s]+/', 'woof', $content);
	}
}