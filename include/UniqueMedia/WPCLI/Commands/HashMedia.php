<?php
/**
 *	@package UniqueMedia\WPCLI
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\WPCLI\Commands;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

class HashMedia extends \WP_CLI_Command {

	/**
	 * Bark.
	 *
	 * ## OPTIONS
	 * <media_id>...
	 * : Post-ID of attachment
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ternum bark dog mole wolve --volume=quiet
	 *
	 *	@alias comment-check
	 */
	public function hash_media( $args, $assoc_args ) {
		$total = 0;
		foreach ( $args as $animal ) {
			if ( in_array( $animal, array( 'dog', 'wolve' ) ) ) {
				$total++;
				$bark = __( "Rouff", 'wp-unique-media' );
				switch ( $assoc_args['volume'] ) {
					case 'loud':
						$bark = strtoupper($bark) . '!!!';
						break;
					case 'quiet':
						$bark = '(' . strtolower($bark) . ')';
						break;
				}
				\WP_CLI::line( $bark );
			} else if ( $animal === 'cat' ) {
				\WP_CLI::error( __( "Bad Idea, chuck!", 'wp-unique-media' ) );
			} else {
				\WP_CLI::warning( __( "$animal did not bark.", 'wp-unique-media' ) );
			}
		}
		\WP_CLI::success( sprintf( __( "%d animal(s) barked.", 'wp-unique-media' ), $total ) );
	}

}
