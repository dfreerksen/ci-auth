<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

	protected $CI;

	function __construct()
	{
		parent::__construct();

		$this->CI =& get_instance();
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if a specific value is in use except when the value is attached to a specific row ID
	 *
	 * @param   string  $str
	 * @param   string  $field
	 * @return  bool
	 */
	public function is_unique_except($str, $field)
	{
		list($table, $column, $fld, $id) = explode('.', $field, 4);

		$this->CI->form_validation->set_message('unique_except', 'The %s that you requested is already in use.');

		$this->db->select('*')
			->from($table)
			->where($column, $str)
			->where($fld.' !=', $id);

		$count = $this->db->count_all_results();

		return ($count) ? FALSE : TRUE;
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