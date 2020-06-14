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

	public const WARNING = 'um-state-warning';
	public const ERROR = 'um-state-error';

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
		add_filter( 'wp_handle_sideload_prefilter', array( $this, 'upload_prefilter' ) ); // #6

		add_filter( 'attachment_fields_to_edit', array($this,'attachment_fields_to_edit'), 10, 2 );
		add_filter( 'update_attached_file', [ $this, 'update_attached_file' ], 10, 2 );
		$this->handle_cron();

	}

	/**
	 *	@filter update_attached_file
	 */
	public function update_attached_file( $file, $attachment_id ) {
		if ( $file ) {
			$size = filesize( $file );
			$hash = md5_file( $file );
			update_post_meta( $attachment_id, $this->hash_meta_key, $hash );
			update_post_meta( $attachment_id, $this->size_meta_key, $size );
		}
		return $file;
	}

	/**
	 *	Show existing duplicates
	 *
	 *	@filter attachment_fields_to_edit
	 */
	public function attachment_fields_to_edit( $fields, $attachment ) {
		$dupes = $this->get_duplicates($attachment);
		if ( empty( $dupes ) ) {
			return $fields;
		}
		$html = '<ul>';
		foreach ( $dupes as $post_id ) {

			$html .= sprintf( '<li><a data-id="%d" href="%s">%s</a></li>', $post_id, get_edit_post_link( $post_id ), get_the_title( $post_id ) );
		}
		$html .= '</ul>';
		$fields[ 'wpum-duplicates' ] = array(
	       		'label' => __('Duplicates','wp-unique-media'),
	   			'input' => 'html',
	   			'html' => $html,
			);
		return $fields;
	}

	/**
	 *	Get duplicates of an attachment
	 *
	 *	@param int|WP_Post $attachment
	 *	@return array with post_ids
	 */
	private function get_duplicates( $attachment ) {

		global $wpdb;

		if ( is_numeric( $attachment ) ) {
			$attachment = get_post( $attachment );
		}

		$hash = get_post_meta( $attachment->ID, $this->hash_meta_key, true );

		if ( ! $hash ) {
			return [];
		}

		return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE post_id != %d AND meta_key = %s AND meta_value = %s",
			$attachment->ID, $this->hash_meta_key, $hash
		) );

	}

	/**
	 *	@param string $hash File hash
	 *	@return array
	 */
	public function get_attachments_by_hash( $hash ) {

		global $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
			$this->hash_meta_key, $hash
		) );

	}

	/**
	 *	@filter wp_handle_upload_prefilter
	 */
	public function upload_prefilter( $file ) {
		global $wpdb;

		if ( empty( $file['tmp_name'] ) ) {
			return $file;
		}

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
					if ( ! $attachment = wp_prepare_attachment_for_js( $this->attachment_id ) ) {
						wp_die();
					}
					$attachment['duplicate_upload'] = true;
					echo wp_json_encode( array(
						'success' => true,
						'data'    => $attachment,
					) );
					exit();
				} else {
					// media upload admin page
					$image = false;
					
					if ( function_exists( 'wp_get_original_image_path' ) ) {
						$image = wp_get_original_image_path( $this->attachment_id );						
					}
					
					if ( ! $image ) {
						$image = get_attached_file( $this->attachment_id );						
					}
					
					/* there is no way pass html in error message :( */
					/* translators: 1 Attachment ID, 2: filename */
					$file['error'] = sprintf( __( 'Duplicate file exists: ID %1$d - "%2$s"', 'wp-unique-media' ), $this->attachment_id, basename( $image ) );
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
		$core = Core\Core::instance();
		wp_enqueue_script( 'unique-media-admin', $this->core->get_asset_url( 'js/admin/wp-media.js' ), [], $core->get_version() );
		wp_localize_script('unique-media-admin', 'unique_media_admin', [] );
	}

	/**
	 *	Generate hash for an attachment.
	 *
	 *	@param int $attachment_id
	 *	@return WP_Error|array array with keys size, hash, wp_error on success, wp_error
	 */
	public function hash_attachment( $attachment_id ) {

		$wp_error = new \WP_Error();

		$file = false;

		if ( function_exists( 'wp_get_original_image_path' ) ) {
			// WP >= 5.3
			$file = wp_get_original_image_path( $attachment_id );			
		} else {
			// WP < 5.3
			$file = get_attached_file( $attachment_id );
		}

		if ( ! $file ) {
			/* translators: %d attachment ID */
			$wp_error->add( self::ERROR, sprintf( __('No file attached to %d','wp-unique-media'), $attachment_id ) );
			return $wp_error;
		}
		if ( ! file_exists( $file ) ) {
			/* translators: 1: file path 2: attachment ID */
			$wp_error->add( self::ERROR, sprintf( __('File %1$s of attachment %2$d does not exist','wp-unique-media'), $file, $attachment_id ) );
			return $wp_error;
		}

		$prev_hash = get_post_meta( $attachment_id, $this->hash_meta_key, true );
		$prev_size = get_post_meta( $attachment_id, $this->size_meta_key, true );
		$prev_size = intval( $prev_size );

		$size = filesize( $file );
		$hash = md5_file( $file );

		if ( $prev_hash !== $hash || $prev_size !== $size ) {

			if ( $prev_hash || $prev_size ) {
				$wp_error->add(
					self::WARNING,
					sprintf(
						/* translators: 1: attachment ID, 2+3: md5 hash, 4+5: file sizes in bytes */
						__('Attachment %1$d hashes differ from previous state. Hash (old:new) (%2$s:%3$s); Size (old:new) (%$4d:%$5d);', 'wp-unique-media' ),
						$attachment_id,
						$prev_hash, $hash,
						$prev_size, $size
					)
				);
			}

			update_post_meta( $attachment_id, $this->hash_meta_key, $hash );
			update_post_meta( $attachment_id, $this->size_meta_key, $size );
		} else {
			/* translators: %d attachment ID */
			$wp_error->add( self::WARNING, sprintf( __('Attachment %d already hashed', 'wp-unique-media' ), $attachment_id ) );
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
		$res = $wpdb->get_col( $wpdb->prepare("SELECT p.ID FROM $wpdb->posts AS p
			   LEFT JOIN $wpdb->postmeta AS m
				   ON p.ID = m.post_id AND m.meta_key=%s
			   WHERE p.post_type=%s AND m.meta_id IS NULL", $this->hash_meta_key, 'attachment' ) );
		return $res;
	}

	/**
	 *	Hash unhashed attachments
	 *
	 *	@param null|int $time_limit Max execution time for this task in seconds. Null for no limit
	 *	@return array array of hash results
	 */
	public function hash_attachments( $time_limit = 5 ) {
		$t0 = time();
		$unhashed = $this->get_unhashed_attachments();
		$results = array();

		while ( true ) {
			if ( ! count( $unhashed ) || ( ! is_null( $time_limit ) && ( ( ( time() ) - $t0 ) > $time_limit ) )) {
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
