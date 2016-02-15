<?php
/**
 * Module Index
 *
 * @author Gerry Demaret <gerry@tigron.be>
 */

use Skeleton\Core\Web\Module;

class Web_Module_XMLRPC extends Module {

	/**
	 * URI of the supervisord XML-RPC interface
	 */
	private $endpoint = null;

	/**
	 * Username as received from the IDENT request
	 */
	public $username = null;

	/**
	 * Display method
	 *
	 * @access public
	 */
	public function display() {
		$config = Config::get();
		$application = \Skeleton\Core\Application::get();

		$request_xml = file_get_contents("php://input");
		$xmlrpc_server = xmlrpc_server_create();

		file_put_contents('/tmp/in.log', $request_xml, FILE_APPEND);

		$module = new self();

		// Do an ident request to determine which user we are serving
		$ident = new \Tigron\Ident\IdentClient();
		$ident->setTimeout(2);
		$module->username = $ident->getUser();

		// Point the module to the supervisord endpoint
		$module->endpoint = $config->supervisord_xmlrpc_endpoint;

		// Because we are lazy
		$class = new ReflectionClass(__CLASS__);
		$methods = $class->getMethods();

		// Loop over all defined methods, only return the actual API calls
		foreach ($methods as $method) {
			if ($method->class !== __CLASS__ or strpos($method->name, 'call_') !== 0) {
				continue;
			}

			$api_method_name = str_replace('_', '.', substr($method->name, 5));
			xmlrpc_server_register_method($xmlrpc_server, $api_method_name, [$module, 'dispatch']);
		}

		// We are serving XML
		header('Content-Type: text/xml');
		$output = xmlrpc_server_call_method($xmlrpc_server, $request_xml, []);
		file_put_contents('/tmp/out.log', $output, FILE_APPEND);
		echo $output;
	}

	/**
	 * Dispatch all requests to the appropriate method
	 *
	 * @param string $method_name
	 * @param array $arguments
	 * @param array $app_data
	 * @access private
	 */
	private function dispatch($method_name, $arguments, $app_data) {
		if ($this->username === null or $this->username === false) {
			return $this->fault('Username is null, the IDENT request probably failed', 500);
		} elseif ($this->endpoint === null) {
			return $this->fault('Endpoint not set, check your configuration', 100);
		}

		$method_name = 'call_' . str_replace('.', '_', $method_name);
		return call_user_func_array([$this, $method_name], $arguments);
	}

	/**
	 * Generate an XML-RPC fault
	 *
	 * @param string $message
	 * @param int $faultcode
	 * @access private
	 */
	private function fault($message, $faultcode) {
		return [
			'faultString' => $message,
			'faultCode' => $faultcode,
		];
	}

	// Actual API calls below

