<?php
/**
 * User class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

use \Skeleton\Database\Database;

class User {
	use \Skeleton\Object\Model;
	use \Skeleton\Object\Get;
	use \Skeleton\Object\Save;
	use \Skeleton\Object\Delete;
	use \Skeleton\Pager\Page;

	/**
	 * Class configuration
	 *
	 * @var array
	 * @access private
	 */
	private static $class_configuration = [
		'disallow_set' => ['password'],
	];

	/**
	 * @var User $user
	 * @access private
	 */
	private static $user = null;

	/**
	 * Validate user data
	 *
	 * @access public
	 * @param array $errors
	 * @return bool $validated
	 */
	public function validate(&$errors = []) {
		$required_fields = ['username', 'firstname', 'lastname', 'email'];
		foreach ($required_fields as $required_field) {
			if (!isset($this->details[$required_field]) OR $this->details[$required_field] == '') {
				$errors[$required_field] = 'required';
			}
		}

		if (count($errors) > 0) {
			return false;
		}

		if (isset($this->details['repeat_password']) AND $this->details['password'] != $this->details['repeat_password']) {
			$errors['password'] = 'do not match';
		}

		// TODO: validate the e-mail address properly

		if ($this->id === null) {
			try {
				$user = self::get_by_username($this->details['username']);
				$errors['username'] = 'already exists';
			} catch (Exception $e) { }
		}

		if ($this->id === null) {
			try {
				$user = self::get_by_email($this->details['email']);
				$errors['email'] = 'already exists';
			} catch (Exception $e) { }
		}

		if (count($errors) > 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Set the user password
	 *
	 * @access public
	 * @param string $password
	 */
	public function set_password($password) {
		$this->details['password'] = password_hash($password, PASSWORD_DEFAULT);
		$this->save();
	}

	/**
	 * Fetch a user by username
	 *
	 * @access public
	 * @param string $username
	 * @return User $user
	 */
	public static function get_by_username($username) {
		$db = Database::Get();
		$id = $db->get_one('SELECT id FROM user WHERE username = ?', [$username]);

		if ($id === null) {
			throw new \Exception('User not found');
		}

		return self::get_by_id($id);
	}

	/**
	 * Fetch a user by email
	 *
	 * @access public
	 * @param string $email
	 * @return User $user
	 */
	public static function get_by_email($email) {
		$db = Database::Get();
		$id = $db->get_one('SELECT id FROM user WHERE email = ?', [$email]);

		if ($id === null) {
			throw new \Exception('User not found');
		}

		return self::get_by_id($id);
	}


	/**
	 * Authenticate a user
	 *
	 * @access public
	 * @throws Exception
	 * @param string $username
	 * @param string $password
	 * @return User $user
	 */
	public static function authenticate($username, $password) {
		$user = self::get_by_username($username);

		if (password_verify($password, $user->password) === false) {
			return false;
		}

		// If we got here, we can assume the password is correct. If the password
		// is still using a weak hash, we can rehash it.
		if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
			$user->set_password($password);
		}

		return $user;
	}

	/**
	 * Get the current user
	 *
	 * @access public
	 * @return User $user
	 */
	public static function get() {
		if (self::$user !== null) {
			return self::$user;
		}

		throw new \Exception('No user set');
	}

	/**
	 * Set the current user
	 *
	 * @access public
	 * @param User $user
	 */
	public static function set(User $user) {
		self::$user = $user;
	}
}

