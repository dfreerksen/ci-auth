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
class Auth_model extends CI_Model {

	/**
	 * Create new user
	 *
	 * @param   array   $user_data
	 * @return  int
	 */
	public function create_user($user_data = array())
	{
		// Create user record
		$this->db->insert($this->auth->auth_table_users, $user_data);

		// Unique user ID
		$id = $this->db->insert_id();

		// Create user meta record
		$user_meta_data = array(
			$this->auth->auth_user_meta_fields['user_id'] => $id
		);

		$this->db->insert($this->auth->auth_table_user_meta, $user_meta_data);

		return $id;
	}

	// ------------------------------------------------------------------------

	/**
	 * Activate/deactivate a user
	 *
	 * @param   int   $id
	 * @param   int $active
	 * @return  bool
	 */
	public function activate_deactivate_user($id = 0, $active = 1)
	{
		$activate = array(
			$this->auth->auth_users_fields['active'] => $active
		);

		$this->db->where($this->auth->auth_users_fields['id'], $id)
			->update($this->auth->auth_table_users, $activate);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Update password for user
	 *
	 * @param   int     $id
	 * @param   string  $pass
	 * @return  bool
	 */
	public function update_password($id = 0, $pass = '')
	{
		$password = array(
			$this->auth->auth_users_fields['password'] => $pass
		);

		// Update database
		$this->db->where($this->auth->auth_users_fields['id'], $id)
			->update($this->auth->auth_table_users, $password);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get user by their username or email
	 *
	 * @param   $username
	 * @return  bool
	 */
	public function get_by_username_email($username)
	{
		// Load email helper
		$this->load->helper('email');

		// Query
		$this->db->select($this->auth->auth_users_fields['id'].' as id')
			->from($this->auth->auth_table_users);

		$field = (valid_email($username)) ? 'email' : 'username';

		$this->db->where($this->auth->auth_users_fields[$field], $username);

		$query = $this->db->get();

		if ($query->num_rows() > 0)
		{
			$result = $query->row_array();

			return $result['id'];
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Test if valid login
	 * @param   string  $username
	 * @param   string  $password
	 * @return  bool
	 */
	public function valid_login($username = '', $password = '')
	{
		// Load email helper
		$this->load->helper('email');

		// Default user id
		$user_id = FALSE;

		// Query
		$this->db->select($this->auth->auth_users_fields['id'].' as id')
			->from($this->auth->auth_table_users)
			->where($this->auth->auth_users_fields['password'], $password);

		$field = (valid_email($username)) ? 'email' : 'username';

		$this->db->where($this->auth->auth_users_fields[$field], $username);

		$query = $this->db->get();

		if ($query->num_rows() > 0)
		{
			$result = $query->row_array();

			$user_id = $result['id'];
		}

		return $user_id;
	}

	// ------------------------------------------------------------------------

	/**
	 * Update the last login date/time of user
	 *
	 * @param   int $id
	 * @return  void
	 */
	public function update_last_login($id = 0)
	{
		// Data for the update
		$data = array(
			$this->auth->auth_users_fields['date_last_login'] => date('Y-m-d H:i:s')
		);

		// Update the database with the last login time
		$this->db->where($this->auth->auth_users_fields['id'], $id)
			->update($this->auth->auth_table_users, $data);
	}

	// ------------------------------------------------------------------------

	/**
	 * Get general user information
	 *
	 * @param   int $id
	 * @return  bool
	 */
	public function get_user($id = 0)
	{
		// Build select string
		foreach ($this->auth->auth_users_fields as $key => $field)
		{
			$this->db->select($field.' as '.$key);
		}

		// Query
		$this->db->from($this->auth->auth_table_users)
			->where($this->auth->auth_users_fields['id'], $id);

		$query = $this->db->get();

		if ($query->num_rows() > 0)
		{
			return $query->row_array();
		}

		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get extended user information
	 *
	 * @param   int $id
	 * @return  bool
	 */
	public function get_user_meta($id = 0)
	{
		$this->db->select('*')
			->from($this->auth->auth_table_user_meta)
			->where($this->auth->auth_user_meta_fields['user_id'], $id);

		$query = $this->db->get();

		$meta = array();

		// Remove unique ID and user ID fields
		foreach ($query->row_array() as $item)
		{
			// We don't care about the meta unique id or the user id
			if ( ! in_array($item['key'], array_values($this->auth->auth_user_meta_fields)))
			{
				$meta[$item['key']] = $item['value'];
			}
		}

		if (empty($meta))
		{
			return FALSE;
		}

		return $meta;
	}

	// ------------------------------------------------------------------------

	/**
	 * Update user information
	 *
	 * @param   int $id
	 * @param   array   $data
	 * @return  bool
	 */
	public function update_user($id = 0, $data = array())
	{
		$user = array();
		$meta = array();

		foreach ($data as $key => $value)
		{
			// No need to update the key
			if ($key != $this->auth->auth_users_fields['id'])
			{
				// General user information
				if (in_array($key, array_values($this->auth->auth_users_fields)))
				{
					// Make sure the password is hashed if it exists
					if ($key == $this->auth->auth_users_fields['password'])
					{
						$user[$key] = $this->auth->hash_password($value);
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
					$meta[$key] = $value;
				}
			}
		}

		// Update user data
		if ( ! empty($user))
		{
			$this->db->where($this->auth->auth_users_fields['id'], $id)
				->update($this->auth->auth_table_users, $user);
		}

		// Update user meta data
		if ( ! empty($meta))
		{
			$this->db->where($this->auth->auth_users_fields['id'], $id)
				->update($this->auth->auth_table_user_meta, $meta);
		}

		return TRUE;
	}

}
// END Auth_model class

/* End of file auth_model.php */
/* Location: ./application/models/auth_model.php */