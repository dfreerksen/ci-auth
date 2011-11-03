# Huh?
Simple user login, logout, and user management class.


# Installing
* Copy/move files into place
    * /application/config/auth.php
    * /application/libraries/Auth.php
    * /application/libraries/MY_Form_validation.php
    * /application/models/auth_model.php
* Autoload database library (/application/config/autoload.php)
* Autoload Auth library (/application/config/autoload.php)
    * No need to autoload Session library as Auth library takes care of that

This library uses the active record classes. So make sure _$active_record_ is set to _TRUE_ in your
/application/config/database.php file.

Table prefixes are also taken into account from the _dbprefix_ setting in /application/config/database.php


# Database
You are able to have your table and fields named however you like. Those modifications will need to be reflected in the
acl.php config file (more on that in the _Configuration_ section). Making those changes directly to the Acl.php library
file is not recommended as it makes it more difficult to update the library later on when updates to the library are
available. If you decide to change the table or field names, the __minimum required__ tables and fields should look
something similar to the following:

    - users
        - user_id
        - role_id
        - email
        - username
        - password
        - name
        - last login
        - date_created
        - active
    - user_meta
        - user_meta_id
        - user_id


# Configuration
All configuration is set in the /application/config/auth.php config file.

* **auth_table_users**
    * Name of the database table where users are stored

* **auth_users_fields**
    * Field names where user information is housed
        * id
             * Unique ID for user
        * role_id
             * Role ID of user
        * email
             * Email address. Must be unique
        * username
             * Username. Must be unique
        * password
             * Password. Salt and SHA1 encrypted
        * name
             * Name of use
        * last_login
             * Last date/time the user logged in
        * date_created
             * Date/time the user account was created
        * active
             * Active or inactive status

* **auth_table_user_meta**
    * Name of the database table where additional user data is stored. Table is set up in EAV structure

* **auth_user_meta_fields**
    * Field names where additional user information is housed
        * id
            * Unique ID of user meta
        * user_id
            * Unique ID of user

* **auth_user_session_key**
    * Name of the session key that stores the user ID

* **auth_cookie_expire**
	* Seconds to keep the cookie. 60 = 1 minute. Set to 0 to keep cookie only for browser session.


# Name
So what's the name of this fancy thing? It's doesn't have a name. I've just been calling it Auth. If you have a fancy
name to call it, let me know what it is.


# TODO
* Cache database queries
* Log in with social account (Facebook, Twiter, Google+, Linkedin, CommonRed)