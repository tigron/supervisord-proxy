<?php
/**
 * Bootstrap Class
 *
 * Initializes the Skeleton framework
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

class Bootstrap {

	/**
	 * Bootstrap
	 *
	 * @access public
	 */
	public static function boot() {
		/**
		 * Set the root path
		 */
		$root_path = realpath(dirname(__FILE__) . '/../..');

		/**
		 * Register the autoloader from Composer
		 */
		require_once $root_path . '/lib/external/packages/autoload.php';

		/**
		 * Get the config
		 */
		if (!file_exists($root_path . '/config/Config.php')) {
			echo 'Please create your Config.php file' . "\n";
			exit(1);
		}

		require_once $root_path . '/config/Config.php';
		$config = Config::get();

		/**
		 * Register the autoloader
		 */
		$autoloader = new \Skeleton\Core\Autoloader();
		$autoloader->add_include_path($root_path . '/lib/base/');
		$autoloader->register();

		/**
		 * Initialize the application directory
		 */
		\Skeleton\Core\Config::$application_dir = $root_path . '/app/';

		/**
		 * Initialize the error handler
		 */
		\Skeleton\Error\Config::$debug = true;
		\Skeleton\Error\Handler::enable();
	}
}
