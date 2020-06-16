<?php
/**
 * Event
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace App\Api\Event;

class Application extends \Skeleton\Core\Event {

	/**
	 * Bootstrap
	 *
	 * @access public
	 */
	public function bootstrap(\Skeleton\Core\Web\Module $module) {}

	/**
	 * Teardown of the application
	 *
	 * @access private
	 */
	public function teardown(\Skeleton\Core\Web\Module $module) {}
}
