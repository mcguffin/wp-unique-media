<?php
/**
 *	@package UniqueMedia\Cron
 *	@version 1.0.0
 *	2018-09-22
 */

namespace UniqueMedia\Cron;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

class Job {

	/**
	 *	@var callable
	 */
	private $callback;

	/**
	 *	@var string
	 */
	private $hook;

	/**
	 *	@var string
	 */
	private $schedule;

	/**
	 *	@var string
	 */
	private $key;


	/**
	 *	@param	string		$hook
	 *	@param	callable	$callback
	 *	@param	array		$args
	 *	@param	string		$schedule
	 */
	public function __construct( $hook, $callback, $args = array(), $schedule = 'hourly' ) {

		$this->hook		= $hook;
		$this->callback	= $callback;
		$this->schedule	= $schedule;
		$this->args		= $args;

	}

	private static function run(  ) {

	}

	/**
	 *	@return Cron\Job
	 */
	public function start() {

		$cron = Cron::instance();
		$cron->register_job( $this );
		$cron->log( sprintf('Start job <%s> hook <%s> key <%s>', $this->schedule, $this->hook, $this->get_key( ) ) );

		if ( ! wp_next_scheduled( $this->hook, $this->args ) ) {
			$result = wp_schedule_event( time(), $this->schedule, $this->hook, $this->args );
			$cron->log( sprintf('Schedule %s hook %s key %s', $this->schedule, $this->hook, $this->get_key( ) ) );
		}

		return $this;
	}

	/**
	 *	@return Cron\Job
	 */
	public function stop() {

		$cron = Cron::instance();
		$cron->unregister_job( $this );

		//*
		if ( $time = wp_next_scheduled( $this->hook, $this->args ) ) {
			wp_unschedule_event( $time, $this->hook, $this->args );
			$cron->log( sprintf('Unschedule %s hook %s key %s', $this->schedule, $this->hook, $this->get_key( ) ) );
		}
		/*/
		wp_clear_scheduled_hook( $this->hook, $this->args );
		//*/
		return $this;
	}

	/**
	 *	@return string
	 */
	public function get_hook() {
		return $this->hook;
	}

	/**
	 *	@return string
	 */
	public function get_key() {
		if ( is_null( $this->key ) ) {
			$cb = $this->callback;
			if ( is_array( $cb ) && is_object( $cb[0] ) ) {
				$cb[0] = 'instance-of-' . get_class( $cb[0] );
			}
			$this->key = md5( var_export( $cb, true ) . var_export( $this->args, true ) );
		}
		return $this->key;
	}

	/**
	 *	@return callable
	 */
	public function get_callback() {
		return $this->callback;
	}

}
