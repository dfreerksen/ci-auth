<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Auth {

	protected $ci;

	protected $_auth_cookie_expire = 0;

	protected $_auth_table_users = 'users';
	protected $_auth_users_fields = array(
		'id' => 'id',
		'role_id' => 'role_id',
		'email' => 'email',
		'username' => 'username',
		'password' => 'password',
		'name' => 'name',
		'last_login' => 'last_login',
		'date_created' => 'date_created',
		'active' => 'active'
	);
	protected $_auth_table_user_meta = 'user_meta';
	protected $_auth_user_meta_fields = array(
		'id' => 'id',
		'user_id' => 'user_id',
		'key' => 'key',
		'value' => 'value'
	);

	protected $_auth_key = 'user_id';

	/**
	 * Constructor
	 *
	 * @param   array   $config
	 */
	public function __construct($config = array())
	{
		$this->ci =& get_instance();

		// Load session library
		$this->ci->load->library('session');

		if ( ! empty($config))
		{
			$this->initialize($config);
		}

		log_message('debug', 'Auth Class Initialized');
	}

	// ------------------------------------------------------------------------

	/**
	 * Set config values
	 *
	 * @param   array   $config
	 */
	public function initialize($config = array())
	{
		if (count($config) > 0)
		{
			foreach ($config as $key => $val)
			{
				$this->__set($key, $val);
			}
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * get magic method. Returns NULL if not found
	 *
	 * @param   string  $name
	 * @return  mixed
	 */
	public function __get($name)
	{
		return isset($this->{'_' . $name}) ? $this->{'_' . $name} : NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * set magic method. Will only set the value if it is a known variable
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 */
	public function __set($name, $value)
	{
		if (isset($this->{'_' . $name}))
		{
			$this->{'_' . $name} = $value;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if user is logged in
	 *
	 * @return  bool
	 */
	public function logged_in()
	{
		$in = FALSE;

		// User is logged in with session
		if ($this->ci->session->userdata($this->_auth_key) !== FALSE)
		{
			$in = TRUE;
		}

		// User has a cookie set. Create session
		elseif ($this->ci->input->cookie($this->_auth_key) !== FALSE)
		{
			$user_id = $this->ci->input->cookie($this->_auth_key);
			$this->_set_session_values($user_id, TRUE);
			$in = TRUE;
		}

		return $in;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get the ID of the current user. Returns 0 if user is not logged in.
	 *
	 * @return  int
	 */
	public function user_id()
	{
		if ($this->logged_in())
		{
			return $this->ci->session->userdata($this->_auth_key);
		}

		return 0;
	}

	// ------------------------------------------------------------------------

	/**
	 * Login. Returns user ID or FALSE if user cannot be logged in
	 *
	 * @param   string      $username
	 * @param   string      $password
	 * @param   bool        $cookie
	 * @return  int|bool
	 */
	public function login($username = '', $password = '', $cookie = FALSE)
	{
		$user_id = FALSE;

		// Load email helper
		$this->ci->load->helper('email');

		// Query
		$this->ci->db->select($this->_auth_users_fields['id'].' as id')
			->from($this->_auth_table_users);

		if (valid_email($username))
		{
			$this->ci->db->where($this->_auth_users_fields['email'], $username);
		}
		else
		{
			$this->ci->db->where($this->_auth_users_fields['username'], $username);
		}

		$password = $this->_hash_password($password);

		$this->ci->db->where($this->_auth_users_fields['password'], $password);

		$query = $this->ci->db->get();

		if ($query->num_rows() > 0)
		{
			$result = $query->row_array();

			$user_id = $result['id'];
		}

		if ($user_id !== FALSE)
		{
			// Set the session
			$this->_set_session_values($user_id, $cookie);

			// Data for the update
			$data = array(
				$this->_auth_users_fields['last_login'] => date('Y-m-d H:i:s')
			);

			// Update the database with the last login time
			$this->ci->db->where($this->_auth_users_fields['id'], $user_id)
				->update($this->_auth_table_users, $data);
		}

		return $user_id;
	}

	// ------------------------------------------------------------------------

	/**
	 * Log the current user out. This kills the current users session and cookie
	 *
	 * @return  bool
	 */
	public function logout()
	{
		if ($this->ci->input->cookie($this->_auth_key) !== FALSE)
		{
			$cookie = array(
				'name' => $this->_auth_key,
				'value' => $this->ci->input->cookie($this->_auth_key),
				'expire' => ''
			);
			$this->ci->input->set_cookie($cookie);
		}

		$this->ci->session->sess_destroy();

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Create a new user
	 *
	 * @param   string  $username
	 * @param   string  $password
	 * @param   string  $email
	 * @param   string  $name
	 * @param   int     $role_id
	 * @param   int     $active
	 * @return  int
	 */
	public function create_user($username = '', $password = '', $email = '', $name = 'New User', $role_id = 1, $active = 1)
	{
		$data = array(
			$this->_auth_users_fields['role_id'] => 1,
			$this->_auth_users_fields['email'] => '',
			$this->_auth_users_fields['username'] => '',
			$this->_auth_users_fields['password'] => '',
			$this->_auth_users_fields['name'] => 'New User',
			$this->_auth_users_fields['active'] => 1,
		);

		// If each value passed, add to data
		if ( ! is_array($username))
		{
			$username = array(
				$this->_auth_users_fields['username'] => $username,
				$this->_auth_users_fields['password'] => $password,
				$this->_auth_users_fields['email'] => $email,
				$this->_auth_users_fields['name'] => $name,
				$this->_auth_users_fields['role_id'] => $role_id,
				$this->_auth_users_fields['active'] => $active
			);
		}

		$data = array_merge($data, array_filter($username));

		$password = $this->_hash_password($data['password']);

		$data[$this->_auth_users_fields['password']] = $password;
		$data[$this->_auth_users_fields['date_create']] = date('Y-m-d H:i:s');

		$this->db->insert($this->_auth_table_users, $data);

		return $this->db->insert_id();
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete user from database.
	 *
	 * @param   int     $id
	 * @return  bool
	 */
	public function delete_user($id = 0)
	{
		// Delete data from users table
		$this->ci->db->where($this->_auth_users_fields['id'], $id)
			->delete($this->_auth_table_user_meta);

		// Delete data from user meta table
		$this->ci->db->where($this->_auth_user_meta_fields['user_id'], $id)
			->delete($this->_auth_table_users);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get general user information
	 *
	 * @param   int $id
	 * @return  array|bool
	 */
	public function get_user($id = 0)
	{
		// Build select string
		$select = '';
		foreach ($this->_auth_users_fields as $key => $field)
		{
			// Add a comma to separate the fields
			if ($select)
			{
				$select .= ', ';
			}

			$select .= $field.' as '.$key;
		}

		// Query
		$this->ci->db->select($select)
			->from($this->_auth_table_users)
			->where($this->_auth_users_fields['id'], $id);

		$query = $this->ci->db->get();

		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get user meta information. Returns FALSE if user does not have any meta information.
	 *
	 * @param   int         $id
	 * @return  array|bool
	 */
	public function get_user_meta($id = 0)
	{
		$this->ci->db->select("{$this->_auth_user_meta_fields['key']} as key, {$this->_auth_user_meta_fields['value']} as value")
			->from($this->_auth_table_user_meta)
			->where($this->_auth_user_meta_fields['user_id'], $id);

		$query = $this->ci->db->get();

		if ($query->num_rows() > 0)
		{
			$result = array();

			foreach ($query->result_array() as $item)
			{
				$result[$item['key']] = $item['value'];
			}
			return $result;
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get user ID by username. Return FALSE if not found
	 *
	 * @param   string      $username
	 * @return  int|bool
	 */
	public function get_by_username($username = '')
	{
		// Load email helper
		$this->ci->load->helper('email');

		// Query
		$this->ci->db->select($this->_auth_users_fields['id'].' as id')
			->from($this->_auth_table_users);

		if (valid_email($username))
		{
			$this->ci->db->where($this->_auth_users_fields['email'], $username);
		}
		else
		{
			$this->ci->db->where($this->_auth_users_fields['username'], $username);
		}

		$query = $this->ci->db->get();

		if ($query->num_rows() > 0)
		{
			$result = $query->row_array();

			return $result['id'];
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get user ID by their email address. Returns FALSE if not found.
	 *
	 * @param   string      $email
	 * @return  int|bool
	 */
	public function get_by_email($email = '')
	{
		return $this->get_by_username($email);
	}

	// ------------------------------------------------------------------------

	/**
	 * Update user
	 *
	 * @param   int     $id
	 * @param   array   $data
	 * @return  bool
	 */
	public function update_user($id, $data)
	{
		$user = array();
		$meta = array();

		foreach ($data as $key => $value)
		{
			// No need to update the key
			if ($key != $this->_auth_users_fields['id'])
			{
				// General user information
				if (in_array($key, array_values($this->_auth_users_fields)))
				{
					// Make sure the password is hashed if it exists
					if ($key == $this->_auth_users_fields['password'])
					{
						$user[$key] = $this->_hash_password($value);
					}

					// Everything else
					else
					{
						$user[$key] = $value;
					}
				}

				// Meta information
				else
				{
					$meta[] = array(
						$this->_auth_user_meta_fields['key'] => $key,
						$this->_auth_user_meta_fields['value'] => $value
					);
				}
			}
		}

		// Update user data
		if ( ! empty($user))
		{
			$this->ci->db->where($this->_auth_users_fields['id'], $id)
				->update($this->_auth_table_users, $user);
		}

		// Update user meta data
		if ( ! empty($meta))
		{
			$this->ci->db->update_batch($this->_auth_table_user_meta, $meta, $this->_auth_user_meta_fields['key']);
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Change user password
	 *
	 * @param   int             $id
	 * @param   string|null     $password
	 * @return  bool
	 */
	public function change_password($id = 0, $password = NULL)
	{
		// Password wasn't passed. Generate one
		if ( ! $password)
		{
			$password = $this->_generate_password();
		}

		// Hash the password
		$password = array(
			$this->_auth_users_fields['password'] => $this->_hash_password($password)
		);

		// Update database
		$this->ci->db->where($this->_auth_users_fields['id'], $id)
			->update($this->_auth_table_users, $password);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Activate a user account
	 *
	 * @param   int     $id
	 * @return  bool
	 */
	public function activate_user($id)
	{
		$active = array(
			$this->_auth_users_fields['active'] => 1
		);

		$this->ci->db->where($this->_auth_users_fields['id'], $id)
			->update($this->_auth_table_users, $active);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Deactivate a user account
	 *
	 * @param   int     $id
	 * @return  bool
	 */
	public function deactivate_user($id)
	{
		$active = array(
			$this->_auth_users_fields['active'] => 0
		);

		$this->ci->db->where($this->_auth_users_fields['id'], $id)
			->update($this->_auth_table_users, $active);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Generate a random password (not hashed)
	 *
	 * @param   int     $min
	 * @param   int     $max
	 * @param   bool    $upper
	 * @param   bool    $num
	 * @param   bool    $special
	 * @return  string
	 */
	public function generate_password($min = 6, $max = 12, $upper = TRUE, $num = TRUE, $special = FALSE)
	{
		return $this->_generate_password($min, $max, $upper, $num, $special);
	}

	// ------------------------------------------------------------------------

	/**
	 * Generate a random password (not hashed)
	 *
	 * @param int $min
	 * @param int $max
	 * @param bool $upper
	 * @param bool $num
	 * @param bool $special
	 * @return string
	 */
	private function _generate_password($min = 6, $max = 12, $upper = TRUE, $num = TRUE, $special = FALSE)
	{
		$str = '';

		$pool = 'abcdefghijklmnopqrstuvwxyz';

		if($upper === TRUE)
		{
			$pool .= strtoupper($pool);
		}

		if($num === TRUE)
		{
			$pool .= '1234567890';
		}

		if($special === TRUE)
		{
			$pool .= '!@#$%&?';
		}

		$random_length = rand($min, $max);

		for ($i = 0; $i < $random_length; $i++)
		{
			$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
		}

		return $str;
	}

	// ------------------------------------------------------------------------

	/**
	 * Set session for user. Create cookie if needed
	 *
	 * @param   int     $user_id
	 * @param   bool    $cookie
	 */
	private function _set_session_values($user_id = 0, $cookie = FALSE)
	{
		if ($cookie === TRUE)
		{
			$cookie = array(
				'name' => $this->_auth_key,
				'value' => $user_id,
				'expire' => $this->_auth_cookie_expire,
				'domain' => $this->ci->config_item('cookie_domain'),
				'path' => $this->ci->config_item('cookie_path'),
				'secure' => $this->ci->config_item('cookie_secure'),
				'prefix' => $this->ci->config_item('cookie_prefix'),
			);

			$this->ci->input->set_cookie($cookie);
		}

		$this->ci->session->set_userdata($this->_auth_key, $user_id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Hash password string
	 *
	 * @param   string  $password
	 * @return  string
	 */
	private function _hash_password($password = '')
	{
		$this->ci->load->library('encrypt');

		return $this->ci->encrypt->sha1($this->ci->config->item('encryption_key').$password);
	}

}
