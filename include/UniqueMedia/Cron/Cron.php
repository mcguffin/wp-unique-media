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


use UniqueMedia\Core;

/**
 *	Usage:
 *	======
 *	Start a job every 10 minutes:
 *
 *	$job = Cron\Cron::getJob( 'some_unique_hook', 'callback', array('callback', 'args'), 600 );
 *	$job->start();
 *
 *	Stop it:
 *
 *	$job = Cron\Cron::getJob( 'same_unique_hook', 'callback', array('callback', 'args') );
 *	$job->stop();
 */
class Cron extends Core\PluginComponent {

	/**
	 *	@param	string		$hook
	 *	@param	callable	$callback
	 *	@param	array		$args
	 *	@param	absint		$interval
	 *	@return array
	 */
	public static function findJobs( $hook, $callback, $args = null, $interval = null ) {
		$jobs = array();
		$cron = get_option( 'cron' );
		$inst = self::instance();

		// early return
		if ( ! isset($inst->jobs[ $hook ]) || $inst->jobs[ $hook ] ) {
			return $jobs;
		}

		unset( $cron['version'] );

		foreach ( $cron as $time => $wp_jobs ) {

			foreach ( $wp_jobs as $job_hook => $job ) {
				if ( $job_hook != $hook ) {
					continue;
				}
				foreach ( $job as $args_key => $wp_job ) {
					//
					if (
							// callback matches
							in_array( $callback, $inst->jobs[ $hook ] ) &&

							// intarval matches
							( is_null( $interval ) || $interval == $wp_job['interval'] ) &&

							// args match
							( is_null( $args ) || $args == $wp_job['args'] )
					) {
						$jobs[]	= self::createJob( $hook, $callback, $wp_job['args'], $wp_job['interval'] );
					}
				}
			}
		}

		return $jobs;
	}

	/**
	 *	@param	string		$hook
	 *	@param	callable	$callback
	 *	@param	array		$args
	 *	@param	absint		$interval
	 *	@return Cron\Job
	 */
	public static function getJob( $hook, $callback, $args = array(), $interval = 3600 ) {

		$found = self::findJobs( $hook, $callback, $args );

		if ( ! empty( $found ) ) {
			return $found[0];
		}

		return self::createJob( $hook, $callback, $args, $interval );
	}

	/**
	 *	@param	string		$hook
	 *	@param	callable	$callback
	 *	@param	array		$args
	 *	@param	absint		$interval
	 *	@return Cron\Job
	 */
	protected static function createJob( $hook, $callback, $args = array(), $interval = 3600 ) {

		if ( $schedule = self::instance()->get_schedule( $interval ) ) {
			return new Job( $hook, $callback, $args, $schedule );
		}

	}


	/**
	 *	Constructor
	 */
	protected function __construct() {
//		add_action('init',array( $this, 'init' ) );

		add_option( 'unique_media_cronjobs', array() );
		add_option( 'unique_media_cronschedules', array() );

		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );

		$this->init();
	}

	/**
	 *	@return void
	 */
	private function init() {
		$this->jobs = get_option( 'unique_media_cronjobs' );

		foreach ( $this->jobs as $hook => $callbacks ) {

			foreach ( array_unique( $callbacks ) as $key => $callback ) {
				add_action( $hook, $callback );
			}
			$this->jobs[ $hook ] = (array) $callbacks;
		}
		$this->update();
	}


	/**
	 *	@param int $interval
	 *	@return bool|string
	 */
	public function get_schedule( $interval ) {
		if ( empty( $interval ) ) {
			return false;
		}
		if ( ! $schedule = $this->find_schedule( $interval ) ) {
			$schedule = sprintf( 'every_%d_seconds', $interval );
			$schedules = get_option('unique_media_cronschedules');
			$schedules[ $schedule ] = array(
				'interval'	=> $interval,
				'display'	=> sprintf( __( 'Every %d seconds', 'calendar-importer' ), $interval ),
			);
			update_option( 'unique_media_cronschedules', $schedules );
		}
		return $schedule;
	}

	/**
	 *	@param int $interval
	 *	@return bool|string
	 */
	public function find_schedule( $interval ) {
		foreach ( wp_get_schedules() as $slug => $schedule ) {
			if ( $schedule['interval'] == $interval ) {
				return $slug;
			}
		}
		return false;
	}

	/**
	 *	@filter cron_schedules
	 */
	public function cron_schedules( $schedules ) {
		return $schedules + get_option('unique_media_cronschedules');
	}

	/**
	 *	Register a job.
	 *	@param Cron\Job $job
	 */
	public function register_job( $job ) {
		$hook = $job->get_hook();
		$key = $job->get_key();

		if ( ! isset( $this->jobs[ $hook ] ) ) {
			$this->jobs[ $hook ] = array();
		}
		$this->jobs[ $hook ][ $key ] = $job->get_callback();

		$this->log( sprintf('Register Job: hook <%s> key <%s>', $hook, $key ) );

		$this->update();
	}

	/**
	 *	@param string $text
	 */
	public function log( $text ) {
		if ( ! is_string( $text ) ) {
			return;
		}
		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			$str = sprintf( '%s: %s', get_class( $this ), $text );
			error_log( $str );
		}
	}

	/**
	 *	Unregister a job.
	 *	@param Cron\Job $job
	 */
	public function unregister_job( $job ) {
		$hook = $job->get_hook();
		$key = $job->get_key();
		if ( isset( $this->jobs[ $hook ], $this->jobs[ $hook ][ $key ] ) ) {
			unset( $this->jobs[ $hook ][ $key ] );
		}
		$this->log( sprintf('Unregister Job: hook <%s> key <%s>', $hook, $key ) );
		$this->update();
	}

	/**
	 *	Update jobs option
	 */
	private function update() {

		update_option( 'unique_media_cronjobs', $this->jobs );

	}


	/**
	 *	@inheritdoc
	 */
	public function activate() {
		return array(
			'success'	=> true,
			'messages'	=> array(),
		);
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
		// stop all jobs
		$jobs = get_option( 'unique_media_cronjobs', $this->jobs );
		foreach ( $jobs as $hook => $callbacks ) {
			/*
			wp_clear_scheduled_hook( $hook );
			/*/
			foreach ( array_unique( (array) $callbacks ) as $callback ) {
				foreach ( self::findJobs( $hook, $callback ) as $job ) {
					$job->stop();
				}
			}
			//*/
		}
		update_option( 'unique_media_cronjobs', array() );
		return array(
			'success'	=> true,
			'messages'	=> array(),
		);
	}

	/**
	 *	@inheritdoc
	 */
	public static function uninstall() {

		delete_option( 'unique_media_cronjobs' );
		delete_option( 'unique_media_cronschedules' );

		return array(
			'success'	=> true,
			'messages'	=> array(),
		);
	}

	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}


}
