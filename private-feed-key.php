<?php
/* 
Plugin Name: Private Feed Key
Plugin URI:  http://www.poeticcoding.co.uk/plugin/private-feed-key
Description: Private Feed Key adds a 32bit (or 40bit) key for each of your users, creating a unique feed url for every registered on user the site. This allows you to restrict you feeds to registered users only.
Version: 0.1
Author: Poetic Coding
Author URI: http://www.poeticcoding.co.uk
License: GPL2
*/ 

class PrivateFeedKey {
	const VERSION = '0.1';
	private $_settings;
	private $_optionsName = 'PrivateFeedKey';
	private $_optionsGroup = 'PrivateFeedKey-options';
	private $_valid = false;
	
	public function __construct() {
		$this->_getSettings();
		if(is_admin()) {
			add_action('admin_init', array($this, 'registerOptions'));
			add_action('admin_menu', array($this,'adminMenu'));
		}
		add_action('init', array($this, 'prepare'));
		add_action('template_redirect', array($this, 'templateRedirect'));
		add_action('show_user_profile', array($this, 'displayKey'));
		add_action('edit_user_profile', array($this, 'displayKey'));
		add_action('profile_update', array($this, 'resetKey'));
		register_activation_hook(__FILE__, array($this, 'activatePlugin'));
		register_deactivation_hook(__FILE__, array($this, 'deactivatePlugin'));
	}
	
	public function registerOptions() {
		register_setting($this->_optionsGroup, $this->_optionsName);
	}
	
	public function activatePlugin() {
		update_option($this->_optionsName, $this->_settings);
	}
	
	public function deactivatePlugin() {
		delete_option($this->_optionsName);
	}
	
	public function getSetting( $settingName, $default = false ) {
		if (empty($this->_settings)) {
			$this->_getSettings();
		}
		if ( isset($this->_settings[$settingName]) ) {
			return $this->_settings[$settingName];
		} else {
			return $default;
		}
	}
	
	private function _getSettings() {
		if(empty($this->_settings)) {
			$this->_settings = get_option($this->_optionsName);
		}
		if(!is_array($this->_settings)) {
			$this->_settings = array();
		}
		$defaults = array(
			'version'	=>	self::VERSION,
			'char_set'	=>	'alpha-numberic-mixed',
			'key_length'=>	'32',
			'salt'		=>	'username',
			'hash_using'=>	'md5',
			'user_reset'=>	TRUE
		);
		$this->_settings = wp_parse_args($this->_settings, $defaults);
	}
	
	public function adminMenu() {
		add_options_page('Private Feed Key', 'Private Feed Key', 'manage_options', 'PrivateFeedKey', array($this, 'options'));
	}
	
	public function templateRedirect() {
		if(is_feed()) {
			if(empty($_GET['feedkey']) || $this->_valid == FALSE) {
				header('HTTP/1.0 401 Unauthorized');
				exit;
			}
		}
	}
	
	public function prepare() {
		global $userdata, $wpdb;
		if(!is_user_logged_in()) {
			//Get User's Feed key
			$users_feedkey = get_user_meta($userdata->ID,'feed_key');
			
			//If there isn't one then generate one
			if (empty($users_feedkey))
			{
				$feedkey = $this->feedkey_gen();
				update_usermeta($userdata->ID, 'feed_key', $feedkey);
			}
		}
		
		$submitted_feedkey = $_GET['feedkey'];
		if(!empty($submitted_feedkey)) {
			$find_feedkey = $wpdb->get_row("SELECT umeta_id,user_id FROM wp_usermeta WHERE meta_value = '" . $wpdb->escape($submitted_feedkey) ."'");
			if (!empty($find_feedkey)) //If Feed Key is found
			{
				$this->_valid = TRUE;
				if(!is_user_logged_in()) {
					wp_set_current_user($find_feedkey->user_id);
					wp_set_auth_cookie($find_feedkey->user_id);
					
				}
			}
		}
	}
	