	/**
	 * supervisor.addProcessGroup
	 *
	 * Update the config for a running process from config file.
	 *
	 * @param string name         name of process group to add
	 * @return boolean result     true if successful
	 */
	private function call_supervisor_addProcessGroup($name) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.addProcessGroup', [$name]);
	}

	/**
	 * supervisor.clearAllProcessLogs
	 *
	 * Clear all process log files
	 *
	 * @return boolean result      Always return true
	 */
	private function call_supervisor_clearAllProcessLogs() {
		$jobs = Job::get_jobs_for_user($this->username);

		$client = new XMLRPC($this->endpoint);
		$result = [];
		foreach ($jobs as $job) {
			$client->call('supervisor.clearProcessLog', [$job['name']]);
			$result[] = [
				'status' => 80,
				'group' => $job['name'],
				'name' => $job['name'],
				'description' => 'OK',
			];
		}

		return $result;
	}

	/**
	 * supervisor.clearLog
	 *
	 * Clear the main log.
	 *
	 * @return boolean result always returns True unless error
	 */
	private function call_supervisor_clearLog() {
		return true;
	}

	/**
	 * supervisor.clearProcessLogs
	 *
	 * Clear the stdout and stderr logs for the named process and
	 * reopen them.
	 *
	 * @param string name          The name of the process (or 'group:name')
	 * @return boolean result      Always True unless error
	 */
	private function call_supervisor_clearProcessLogs($name) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		$client->call('supervisor.clearProcessLog', [$name]);

		return true;
	}

	/**
	 * supervisor.getAPIVersion
	 *
	 * Return the version of the RPC API used by supervisord
	 *
	 * @return string version version id
	 */
	private function call_supervisor_getApiVersion() {
		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getApiVersion');
	}

	/**
	 * supervisor.getAllConfigInfo
	 *
	 * Get info about all available process configurations. Each struct
	 * represents a single process (i.e. groups get flattened).
	 *
	 * @return array result  An array of process config info structs
	 */
	private function call_supervisor_getAllConfigInfo() {
		$client = new XMLRPC($this->endpoint);
		$configinfos = $client->call('supervisor.getAllConfigInfo');

		foreach ($configinfos as $key => $configinfo) {
			if (!Job::is_job_from_user($configinfo['name'], $this->username)) {
				unset($configinfos[$key]);
			}
		}

		return array_values($configinfos);
	}

	/**
	 * supervisor.getAllProcessInfo
	 *
	 * Get info about all processes
	 *
	 * @return array result  An array of process status results
	 */
	private function call_supervisor_getAllProcessInfo() {
		$client = new XMLRPC($this->endpoint);
		$processinfos = $client->call('supervisor.getAllProcessInfo');

		foreach ($processinfos as $key => $processinfo) {
			if (!Job::is_job_from_user($processinfo['name'], $this->username)) {
				unset($processinfos[$key]);
			}
		}

		return array_values($processinfos);
	}

	/**
	 * supervisor.getIdentification
	 *
	 * Return identifiying string of supervisord
	 *
	 * @return string identifier identifying string
	 */
	private function call_supervisor_getIdentification() {
		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getIdentification');
	}

	/**
	 * supervisor.getPID
	 *
	 * Return the PID of supervisord
	 *
	 * @return int PID
	 */
	private function call_supervisor_getPID() {
		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getPID');
	}

	/**
	 * supervisor.getProcessInfo
	 *
	 * Get info about a process named name
	 *
	 * @param string name The name of the process (or 'group:name')
	 * @return struct result     A structure containing data about the process
	 */
	private function call_supervisor_getProcessInfo($name) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getProcessInfo', [$name]);
	}

	/**
	 * supervisor.getState
	 *
	 * Return current state of supervisord as a struct
	 *
	 * @return struct A struct with keys string statecode, int statename
	 */
	private function call_supervisor_getState() {
		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getState');
	}

	/**
	 * supervisor.getSupervisorVersion
	 *
	 * Return the version of the supervisor package in use by supervisord
	 *
	 * @return string version version id
	 */
	private function call_supervisor_getSupervisorVersion() {
		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getSupervisorVersion');
	}

	/**
	 * supervisor.getVersion
	 *
	 * Return the version of the RPC API used by supervisord
	 *
	 * @return string version version id
	 */
	private function call_supervisor_getVersion() {
		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.getVersion');
	}

	/**
	 * supervisor.readLog
	 *
	 * Read length bytes from the main log starting at offset
	 *
	 * @param int offset         offset to start reading from.
	 * @param int length         number of bytes to read from the log.
	 * @return string result     Bytes of log
	 */
	private function call_supervisor_readLog($offset, $length) {
		return '';
	}

	/**
	 * supervisor.readProcessLog
	 *
	 * Read length bytes from name's stdout log starting at offset
	 *
	 * @param string name        the name of the process (or 'group:name')
	 * @param int offset         offset to start reading from.
	 * @param int length         number of bytes to read from the log.
	 * @return string result     Bytes of log
	 */
	private function call_supervisor_readProcessLog($name, $offset, $length) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.readProcessLog', [$name, $offset, $length]);
	}

	/**
	 * supervisor.readProcessStderrLog
	 *
	 * Read length bytes from name's stderr log starting at offset
	 *
	 * @param string name        the name of the process (or 'group:name')
	 * @param int offset         offset to start reading from.
	 * @param int length         number of bytes to read from the log.
	 * @return string result     Bytes of log
	 */
	private function call_supervisor_readProcessStderrLog($name, $offset, $length) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.readProcessStderrLog', [$name, $offset, $length]);
	}

	/**
	 * supervisor.readProcessStdoutLog
	 *
	 * Read length bytes from name's stdout log starting at offset
	 *
	 * @param string name        the name of the process (or 'group:name')
	 * @param int offset         offset to start reading from.
	 * @param int length         number of bytes to read from the log.
	 * @return string result     Bytes of log
	 */
	private function call_supervisor_readProcessStdoutLog($name, $offset, $length) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.readProcessStdoutLog', [$name, $offset, $length]);
	}

	/**
	 * supervisor.reloadConfig
	 *
	 * Reload configuration
	 *
	 * @return boolean result  always return True unless error
	 */
	private function call_supervisor_reloadConfig() {
		return false; // denied
	}

	/**
	 * supervisor.removeProcessGroup
	 *
	 * Remove a stopped process from the active configuration.
	 *
	 * @param string name         name of process group to remove
	 * @return boolean result     Indicates wether the removal was successful
	 */
	private function call_supervisor_removeProcessGroup($name) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.removeProcessGroup', [$name]);
	}

	/**
	 * supervisor.restart
	 *
	 * Restart the supervisor process
	 *
	 * @return boolean result  always return True unless error
	 */
	private function call_supervisor_restart() {
		return false; // denied
	}

	/**
	 * supervisor.sendProcessStdin
	 *
	 * Send a string of chars to the stdin of the process name.
	 * If non-7-bit data is sent (unicode), it is encoded to utf-8
	 * before being sent to the process' stdin.  If chars is not a
	 * string or is not unicode, raise INCORRECT_PARAMETERS.  If the
	 * process is not running, raise NOT_RUNNING.  If the process'
	 * stdin cannot accept input (e.g. it was closed by the child
	 * process), raise NO_FILE.
	 *
	 * @param string name        The process name to send to (or 'group:name')
	 * @param string chars       The character data to send to the process
	 * @return boolean result    Always return True unless error
	 */
	private function call_supervisor_sendProcessStdin($name, $chars) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.sendProcessStdin', [$name, $chars]);
	}

	/**
	 * supervisor.sendRemoteCommEvent
	 *
	 * Send an event that will be received by event listener
	 * subprocesses subscribing to the RemoteCommunicationEvent.
	 *
	 * @param  string  type  String for the "type" key in the event header
	 * @param  string  data  Data for the event body
	 * @return boolean       Always return True unless error
	 */
	private function call_supervisor_sendRemoteCommEvent() {
		// FIXME: this isn't implemented
		return false;
	}

	/**
	 * supervisor.shutdown
	 *
	 * Shut down the supervisor process
	 *
	 * @return boolean result always returns True unless error
	 */
	private function call_supervisor_shutdown() {
		return false; // denied
	}

	/**
	 * supervisor.startAllProcesses
	 *
	 * Start all processes listed in the configuration file
	 *
	 * @param boolean wait    Wait for each process to be fully started
	 * @return array result   An array of process status info structs
	 */
	private function call_supervisor_startAllProcesses($wait = true) {
		$jobs = Job::get_jobs_for_user($this->username);

		$client = new XMLRPC($this->endpoint);
		$results = [];
		foreach ($jobs as $job) {
			$response = $client->call('supervisor.startProcess', [$job['name'], $wait]);

			if ($response === true) {
				$result = [];
				$result['status'] = 80;
				$result['group'] = $job['name'];
				$result['name'] = $job['name'];
				$result['description'] = 'OK';
				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 * supervisor.startProcess
	 *
	 * Start a process
	 *
	 * @param string name Process name (or 'group:name', or 'group:*')
	 * @param boolean wait Wait for process to be fully started
	 * @return boolean result     Always true unless error
	 */
	private function call_supervisor_startProcess($name, $wait = true) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.startProcess', [$name, $wait]);
	}

	/**
	 * supervisor.startProcessGroup
	 *
	 * Start all processes in the group named 'name'
	 *
	 * @param string name     The group name
	 * @param boolean wait    Wait for each process to be fully started
	 * @return array result   An array of process status info structs
	 */
	private function call_supervisor_startProcessGroup($name, $wait = true) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.startProcessGroup', [$name, $wait]);
	}

	/**
	 * supervisor.stopAllProcesses
	 *
	 * Stop all processes in the process list
	 *
	 * @param  boolean wait   Wait for each process to be fully stopped
	 * @return array result   An array of process status info structs
	 */
	private function call_supervisor_stopAllProcesses($wait = true) {
		$jobs = Job::get_jobs_for_user($this->username);

		$client = new XMLRPC($this->endpoint);
		$results = [];
		foreach ($jobs as $job) {
			$response = $client->call('supervisor.stopProcess', [$job['name'], $wait]);

			if ($response === true) {
				$result = [];
				$result['status'] = 80;
				$result['group'] = $job['name'];
				$result['name'] = $job['name'];
				$result['description'] = 'OK';
				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 * supervisor.stopProcess
	 *
	 * Stop a process named by name
	 *
	 * @param string name  The name of the process to stop (or 'group:name')
	 * @param boolean wait        Wait for the process to be fully stopped
	 * @return boolean result     Always return True unless error
	 */
	private function call_supervisor_stopProcess($name, $wait = true) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.stopProcess', [$name, $wait]);
	}

	/**
	 * supervisor.stopProcessGroup
	 *
	 * Stop all processes in the process group named 'name'
	 *
	 * @param string name     The group name
	 * @param boolean wait    Wait for each process to be fully stopped
	 * @return array result   An array of process status info structs
	 */
	private function call_supervisor_stopProcessGroup($name, $wait = true) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.stopProcessGroup', [$name, $wait]);
	}

	/**
	 * Provides a more efficient way to tail the (stderr) log than
	 * readProcessStderrLog().  Use readProcessStderrLog() to read
	 * chunks and tailProcessStderrLog() to tail.
	 *
	 * Requests (length) bytes from the (name)'s log, starting at
	 * (offset).  If the total log size is greater than (offset +
	 * length), the overflow flag is set and the (offset) is
	 * automatically increased to position the buffer at the end of
	 * the log.  If less than (length) bytes are available, the
	 * maximum number of available bytes will be returned.  (offset)
	 * returned is always the last offset in the log +1.
	 *
	 * @param string name         the name of the process (or 'group:name')
	 * @param int offset          offset to start reading from
	 * @param int length          maximum number of bytes to return
	 * @return array result       [string bytes, int offset, bool overflow]
	 */
	private function call_supervisor_tailProcessStderrLog($name, $offset, $length) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.tailProcessStderrLog', [$name, $offset, $length]);
	}

	/**
	 * Provides a more efficient way to tail the (stdout) log than
	 * readProcessStdoutLog().  Use readProcessStdoutLog() to read
	 * chunks and tailProcessStdoutLog() to tail.
	 *
	 * Requests (length) bytes from the (name)'s log, starting at
	 * (offset).  If the total log size is greater than (offset +
	 * length), the overflow flag is set and the (offset) is
	 * automatically increased to position the buffer at the end of
	 * the log.  If less than (length) bytes are available, the
	 * maximum number of available bytes will be returned.  (offset)
	 * returned is always the last offset in the log +1.
	 *
	 * @param string name         the name of the process (or 'group:name')
	 * @param int offset          offset to start reading from
	 * @param int length          maximum number of bytes to return
	 * @return array result       [string bytes, int offset, bool overflow]
	 */
	private function call_supervisor_tailProcessStdoutLog($name, $offset, $length) {
		if (!Job::is_job_from_user($name, $this->username)) {
			return; // denied
		}

		$client = new XMLRPC($this->endpoint);
		return $client->call('supervisor.tailProcessStdoutLog', [$name, $offset, $length]);
	}
}
