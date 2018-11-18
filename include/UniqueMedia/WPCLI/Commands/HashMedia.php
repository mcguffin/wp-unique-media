<?php
/**
 *	@package UniqueMedia\WPCLI
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\WPCLI\Commands;

use UniqueMedia\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

class HashMedia extends \WP_CLI_Command {

	/**
	 * Bark.
	 *
	 * ## OPTIONS
	 * [--attachment_id=<attachment_id>]
	 * : Post-ID of attachment
	 * ---
	 * default: null
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp unique-media-hash
	 *
	 */
	public function hash_media( $args, $assoc_args ) {
		$total = 0;

		$admin = Admin\Admin::instance();
		$ids = isset( $assoc_args['attachment_id'] ) ? array( intval( $assoc_args['attachment_id'] ) ) : $admin->get_unhashed_attachments();
		foreach ( $ids as $attachment_id ) {
			// calc all
			$result = $admin->hash_attachment( $attachment_id );
			if ( is_wp_error( $result ) ) {
				\WP_CLI::warning( $result->get_error_message() );
				continue;
			}

			extract($result); // id, size, hash, prev_size, prev_hash, error
			if ( $error->get_error_code() === Admin\Admin::WARNING ) {
				\WP_CLI::warning( $error->get_error_message() );
			}
			\WP_CLI::line( sprintf(__('Processed ID:<%d> Hash:<%s> '), $attachment_id, $hash ) );
			$total++;

		}
		\WP_CLI::success( sprintf( __( "%d attachments processed.", 'wp-unique-media' ), $total ) );
	}

}
