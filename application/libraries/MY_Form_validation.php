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
class MY_Form_validation extends CI_Form_validation {

	protected $CI;

	function __construct()
	{
		parent::__construct();

		$this->CI =& get_instance();
	}

	// ------------------------------------------------------------------------

	/**
	 * Inject an additional validation rule on to validation rule that has already been set
	 *
	 * @access	public
	 * @param	string
	 * @param	field
	 * @return	bool
	 */
	public function inject_rule($field, $rules = '', $label = '')
	{
		// No reason to set rules if we have no POST data
		if (count($_POST) == 0)
		{
			return $this;
		}

		// No fields? Nothing to do...
		if ( ! is_string($field) OR  ! is_string($rules) OR $field == '')
		{
			return $this;
		}

		// Make sure the field has been set up already. If If not, create it instead of inject new rules
		if ( ! isset($this->_field_data[$field]))
		{
			$this->set_rules($field, $label, $rules);
		}
		// Rule exists, append the new rule
		else
		{
			$rules = $this->_field_data[$field]['rules'];

			foreach ((array)$rules as $rule)
			{
				if (strlen ($rules))
				{
					$rules .= '|';
				}
				$rules .= $rule;
			}

			$this->_field_data[$field]['rules'] = $rules;
		}

		return $this;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if a specific value is in use
	 *
	 * @param   string  $str
	 * @param   string  $field
	 * @return  bool
	 */
	public function unique($str, $field)
	{
		list($table, $column) = explode(',', $field, 2);

		$this->CI->form_validation->set_message('unique', 'The %s that you requested is already in use.');

		return $this->CI->auth_model->is_unique($table, $column, $str);
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if a specific value is in use except when the value is attached to a specific row ID
	 *
	 * @param   string  $str
	 * @param   string  $field
	 * @return  bool
	 */
	public function unique_except($str, $field)
	{
		list($table, $column, $fld, $id) = explode(',', $field, 4);

		$this->CI->form_validation->set_message('unique_except', 'The %s that you requested is already in use.');

		return $this->CI->auth_model->is_unique_except($table, $column, $str, $fld, $id);
	}

	// ------------------------------------------------------------------------

	/**
	 * Login
	 *
	 * @param   string  $str
	 * @return  bool
	 */
	public function login($str)
	{
		$this->CI->form_validation->set_message('login', 'Unable to log you in with the provided credentials.');

		$username = $this->CI->input->get_post('username');
		$password = $this->CI->input->get_post('password');
		$remember = $this->CI->input->get_post('remember');

		$user = $this->CI->auth->login($username, $password, $remember);

		return ($user) ? TRUE : FALSE;
	}

}
// END MY_Form_validation class

/* End of file MY_Form_validation.php */
/* Location: ./application/libraries/MY_Form_validation.php */