<?php
/**
 *	@package UniqueMedia\Admin
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use UniqueMedia\Core;
use UniqueMedia\Cron;


class Admin extends Core\PluginComponent {

	private $core;

	private $size_meta_key = 'mdd_size';
	private $hash_meta_key = 'mdd_hash';

	private $last_hash = null;

	private $attachment_id = null;

	private $cron_hook = 'unique_media_hash_attachments';

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init', array( $this , 'admin_init' ) );
		add_action( 'wp_enqueue_media', array( $this , 'enqueue_assets' ) );

		add_filter( 'wp_handle_upload_prefilter', array( $this, 'upload_prefilter' ) );

		$this->handle_cron();

	}

	/**
	 *	@filter wp_handle_upload_prefilter
	 */
	public function upload_prefilter( $file ) {
		global $wpdb;

		$this->last_size = $file['size'];
		$this->last_hash = md5_file( $file['tmp_name'] );

		if ( absint( $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(meta_id)
						FROM $wpdb->postmeta
						WHERE meta_key=%s AND meta_value=%d",

						$this->size_meta_key, $this->last_size )
				)
			)) {


			if ( $this->attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s LIMIT 1", $this->hash_meta_key, $this->last_hash ) ) ) {

				// ajax
				if ( isset( $_REQUEST['action'] ) && 'upload-attachment' === $_REQUEST['action'] ) {
					// return existing media file
					// stolen from wp-admin/async-upload.php
					if ( ! $attachment = wp_prepare_attachment_for_js( $this->attachment_id ) )
						wp_die();
					$attachment['duplicated_upload'] = true;
					echo wp_json_encode( array(
						'success' => true,
						'data'    => $attachment,
					) );
					exit();
				} else {
					// return error
				}

			}
		} else {
			add_action('add_attachment', array( $this,'add_attachment') );
		}
		return $file;
	}
	/**
	 *	@action add_attachment
	 */
	public function add_attachment( $attachment_id ) {
		update_post_meta( $attachment_id, $this->hash_meta_key, $this->last_hash );
		update_post_meta( $attachment_id, $this->size_meta_key, $this->last_size );
	}

	/**
	 *	Admin init
	 *	@action admin_init
	 */
	public function admin_init() {
	}

	/**
	 *	Enqueue options Assets
	 *	@action admin_print_scripts
	 */
	public function enqueue_assets() {
		wp_enqueue_script( 'unique-media-admin' , $this->core->get_asset_url( 'js/admin/wp-media.js' ) );
		wp_localize_script('unique-media-admin' , 'unique_media_admin' , array(
		) );
	}

	/**
	 *	Generate hash for an attachment.
	 *
	 *	@param int $attachment_id
	 *	@return WP_Error|array array with keys size, hash, wp_error on success, wp_error
	 */
	public function hash_attachment( $attachment_id ) {
		$wp_error = new \WP_Error();
		if ( ! $file = get_attached_file( $attachment_id ) ) {
			$wp_error->add( $this->core->get_slug() . '-error', sprintf( __('No file attached to %d','wp-unique-media'), $attachment_id ) );
			return $wp_error;
		}
		if ( ! file_exists( $file ) ) {
			$wp_error->add( $this->core->get_slug() . '-error', sprintf( __('File %s of attachment %d does not exist','wp-unique-media'), $attachment_id, $file ) );
			return $wp_error;
		}

		$prev_hash = get_post_meta( $attachment_id, $this->hash_meta_key, true );
		$prev_size = get_post_meta( $attachment_id, $this->size_meta_key, true );
		$prev_size = intval( $prev_size );

		$size = filesize( $file );
		$hash = md5_file( $file );

		if ( $prev_hash !== $hash || $prev_size !== $size ) {

			if ( $prev_hash || $prev_size ) {
				$wp_error->add( $this->core->get_slug() . '-warning', sprintf( __('Attachment %d already hashed', 'wp-unique-media' ), $attachment_id ) );
			}

			update_post_meta( $attachment_id, $this->hash_meta_key, $hash );
			update_post_meta( $attachment_id, $this->size_meta_key, $size );
		}

		return array(
			'id'		=> $attachment_id,
			'size'		=> $size,
			'hash'		=> $hash,
			'prev_size'	=> $prev_size,
			'prev_hash'	=> $prev_hash,
			'error'		=> $wp_error,
		);
	}

	/**
	 *	@return array with post_ids
	 */
	public function get_unhashed_attachments() {
		global $wpdb;
		$sql = $wpdb->prepare("SELECT p.ID FROM $wpdb->posts AS p
			   LEFT JOIN $wpdb->postmeta AS m
				   ON p.ID = m.post_id AND m.meta_key=%s
			   WHERE p.post_type=%s AND m.meta_id IS NULL", $this->hash_meta_key, 'attachment' );
		$res = $wpdb->get_col( $sql );
		return $res;
	}

	/**
	 *	Hash unhashed attachments
	 *
	 *	@param int $time_limit Max execution time for this task in seconds
	 *	@return array array of hash results
	 */
	public function hash_attachments( $time_limit = 5 ) {
		$t0 = time();
		$unhashed = $this->get_unhashed_attachments();
		$results = array();

		while ( true ) {
			if ( ! count( $unhashed ) || ( ( ( time() ) - $t0 ) > $time_limit ) ) {
				break;
			}
			$attachment_id = array_pop( $unhashed );
			$results[] = $this->hash_attachment( $attachment_id );
		}
		return $results;
	}
	/**
	 *
	 */
	public function hash_attachments_cron() {

		Cron\Cron::instance()->log( 'Run cron!' );

		$this->hash_attachments( 2 );
		$this->handle_cron();
	}

	/**
	 *
	 */
	public function handle_cron() {


		// schedule every 15 minutes
		$job = Cron\Cron::getJob( $this->cron_hook, array( $this, 'hash_attachments_cron' ), array(), 15 * 60 );

		$unhashed = $this->get_unhashed_attachments();
		$job->start();
		if ( count( $unhashed ) ) {
		} else {
//			$job->stop();
		}
	}


	/**
	 *	@inheritdoc
	 */
	public function activate() {

		// find unhashed media. Schedule hashing ... 20 every 10 minutes

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
