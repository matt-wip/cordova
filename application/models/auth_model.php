<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth_model extends Ion_auth_model {

	function __construct()
	{
		parent::__construct();
	}

  /**
   * Force Login
   *
   * WARNING: Do not, I repeat, DO NOT use this function unless you
   * have some form of authentication in place. Please read the
   * description below carefully before using.
   *
   * This function logs in a user without supplying a password.
   * The purpose of this function is to login a user after an external
   * means of authentication has completed. For example, if a user
   * would like to login using their University credentials, then
   * they can first authenticate through the University's servers. Then
   * if authentication is successful this function can be used to
   * complete the login process for that user.
   * 
   * Using this function without prior authentication can cause
   * serious security risks. If your user has a password stored in
   * the local database, then you should use Ion Auth's login()
   * function instead.
   * 
   * NOTE: This is a modified version of Ion Auth's native function
   *       called login() originally written by Mathew.
   * 
	 * @author Mathew
   * @author Sean Ephraim
   * @access public
   * @param  boolean  TRUE on success, else FALSE
   */
	public function force_login($identity, $remember=FALSE)
	{
		$this->trigger_events('pre_login');

		if (empty($identity))
		{
			$this->set_error('login_unsuccessful');
			return FALSE;
		}

		$this->trigger_events('extra_where');

		$query = $this->db->select($this->identity_column . ', username, email, id, password, active, last_login')
		                  ->where($this->identity_column, $this->db->escape_str($identity))
		                  ->limit(1)
		                  ->get($this->tables['users']);

		if($this->is_time_locked_out($identity))
		{
			//Hash something anyway, just to take up time
			$this->hash_password($identity);

			$this->trigger_events('post_login_unsuccessful');
			$this->set_error('login_timeout');

			return FALSE;
		}

		if ($query->num_rows() === 1)
		{
			$user = $query->row();

			if ($user->active == 0)
			{
				$this->trigger_events('post_login_unsuccessful');
				$this->set_error('login_unsuccessful_not_active');

				return FALSE;
			}

            // 'admin' should NEVER be authenticated without a password
			if ($user->username == 'admin')
			{
				$this->trigger_events('post_login_unsuccessful');
				$this->set_error('login_unsuccessful');

				return FALSE;
            }

			$this->set_session($user);

			$this->update_last_login($user->id);

			$this->clear_login_attempts($identity);

			if ($remember && $this->config->item('remember_users', 'ion_auth'))
			{
				$this->remember_user($user->id);
			}

			$this->trigger_events(array('post_login', 'post_login_successful'));
			$this->set_message('login_successful');

			return TRUE;
		}

		//Hash something anyway, just to take up time
		$this->hash_password($identity);

		$this->increase_login_attempts($identity);

		$this->trigger_events('post_login_unsuccessful');
		$this->set_error('login_unsuccessful');

		return FALSE;
	}

}

/* End of file uiowa_auth_model.php */
/* Location: ./application/models/external_auth/uiowa_auth_model.php */
