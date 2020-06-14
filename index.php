<?php

/*
Plugin Name: WP Unique Media
Plugin URI: https://github.com/mcguffin/wp-unique-media
Description: Prevent duplicate files from being uploaded.
Author: Jörn Lund
Version: 0.1.0
Author URI: https://github.com/mcguffin
License: GPL3
Github Repository: mcguffin/wp-unique-media
GitHub Plugin URI: mcguffin/wp-unique-media
Requires WP: 4.8
Requires PHP: 5.6
Text Domain: wp-unique-media
Domain Path: /languages/
*/

/*  Copyright 2018 Jörn Lund

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin was generated by WP Plugin Scaffold
https://github.com/mcguffin/wp-plugin-scaffold
*/


namespace UniqueMedia;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


require_once __DIR__ . DIRECTORY_SEPARATOR . 'include/autoload.php';


Core\Core::instance( __FILE__ );

Cron\Cron::instance();

if ( is_admin() || defined( 'DOING_AJAX' ) ) {


	Admin\Admin::instance();

	// don't WP-Update actual repos!
	if ( ! file_exists( plugin_dir_path(__FILE__) . '/.git/' ) ) {

		// not a git. Check if https://github.com/afragen/github-updater is active. (function is_plugin_active not available yet)
		$active_plugins = get_option('active_plugins');
		if ( $sitewide_plugins = get_site_option('active_sitewide_plugins') ) {
			$active_plugins = array_merge( $active_plugins, array_keys( $sitewide_plugins ) );
		}

		if ( ! in_array( 'github-updater/github-updater.php', $active_plugins ) ) {
			// not github updater. Init our our own...
			AutoUpdate\AutoUpdateGithub::instance();
		}
	}

}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WPCLI\WPCLI::instance();
}