	private function feedkey_gen() {
		global $userdata;
		
		
		//Construct Character Set
		$charset = "0123456789"; //Numeric Character Set
		
		//Add rest of character set based on settings
		switch ($this->_settings['char_set'])
		{
			case 'alpha-numeric-lower':
				$charset .= 'abcdefghijklmnopqrstuvwxyz';
				break;
			case 'alpha-numeric-upper':
				$charset .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alpha-numeric-mix':
				$charset .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alpha-lower':
				$charset = 'abcdefghijklmnopqrstuvwxyz';
				break;
			case 'alpha-upper':
				$charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'alpha-mixed':
				$charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
				break;
			case 'numeric':
				break;
		}
		
		$keylength = $this->_settings['key_length']; //Key Length

		for ($i=0; $i<$keylength; $i++) 
		{
			$key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
		}
		
		//Choose salt being used to hash key against
		switch ($this->_settings['salt'])
		{
			case 'username':
				$salt = $userdata->user_login;
				break;
			case 'email':
				$salt = $userdata->user_email;
				break;
		}
		
		switch ($this->_settings['hash_using'])
		{
			case 'md5':
				$hashedkey = md5($salt.$key);
				break;
			case 'sha1':
				$hashedkey = sha1($salt.$key);
				break;
			case 'sha1_md5':
				$hashedkey = sha1(md5($salt.$key));
				break;
			case 'md5_sha1':
				$hashedkey = md5(sha1($salt.$key));
				break;
		}
		
		return $hashedkey;
	}
	
	public function options() {
	
		$optionarray_def = $this->_settings;
		$charset_types = array(
			'Alpha-Numeric (Lowercase)' => 'alpha-numeric-lower',
			'Alpha-Numeric (Uppercase)' => 'alpha-numeric-upper',
			'Alpha-Numeric (Mixed Case)' => 'alpha-numeric-mixed',
			'Alpha (Lowercase)' => 'alpha-lower',
			'Alpha (Uppercase)' => 'alpha-upper',
			'Alpha (Mixed)' => 'alpha-mixed',
			'Numeric' => 'numeric'
		);
		
		foreach ($charset_types as $option => $value) {
			if ($value == $optionarray_def['char_set']) {
					$selected = 'selected="selected"';
			} else {
					$selected = '';
			}
			
			$charset_options .= "\n\t<option value='$value' $selected>$option</option>";
		}
		
		// Setup Key Length Options
		$keylength_types = array(
			'8bit' => '8',
			'16bit' => '16',
			'32bit' => '32',
			'64bit' => '64',
			'128bit' => '128',
			'256bit' => '256'
		);
		
		foreach ($keylength_types as $option => $value) {
			if ($value == $optionarray_def['key_length']) {
					$selected = 'selected="selected"';
			} else {
					$selected = '';
			}
			
			$keylength_options .= "\n\t<option value='$value' $selected>$option</option>";
		}
		
		// Setup Salt Options
		$salt_types = array(
			'Username' => 'username',
			"eMail" => 'email'
		);
		
		foreach ($salt_types as $option => $value) {
			if ($value == $optionarray_def['salt']) {
					$selected = 'selected="selected"';
			} else {
					$selected = '';
			}
			
			$salt_options .= "\n\t<option value='$value' $selected>$option</option>";
		}
		
		// Setup Hash Options
		$hash_types = array(
			'md5' => 'md5',
			'sha1' => 'sha1',
			'sha1 then md5' => 'sha1_md5',
			'md5 then sha1' => 'md5_sha1'
		);
		
		foreach ($hash_types as $option => $value) {
			if ($value == $optionarray_def['hash_using']) {
					$selected = 'selected="selected"';
			} else {
					$selected = '';
			}
			
			$hash_options .= "\n\t<option value='$value' $selected>$option</option>";
		}
		
		?>
		<div class="wrap">
			<?php screen_icon('tools'); ?><h2>Private Feed Key</h2>
			<form method="post" action="options.php">
				<?php settings_fields($this->_optionsGroup); ?>
				<?php do_settings_sections($this->_optionsGroup); ?>
				<fieldset class="options" style="border: none">
					<p>
						<em>Private Feed Key</em> creates unique feed URLs for each of your users on your site by adding <em>Feed Keys</em> to the end of the exsisting feed url. <em>Feed Keys</em> are made unique by hashing the user's email or username against a random key, of which you can choose the length. You can also choose which algorithm to use to hash the salt and the key together by choosing either md5, sha1 or both (in either order).
					</p>
					<h3>Key Generation</h3>
					<table class="form-table">
						<tr valign="top">
							<th width="200px" scope="row">Character Set</th>
							<td width="100px"><select name="<?php echo $this->_optionsName; ?>[char_set]" id="<?php echo $this->_optionsName; ?>_char_set"><?php echo $charset_options ?></select></td>
							<td><span style="color: #555; font-size: .85em;">Choose which character set you want to use to make the input key <em>(Feed Keys are always alpha-numeric)</em></span></td>
						</tr>
						<tr valign="top">
							<th width="200px" scope="row">Input Key Length</th>
							<td width="100px"><select name="<?php echo $this->_optionsName; ?>[key_length]" id="<?php echo $this->_optionsName; ?>_key_length"><?php echo $keylength_options ?></select></td>
							<td><span style="color: #555; font-size: .85em;">Choose the length of the input key, before it gets hashed <em>(Feed Keys are either 32 or 40bit in length)</em></span></td>
						</tr>
						<tr valign="top">
							<th width="200px" scope="row">Salt</th>
							<td width="100px"><select name="<?php echo $this->_optionsName; ?>[salt]" id="<?php echo $this->_optionsName; ?>_salt"><?php echo $salt_options ?></select></td>
							<td><span style="color: #555; font-size: .85em;">To ensure the key is unique, choose what user info to use as the 'salt' when hashing the input key</span></td>
						</tr>
						<tr valign="top">
							<th width="200px" scope="row">Algorithm</th>
							<td width="100px"><select name="<?php echo $this->_optionsName; ?>[hash_using]" id="<?php echo $this->_optionsName; ?>_hash_using"><?php echo $hash_options ?></select></td>
							<td><span style="color: #555; font-size: .85em;">Choose how which algorithm to hash the 'salt' and key with. md5 <em>(and sha1 followed by md5)</em> will result in a 32bit <em>Feed Key</em>, sha1 <em>(and md5 followed by sha1)</em> will result in a 40bit <em>Feed Key</em>.</span></td>
						</tr>
					</table>
					<h3>Feed Key Reset</h3>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">User Reset</th>
							<td><input name="<?php echo $this->_optionsName; ?>[user_reset]" type="checkbox" id="<?php echo $this->_optionsName; ?>_user_reset" value="1" <?php checked('1', $optionarray_def['user_reset']); ?> /></td>
							<td><span style="color: #555; font-size: .85em;">Choose whether users can reset their own <em>Feed Key</em>, otherwise only admins can reset <em>Feed Keys</em></span></td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</fieldset>
			</form>
		</div>
		<?php
	}
	
