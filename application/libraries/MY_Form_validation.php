<?php

class MY_Form_validation extends CI_Form_validation {

	protected $ci;

	function __construct()
	{
		parent::__construct();

		$this->ci =& get_instance();
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
	 * @param	string
	 * @param	field
	 * @return	bool
	 */
	public function unique($str, $field)
	{
		list($table, $column) = explode(',', $field, 2);

		$this->ci->form_validation->set_message('unique', 'The %s that you requested is already in use.');

		$query = $this->ci->db->query("SELECT COUNT(*) AS dupe FROM {$this->ci->db->dbprefix($table)} WHERE {$column} = '{$str}'");
		$row = $query->row();
		
		return ($row->dupe > 0) ? FALSE : TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Check if a specific value is in use except when the value is attached to a specific row ID
	 *
	 * @param	string
	 * @param	field
	 * @return	bool
	 */
	public function unique_exclude($str, $field)
	{

		list($table, $column, $fld, $id) = explode(',', $field, 4);

		$this->ci->form_validation->set_message('unique_exclude', 'The %s that you requested is already in use.');

		$query = $this->ci->db->query("SELECT COUNT(*) AS dupe FROM {$this->ci->db->dbprefix($table)} WHERE {$column} = '$str' AND {$fld} <> {$id}");
		$row = $query->row();

		return ($row->dupe > 0) ? FALSE : TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Login
	 *
	 * @param   $str
	 * @return  bool
	 */
	public function login($str)
	{
		$this->ci->form_validation->set_message('login', 'Unable to log you in with the provided credentials.');

		$user = $this->ci->auth->login($this->ci->input->get_post('username'), $this->ci->input->get_post('password'), $this->ci->input->get_post('remember'));

		return ($user === FALSE) ? FALSE : TRUE;
	}

}
