<?php
/**
 * Job Class
 *
 * Hacky way to get a list of all defined supervisor jobs and match them against
 * a given username. This code makes a lot of assumptions:
 *
 * - All jobs are defined in supervisord's conf.d directory
 * - Said directory does not contain subdirectories
 * - Each file in said directory contains exactly one job
 * - The filename is <jobname>.conf
 *
 * This is to be replaced with a supervisord API extension and calls to said API
 * extension, as this does not scale at all.
 *
 * @author Gerry Demaret <gerry@tigron.be>
 */

class Job {
	private static $supervisord_confd = '/etc/supervisor/conf.d';

	public static function get_all() {
		$files = scandir(self::$supervisord_confd);

		$jobs = [];
		foreach ($files as $key => $filename) {
			if (!is_file(self::$supervisord_confd . '/' . $filename) or substr($filename, -5) !== '.conf') {
				unset($files[$key]);
				continue;
			}

			$jobname = substr($filename, 0, strlen($filename) - 5);
			$content = file(self::$supervisord_confd . '/' . $filename);
			$line = array_filter($content, function($item) {
				return preg_match('/^user=/', $item);
			});

			if (count($line) <> 1) {
				unset($files[$key]);
				continue;
			}

			$user = trim(substr(array_pop($line), 5));

			$jobs[] = [
				'name' => $jobname,
				'user' => $user,
			];
		}

		return $jobs;
	}

	public static function get_jobs_for_user($user) {
		$jobs = self::get_all();
		foreach ($jobs as $key => $job) {
			if ($job['user'] !== $user) {
				unset($jobs[$key]);
			}
		}

		return array_values($jobs);
	}

	public static function is_job_from_user($jobname, $user) {
		$jobs = self::get_all();
		foreach ($jobs as $key => $job) {
			if ($job['user'] === $user and $job['name'] === $jobname) {
				return true;
			}
		}

		return false;
	}
}