	public function displayKey() {
		global $profileuser, $current_user;
		
		// Setup Feed Key Reset Options
		$feedkey_reset_types = array(
		'Feed Key Options...' => NULL,
		'Reset Feed Key' => 'feedkey-reset',
		'Remove Feed Key' => 'feedkey-remove'
		);
		
		foreach ($feedkey_reset_types as $option => $value) {
			if ($value == $optionarray_def['login_redirect_to']) {
					$selected = 'selected="selected"';
			} else {
					$selected = '';
			}
			
			$feedkey_reset_options .= "\n\t<option value='$value' $selected>$option</option>";
		}
		
		$yourprofile = $profileuser->ID == $current_user->ID;
		$feedkey = get_user_meta($profileuser->ID,'feed_key');
		$permalink_structure = get_option(permalink_structure);
		
		//Check if Permalinks are being used
		empty($permalink_structure) ? $feedjoin = '?feed=rss2&feedkey=' : $feedjoin = '/feed/?feedkey=';
		$blogurl = get_bloginfo('url');
		$feedurl = $blogurl.$feedjoin.$feedkey[0];
		$feedurl = '<a href="'.$feedurl.'">'.$feedurl.'</a>';

		?>
		<table class="form-table">
			<h3>Your Private Feed Key</h3>
			<tr>
				<th><label for="feedkey">Feed Key</label></th>
				<td width="250px"><?php echo empty($feedkey) ? _e($errormsg['feedkey_notgen']) : _e($feedkey[0]); ?></td>
				<td>
				<?php if ($this->_settings ['feedkey_reset'] == TRUE && !$current_user->has_cap('level_9')) : ?>
					<input name="feedkey-reset" type="checkbox" id="feedkey-reset_inp" value="0" /> Reset Key
				<?php elseif ($current_user->has_cap('level_9')) : ?>
					<?php if (empty($feedkey)) : ?>
						<input name="feedkey-generate" type="checkbox" id="feedkey-generate_inp" value="0" /> Generate Key
					<?php else : ?>
						<select name="feedkey-reset-admin" id="feedkey-reset-admin"><?php echo $feedkey_reset_options ?></select>
					<?php endif; ?>
				<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="feedkey">Your Feed URL</label></th>
				<td colspan="2"><?php echo empty($feedkey) ? _e($errormsg['feedurl_notgen']) : _e($feedurl); ?></td>
			</tr>
		</table>
		<?php
	}
	
	public function resetKey($user_id) {
		if ($_POST['feedkey-reset'] != NULL || $_POST['feedkey-generate'] != NULL || $_POST['feedkey-reset-admin'] == 'feedkey-reset') {
			$feedkey = $this->feedkey_gen();
			update_usermeta($user_id, 'feed_key', $feedkey);
		}
		
		if ($_POST['feedkey-reset-admin'] == 'feedkey-remove') {
			$feedkey = NULL;
			update_usermeta($id, 'feed_key', $feedkey);
		}
	}
}

$PrivateFeedKey = new PrivateFeedKey();
?>