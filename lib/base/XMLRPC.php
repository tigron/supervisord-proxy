<?php
/**
 * XMLRPC Class
 *
 * Small, naive and stupid XML-RPC client, supports connecting to UNIX sockets.
 *
 * @author Gerry Demaret <gerry@tigron.be>
 */

class XMLRPC_Exception extends Exception {}

class XMLRPC {
	/**
	 * @var string $endpoint XML-RPC endpoint
	 * @access private
	 */
	private $endpoint;

	/**
	 * @var int $timeout
	 * @access private
	 */
	private $timeout;

	/**
	 * Constructor
	 *
	 * @param string $endpoint
	 * @param int $timeout
	 * @return XMLRPC
	 * @access public
	 */
	public function __construct($endpoint, $timeout = 5) {
		$this->endpoint = $endpoint;
		$this->timeout = $timeout;
	}

	/**
	 * Execute an XMLRPC call
	 *
	 * @param string $method XML-RPC method to call
	 * @param array $parameters Optional parameters
	 * @return string
	 * @access public
	 */
	public function call($method, $parameters = []) {
		$post = xmlrpc_encode_request($method, $parameters);

		$url_parts = parse_url($this->endpoint);

		switch ($url_parts['scheme']) {
			case 'http':
				$port = isset($url_parts['port']) ? $url_parts['port'] : 80;
				$socket = fsockopen($url_parts['host'], $port, $error_number, $error_string, 2);
				break;
			default:
				$socket = stream_socket_client($this->endpoint, $error_number, $error_string, 2);
		}

		if (!$socket) {
			throw new XMLRPC_Exception($error_string . '(' . $error_number . ')');
		} else {
			fwrite($socket, 'POST /RPC2 HTTP/1.0' . "\r\n");
			fwrite($socket, 'Content-type: text/xml' . "\r\n");
			fwrite($socket, 'Content-length: ' . strlen($post) . "\r\n");
			fwrite($socket, "\r\n");
			fwrite($socket, $post);

			$response = '';
			while (!feof($socket)) {
				$response .= fgets($socket, 1024);
			}

			fclose($socket);
		}

		if (1 != preg_match("/^HTTP\/[0-9\.]* ([0-9]{3}) ([^\r\n]*)/", $response, $matches)) {
			throw new XMLRPC_Exception('Invalid HTTP response');
		}

		if ($matches[1] != '200') {
			throw new XMLRPC_Exception('HTTP error: ' . $matches[1] . ' ' . $matches[2]);
		}

		$response = preg_replace("/^.*\r\n\r\n/Us", '', $response);
		return xmlrpc_decode($response);
	}
}
