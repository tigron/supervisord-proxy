<?php
/**
 * Configuration Class
 *
 * Implemented as singleton (only one instance globally).
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

class Config {
	/**
	 * Config array
	 *
	 * @var array
	 * @access private
	 */
	protected $config_data = [];

	/**
	 * Config object
	 *
	 * @var Config
	 * @access private
	 */
	private static $config = null;

	/**
	 * Private (disabled) constructor
	 *
	 * @access private
	 */
	public function __construct() {
		$this->config_data = array_merge($this->read(), $this->config_data);

		// See if we have an environment file, if that is the case, its contents
		// should override any configuration defined in our current config_data
		$environment_file = dirname(__FILE__) . '/../.environment.php';
		if (file_exists($environment_file)) {
			require($environment_file);
			$this->config_data = array_merge($this->config_data, $environment);
		}
	}

	/**
	 * Get config vars as properties
	 *
	 * @param string name
	 * @return mixed
	 * @throws Exception When accessing an unknown config variable, an Exception is thrown
	 * @access public
	 */
	public function __get($name) {
		if (!array_key_exists($name, $this->config_data)) {
			throw new Exception('Attempting to read unkown config key: '.$name);
		}

		return $this->config_data[$name];
	}
	/**
	 * Get function, returns a Config object
	 *
	 * @return Config
	 * @access public
	 */
	public static function Get() {
		if (!isset(self::$config)) {
			try {
				self::$config = \Skeleton\Core\Application::Get()->config;
			} catch (Exception $e) {
				return new Config();
			}
		}
		return self::$config;
	}

	/**
	 * Check if config var exists
	 *
	 * @param string key
	 * @return bool $isset
	 * @access public
	 */
	public function __isset($key) {
		if (!isset($this->config_data) OR $this->config_data === null) {
			$this->read();
		}

		if (isset($this->config_data[$key])) {
			return true;
		}

		return false;
	}

	/**
	 * Read config file
	 *
	 * Populates the $this->config var, now the config is just in this function
	 * but it could easily be replaced with something else
	 *
	 * @access private
	 */
	private function read() {
		return [
			/**
			 * APPLICATION SPECIFIC CONFIGURATION
			 *
			 * The following configuration items needs to be overwritten in the application config file
			 */

			/**
			 * The hostname to listen on
			 */
			'hostnames' => [],

			/**
			 * Routes
			 */
			'routes' => [],

			/**
			 * Default language. Used for sending mails when the language is not given
			 */
			'default_language' => 'en',

			/**
			 * Default module
			 */
			'module_default' => 'index',

			/**
			 * 404_module
			 */
			'module_404' => '404',

			/**
			 * GENERAL CONFIGURATION
			 *
			 * These configuration items can be overwritten by application specific configuration.
			 * However they are probably the same for all applications.
			 */

			/**
			 * Setting debug to true will enable debug output and error display.
			 * Error email is not affected.
			 */
			'debug' => true,
			'debug_errors_from' => 'errors@example.com',
			'debug_errors_to' => null,

			/**
			 * Translation base language that the templates will be made up in
			 * Do not change after creation of your project!
			 */
			'base_language' => 'en',

			/**
			 * The default language that will be shown to the user if it can not be guessed
			 */
			'default_language' => 'en',
		];
	}
}
