<?php
/**
 *	@package UniqueMedia\Compat
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use UniqueMedia\Core;


class ACF extends Core\PluginComponent {

	/**
	 *	@var string prefix for field group json
	 */
	private $group_prefix = 'PREFIX';

	/**
	 *	@var Core\Core instance
	 */
	private $core;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		// json load path
		add_filter('acf/settings/load_json', array( $this, 'json_load_path' ) );

		// json save path
		add_action('acf/update_field_group',		array( $this, 'maybe_json_save_path'), 9, 1);
		add_action('acf/duplicate_field_group',		array( $this, 'maybe_json_save_path'), 9, 1);
		add_action('acf/untrash_field_group',		array( $this, 'maybe_json_save_path'), 9, 1);
		add_action('acf/trash_field_group',			array( $this, 'maybe_json_save_path'), 9, 1);
		add_action('acf/delete_field_group',		array( $this, 'maybe_json_save_path'), 9, 1);
	}


	/**
	 *	Filter json save path if field prefix matches group key
	 *
	 *	@action acf/update_field_group
	 *	@action acf/duplicate_field_group
	 *	@action acf/untrash_field_group
	 *	@action acf/trash_field_group
	 *	@action acf/delete_field_group
	 */
	public function maybe_json_save_path( $field_group ) {
		if ( strpos( $field_group['key'], 'group_'.$this->group_prefix . '_' ) !== false ) {
			add_filter('acf/settings/save_json', array( $this, 'json_save_path' ) );
		}
	}

	/**
	 *	@filter acf/settings/save_json
	 */
	public function json_save_path($path){
		return $this->core->get_plugin_dir() . '/acf-json/';
	}

	/**
	 *	@filter acf/settings/load_json
	 */
	public function json_load_path($paths){
		$paths[] = $this->core->get_plugin_dir() . '/acf-json/';
		return $paths;
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
