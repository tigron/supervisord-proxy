<?php
/**
 * App Configuration Class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */
class Config_Api extends Config {

	/**
	 * Config array
	 *
	 * @var array
	 * @access private
	 */
	protected $config_data = [

		/**
		 * Hostnames
		 */
		'hostnames' => ['*'],

		/**
		 * Default language. If no language is requested
		 */
		'default_language' => 'en',


		/**
		 * Routes
		 */
		'routes' => [
			'web_module_xmlrpc' => [
				'RPC2',
			],
			'web_module_logtail' => [
				'logtail/$name/$output',
			],
		],
	];
}
