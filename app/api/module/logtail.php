<?php
/**
 * Module Index
 *
 * @author Gerry Demaret <gerry@tigron.be>
 */

use Skeleton\Core\Web\Module;

class Web_Module_Logtail extends Module {

	/**
	 * Display method
	 *
	 * @access public
	 */
	public function display() {
		$config = Config::get();

		$ident = new \Tigron\Ident\IdentClient();
		$ident->setTimeout(2);
		$username = $ident->getUser();

		if ($username === false or $username === null) {
			echo 'Could not get your username, IDENT request failed';
			die();
		}

		$request = explode('/', $_SERVER['REQUEST_URI']);

		if (count($request) <> 4) {
			echo 'Invalid request';
			die();
		}

		if (!Job::is_job_from_user($request[2], $username)) {
			echo 'Job does not belong to user' . $request[1];
			die();
		}

		set_time_limit( 120);
		ob_implicit_flush(true);
		ob_end_flush();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $config->supervisord_logtail_endpoint . $_SERVER['REQUEST_URI']);
		curl_setopt($ch, CURLOPT_BUFFERSIZE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, [$this, 'curl_callback']);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		$html = curl_exec($ch);
		curl_close($ch);
	}

	/**
	 * cURL callback method, outputs data as soon as any is received
	 *
	 * @param resource $curl_handle
	 * @string $data
	 * @access private
	 */
	private function curl_callback($curl_handle, $data) {
		echo $data;
		flush();
		return strlen($data);
	}
}
