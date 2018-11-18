<?php
/**
 *	@package UniqueMedia\Core
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use UniqueMedia\Compat;

class Core extends Plugin {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );

		$args = func_get_args();
		parent::__construct( ...$args );
	}


	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {
		// if ( class_exists( '\RegenerateThumbnails' ) ) {
		// 	Compat\RegenerateThumbnails::instance();
		// }
		if ( is_multisite() && function_exists('is_plugin_active_for_network') && is_plugin_active_for_network( $this->get_wp_plugin() ) ) {
			Compat\WPMU::instance();
		}
	}


	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_url( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return plugins_url( $asset, $this->get_plugin_file() );
	}

}
