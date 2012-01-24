<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['auth_cookie_expire'] = 432000; // Seconds to keep the cookie. 60 = 1 minute. Set to 0 to keep cookie only for browser session. Currently set to 5 days

$config['auth_table_users'] = 'users';
$config['auth_users_fields'] = array(
	'id' => 'id',
	'role_id' => 'role_id',
	'email' => 'email',
	'username' => 'username',
	'password' => 'password',
	'first_name' => 'first_name',
	'last_name' => 'last_name',
	'date_last_login' => 'date_last_login',
	'date_created' => 'date_created',
	'active' => 'active'
);

$config['auth_table_user_meta'] = 'user_meta';
$config['auth_user_meta_fields'] = array(
	'id' => 'id',
	'user_id' => 'user_id'
);

$config['auth_user_session_key'] = 'user_id';

$config['auth_encryption'] = 'md5';