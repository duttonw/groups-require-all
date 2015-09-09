<?php
/**
 * Plugin Name: Groups Require All
 * Plugin URI: http://www.enhanceindustries.com.au/groups-require-all
 * Description: Makes posts/pages require all selected groups to be met before showing the posts/pages
 * Author: William Dutton
 * Author URI: http://www.enhanceindustries.com.au/williamjdutton
 * Version: 1.0.0
 *
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   Groups_Require_All
 * @author    William Dutton
 * @category  membership
 * @copyright Copyright (c) 2015, Enhance Industries
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function is_groups_active() {
	$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	return in_array( 'groups/groups.php', $active_plugins ) || array_key_exists( 'groups/groups.php', $active_plugins );
}

// Check if Groups is active
if ( ! is_groups_active() ) {
	return;
}


/**
 * The Groups_Require_All global object
 * @name $Groups_Require_All
 * @global Groups_Require_All $GLOBALS['Groups_Require_All']
 */
$GLOBALS['Groups_Require_All'] = new Groups_Require_All();

class Groups_Require_All {

	/** plugin version number */
	const VERSION = '1.0.0';

	/** @var string the plugin path */
	private $plugin_path;

	/** @var string the plugin url */
	private $plugin_url;

	
	/** @var \Groups_Require_All_Admin admin class */
	private $admin;
     
    /**
	 * Initializes the plugin
	 *
	 * @since 1.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'groups_require_all_load' ), 25 );
		
		// admin
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			// run every time
			$this->install();
		}
	}
	
	public function groups_require_all_load() {
		add_filter( 'groups_post_access_user_can_read_post', array( $this, 'groups_require_all' ), 10, 3 );
	}
	
	public function groups_require_all($result, $post_id, $user_id) {
		$groups_user = new Groups_User( $user_id );
		$read_caps = Groups_Post_Access::get_read_post_capabilities( $post_id );
		
		if ( !empty( $read_caps ) ) {
			//loop through read capabilities
			foreach( $read_caps as $read_cap ) {
				//check if any of them are false (e.g. if can return false)
				if ( ! $groups_user->can( $read_cap ) ) {
					return false;
				}
			}
		}
		
		//If none are false, success;
		return true;
	}
    
	/**
	 * Gets the absolute plugin path without a trailing slash, e.g.
	 * /path/to/wp-content/plugins/plugin-directory
	 *
	 * @since 1.0
	 * @return string plugin path
	 */
	public function get_plugin_path() {

		if ( $this->plugin_path )
			return $this->plugin_path;

		return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Gets the plugin url without a trailing slash
	 *
	 * @since 1.0
	 * @return string the plugin url
	 */
	public function get_plugin_url() {

		if ( $this->plugin_url )
			return $this->plugin_url;

		return $this->plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/** Lifecycle methods ******************************************************/

	/**
	 * Run every time.  Used since the activation hook is not executed when updating a plugin
	 *
	 * @since 1.0
	 */
	private function install() {

		// get current version to check for upgrade
		$installed_version = get_option( 'Groups_Require_All_version' );

		// install
		if ( ! $installed_version ) {

			// initial install work if required
		}

		// upgrade if installed version lower than plugin version
		if ( -1 === version_compare( $installed_version, self::VERSION ) )
			$this->upgrade( $installed_version );
	}

	/**
	 * Perform any version-related changes. Changes to custom db tables are handled by the migrate() method
	 *
	 * @since 1.0
	 * @param int $installed_version the currently installed version of the plugin
	 */
	private function upgrade( $installed_version ) {

		// update the installed version option
		update_option( 'Groups_Require_All_version', self::VERSION );
	}

} // end \Groups_Require_All class (note the newline after this line)
