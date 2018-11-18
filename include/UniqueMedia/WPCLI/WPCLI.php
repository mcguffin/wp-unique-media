<?php
/**
 *	@package UniqueMedia\WPCLI
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\WPCLI;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use UniqueMedia\Core;

class WPCLI extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		$command = new Commands\HashMedia();
		\WP_CLI::add_command( 'unique-media-hash', array( $command, 'hash_media' ), array(
//			'before_invoke'	=> 'a_callable',
//			'after_invoke'	=> 'another_callable',
			'shortdesc'		=> 'WP Unique Media commands',
//			'when'			=> 'before_wp_load',
			'is_deferred'	=> false,
		) );
	}

}
