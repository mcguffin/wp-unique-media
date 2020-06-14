<?php
/**
 *	@package UniqueMedia\Compat
 *	@version 1.0.0
 *	2018-09-25
 */

namespace UniqueMedia\Compat;

use UniqueMedia\Admin;
use UniqueMedia\Core;

class EnableMediaReplace extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'wp_handle_replace', [ $this, 'handle_replace' ] );

	}

	/**
	 *	@action wp_handle_replace
	 */
	public function handle_replace( $args ) {

		$attachment_id = $args['post_id'];

		if ( ! isset( $_FILES['userfile']['tmp_name'] ) ) {
			return;
		}
		$file = wp_unslash( $_FILES['userfile']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! file_exists( $file ) ) {
			return;
		}

		$admin = Admin\Admin::instance();
		$new_hash = md5_file( $file );
		$duplicates = $admin->get_attachments_by_hash( $new_hash );

		foreach ( $duplicates as $duplicate_id ) {
			// deny replacement!
			$uihelper = new \EnableMediaReplace\UIHelper();

			\EnableMediaReplace\Notices\NoticeController::addError(
				/* translators: Attachment ID */
				sprintf( __( 'Duplicate file exists: ID %1$d', 'wp-unique-media' ), $duplicate_id )
			);
			wp_safe_redirect( $uihelper->getFailedRedirect( $attachment_id ) );
			exit();
		}
		/*
		deny if 
		new file in $_FILES['userfile']['tmp_name']
		*/
	}

	/**
	 *	@action enable-media-replace-upload-done
	 */
	public function enable_media_replace_upload_done( $new_guid, $current_guid ) {

		$admin = Admin\Admin::instance();

		$attachment_id = attachment_url_to_postid( $new_guid );

		if ( $attachment_id ) {
			$admin->hash_attachment( $attachment_id );			
		}

	}


	/**
	 *	@inheritdoc
	 */
	 public function activate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public function deactivate(){

	 }

	 /**
	  *	@inheritdoc
	  */
	 public static function uninstall() {
		 // remove content and settings
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}


}
