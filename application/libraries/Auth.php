<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter Auth Class
 *
 * This class enables users to log in, log out, and update their profile.
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Libraries
 * @author      David Freerksen
 * @link        https://github.com/dfreerksen/ci-auth
 */
class Auth {

	protected $CI;

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
		'user_id' => 'user_id'
	);

	protected $_auth_user_session_key = 'user_id';

	/**
	 * Constructor
	 *
	 * @param   array   $config
	 */
	public function __construct($config = array())
	{
		$this->CI =& get_instance();

		// Load session library
		$this->CI->load->library('session');

		// Load Auth model
		$this->CI->load->model('auth_model');

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
	public function __get($key)
	{
		return isset($this->{'_'.$key}) ? $this->{'_'.$key} : NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * set magic method. Will only set the value if it is a known variable
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		if (isset($this->{'_'.$key}))
		{
			$this->{'_'.$key} = $value;
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
		// User is logged in with session
		if ($this->CI->session->userdata($this->_auth_user_session_key) !== FALSE)
		{
			return TRUE;
		}

		// User has a cookie set. Create session
		elseif ($this->CI->input->cookie($this->_auth_user_session_key) !== FALSE)
		{
			$user_id = $this->CI->input->cookie($this->_auth_user_session_key);
			$this->_set_session_values($user_id, TRUE);

			return TRUE;
		}

		return FALSE;
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
			return $this->CI->session->userdata($this->_auth_user_session_key);
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
		// Hash password
		$password = $this->hash_password($password);

		$user_id = $this->CI->auth_model->valid_login($username, $password);

		if ($user_id)
		{
			// Set the session
			$this->_set_session_values($user_id, $cookie);

			// Update the database with the last login time
			$this->CI->auth_model->update_last_login($user_id);
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
		if ($this->CI->input->cookie($this->_auth_user_session_key) !== FALSE)
		{
			$cookie = array(
				'name' => $this->_auth_user_session_key,
				'value' => $this->CI->input->cookie($this->_auth_user_session_key),
				'expire' => ''
			);

			$this->CI->input->set_cookie($cookie);
		}

		$this->CI->session->sess_destroy();

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
				$this->_auth_users_fields['role_id'] => $role_id,
				$this->_auth_users_fields['email'] => $email,
				$this->_auth_users_fields['username'] => $username,
				$this->_auth_users_fields['password'] => $password,
				$this->_auth_users_fields['name'] => $name,
				$this->_auth_users_fields['active'] => $active
			);
		}

		$data = array_merge($data, array_filter($username));

		$password = $this->hash_password($username[$this->_auth_users_fields['password']]);

		$data[$this->_auth_users_fields['password']] = $password;
		$data[$this->_auth_users_fields['date_created']] = date('Y-m-d H:i:s');

		$this->CI->db->insert($this->_auth_table_users, $data);

		return $this->CI->db->insert_id();
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
		$this->CI->auth_model->delete_user($id);

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
		return $this->CI->auth_model->get_user($id);
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
		return $this->CI->auth_model->get_user_meta($id);
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
		return $this->CI->auth_model->get_by_username_email($username);
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
		return $this->CI->auth_model->update_user($id, $data);
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
			$password = $this->generate_password();
		}

		// Hash the password
		$hash = $this->hash_password($password);

		return $this->CI->auth_model->update_password($id, $hash);
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
		return $this->CI->auth_model->activate_deactivate_user($id, 1);
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
		return $this->CI->auth_model->activate_deactivate_user($id, 0);
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
		$str = '';

		$pool = 'abcdefghijklmnopqrstuvwxyz';

		if ($upper === TRUE)
		{
			$pool .= strtoupper($pool);
		}

		if ($num === TRUE)
		{
			$pool .= '1234567890';
		}

		if ($special === TRUE)
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
	 * Hash password string
	 *
	 * @param   string  $password
	 * @return  string
	 */
	public function hash_password($password = '')
	{
		// Load encrypt library
		$this->CI->load->library('encrypt');

		return $this->CI->encrypt->sha1($this->CI->config->item('encryption_key').$password);
	}

	// ------------------------------------------------------------------------

	/**
	 * Set session for user. Create cookie if needed
	 *
	 * @param   int     $user_id
	 * @param   bool    $cookie
	 * @return void
	 */
	private function _set_session_values($user_id = 0, $cookie = FALSE)
	{
		if ($cookie === TRUE)
		{
			$cookie = array(
				'name' => $this->_auth_user_session_key,
				'value' => $user_id,
				'expire' => $this->_auth_cookie_expire
			);

			$this->CI->input->set_cookie($cookie);
		}

		$this->CI->session->set_userdata($this->_auth_user_session_key, $user_id);
	}

}
// END Auth class

/* End of file Auth.php */
/* Location: ./application/libraries/Auth.php */