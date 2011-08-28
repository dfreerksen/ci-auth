<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['auth_cookie_expire'] = 432000; // Seconds to keep the cookie. 60 = 1 minute. Set to 0 to keep cookie only for browser session. Currently set to 5 days

$config['auth_table_users'] = 'users';
$config['auth_table_roles'] = 'roles';
$config['auth_table_permissions'] = 'permissions';
$config['auth_table_users_meta'] = 'user_meta';
$config['auth_table_role_permissions'] = 'role_permissions';

$config['auth_key'] = 'user_id';