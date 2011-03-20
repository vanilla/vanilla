<?php
/**
 * Single Sign-on functions.
 */

$VanillaCookieName = vf_get_option('vanilla_cookie_name');
if (empty($VanillaCookieName))
   $VanillaCookieName = 'Vanilla'; // <-- You might need to change this if you've customized your cookie name in Vanilla.
   
define('VF_COOKIE', $VanillaCookieName); 

/**
 * SSO administration page.
 */
function vf_sso_admin_page() {
	if (!current_user_can('manage_options'))
		wp_die(__('You do not have sufficient permissions to access this page.'));
  
	$options = get_option(VF_OPTIONS_NAME);
	$cookie_domain = vf_get_value('vanilla_cookie_domain', $options, '');
	
	$Key = vf_get_value('vanilla_sso_key', $options, '');
	if ($Key == '') {
		$Characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
      $Key = '' ;
      for ($i = 0; $i < 16; ++$i) {
        $Offset = rand() % 35;
        $Key .= substr($Characters, $Offset, 1);
      }
		vf_update_option('vanilla_sso_key', $Key);
	}
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2><?php _e('Vanilla Single Sign-on Configuration'); ?></h2>
	<h3><?php _e('Vanilla Settings for WordPress'); ?></h3>
	<p>Grab this value from the single sign-on configuration screen in your Vanilla installation:</p>
	<?php vf_open_form('sso-form'); ?>
		<table class="form-table">
			<tr>
				<th>Vanilla's Cookie Domain</th>
				<td><input name="<?php echo vf_get_option_name('vanilla_cookie_domain'); ?>" value="<?php echo $cookie_domain; ?>" class="InputBox" /></td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="save" value="<?php _e('Save'); ?>" /></p>
	</form>
	<h3><?php _e('WordPress Settings for Vanilla'); ?></h3>
	<p>Copy & paste the following information into the single sign-on configuration screen in your Vanilla installation:</p>
	<table class="form-table">
		<tr>
			<th>Authenticate Url</th>
			<td><span class="description"><?php echo site_url('?VanillaChallengeKey='.$Key, 'vanilla-sso-info'); ?></span></td>
		</tr>
		<tr>
			<th>Registration Url</th>
			<td><span class="description"><?php echo site_url('wp-login.php?action=register', 'login'); ?></span></td>
		</tr>
		<tr>
			<th>Sign-in Url</th>
			<td><span class="description"><?php echo wp_login_url(); ?>?redirect_to={Redirect}</span></td>
		</tr>
		<tr>
			<th>Sign-out Url</th>
			<td><span class="description"><?php
				echo add_query_arg(array('action' => 'logout', '_wpnonce' => '{Nonce}', 'redirect_to' => '{Redirect}'), site_url('wp-login.php', 'login'));
			?></span></td>
		</tr>
	</table>
</div>
<?php
}

// Check to see if we should kill processing and display signed in user info
if (is_array($_GET)) {

   // allow the remote host to detect us
   header('X-ProxyConnect-Enabled: yes');
   $VanillaChallengeKey = vf_get_option('vanilla_sso_key');
   $SuppliedChallangeKey = vf_get_value('VanillaChallengeKey', $_GET, NULL);
   
   if (array_key_exists('ProxyConnectAutoconfigure', $_GET)) {
      $AutoconfigureTask = vf_get_value('Task', $_GET, 'Check');
      $Secure = TRUE;

      if ($VanillaChallengeKey == '') {
         header('X-Autoconfigure-Challenge: missing');
         $Secure = FALSE;
      }
      
      else if (!is_null($SuppliedChallangeKey) && $VanillaChallengeKey != $SuppliedChallangeKey) {
         header('X-Autoconfigure-Challenge: invalid');
         $Secure = FALSE;
      }
      
      switch ($AutoconfigureTask) {
         case 'Check':
            // Remote is simply seeing if we respond appropriately
         break;
         
         case 'Secure':
            $Key = vf_get_value('Challenge', $_GET, NULL);
            if (!is_null($Key)) {
               if (!is_null($VanillaChallengeKey) || $Secure) {
                  vf_update_option('vanilla_sso_key', $Key);
                  header('X-Autoconfigure-Challenge: set');
               }
            }
         break;
         
         case 'Exchange':
            if ($Secure) {
               $OutInfo = array(
                  "AuthenticateUrl" => site_url('?VanillaChallengeKey='.$VanillaChallengeKey, 'vanilla-sso-info'),
                  "RegisterUrl" => add_query_arg(array('action' => 'register'),site_url('wp-login.php', 'login')),
                  "SignInUrl" => add_query_arg(array('redirect_to' => '{Redirect}'), site_url('wp-login.php', 'login')),
                  "SignOutUrl" => add_query_arg(array('action' => 'logout', '_wpnonce' => '{Nonce}', 'redirect_to' => '{Redirect}'), site_url('wp-login.php', 'login')),
                  "PasswordUrl" => "not supported",
                  "ProfileUrl" => "not supported"
               );

               echo json_encode($OutInfo);
            }
         break;
         
         case 'Cookie':
            $VanillaCookieDomain = vf_get_option('vanilla_cookie_domain');
            if ($VanillaCookieDomain == '') 
               header('X-Autoconfigure-Cookie: missing');
            else 
               header('X-Autoconfigure-Cookie: set');
               
            if ($Secure) {
               $CookieDomain = vf_get_value('Cookie', $_GET, NULL);
               if (!is_null($CookieDomain)) {
                  vf_update_option('vanilla_cookie_domain', $CookieDomain);
                  header('X-Autoconfigure-Cookie: set');
               }
            }
         break;
      }
      
      exit();
   }

	if ($VanillaChallengeKey != '' && $VanillaChallengeKey == $_GET['VanillaChallengeKey']) {
		// If the challenge key was provided to WordPress, and it validates to the one saved in this system (and it's not empty)
		// Spit out some json_encoded data about the user for Vanilla to use when authenticating/creating user accounts
		global $current_user;
		if (!function_exists('get_currentuserinfo'))
			require (ABSPATH . WPINC . '/pluggable.php');
			
      get_currentuserinfo();
		// Only report user info if the user is authenticated
		if ($current_user->ID != '') { 
			echo "UniqueID=".$current_user->ID
				."\nName=".$current_user->display_name
				."\nEmail=".$current_user->user_email
				."\nTransientKey=".wp_create_nonce('log-out') // Vanilla's "TransientKey" is the equivalent of WordPress' "wpnonce".
				."\nDateOfBirth=" // Format: 1975-09-16
				."\nGender=Male";
		}
		exit();
	}
}

add_filter('login_redirect', 'proxyconnect_vanilla_li_redirect', 10, 3);
function proxyconnect_vanilla_li_redirect($redirect_to, $raw_requested_redirect_to, $user) {
   if ($user instanceof WP_User) {
      wp_redirect($redirect_to);
      die();
   }
      
   return $redirect_to;
}

add_action('wp_logout', 'proxyconnect_vanilla_logout');
function proxyconnect_vanilla_logout() {
   $RedirectTo = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : FALSE;
   if ($RedirectTo !== FALSE) {
      $UrlParts = parse_url($RedirectTo);
      $Host = $UrlParts['host'];
      
      $Referer = $_SERVER['HTTP_REFERER'];
      $RefererUrlParts = parse_url($Referer);
      $RefererHost = $RefererUrlParts['host'];
      
      if ($Host == $RefererHost) {
         wp_redirect($RedirectTo);
         die();
      }
   }

}

// Make sure that wordpress cookies don't set the path variable and are accessible to all subdomains.
if ( !function_exists('wp_set_auth_cookie') ) {
   /**
    * Sets the authentication cookies based User ID.
    *
    * The $remember parameter increases the time that the cookie will be kept. The
    * default the cookie is kept without remembering is two days. When $remember is
    * set, the cookies will be kept for 14 days or two weeks.
    *
    * @since 2.5
    *
    * @param int $user_id User ID
    * @param bool $remember Whether to remember the user or not
    */
   function wp_set_auth_cookie($user_id, $remember = false, $secure = '') {
   	if ( $remember ) {
   		$expiration = $expire = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, $remember);
   	} else {
   		$expiration = time() + apply_filters('auth_cookie_expiration', 172800, $user_id, $remember);
   		$expire = 0;
   	}
   
   	if ( '' === $secure )
   		$secure = is_ssl() ? true : false;
   
   	if ( $secure ) {
   		$auth_cookie_name = SECURE_AUTH_COOKIE;
   		$scheme = 'secure_auth';
   	} else {
   		$auth_cookie_name = AUTH_COOKIE;
   		$scheme = 'auth';
   	}
   
   	$auth_cookie = wp_generate_auth_cookie($user_id, $expiration, $scheme);
   	$logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');
   
   	do_action('set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme);
   	do_action('set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in');
   	$VanillaCookiePath = '/';
   	$VanillaCookieDomain = get_option('vanilla_cookie_domain');
   
   	// Set httponly if the php version is >= 5.2.0
   	if ( version_compare(phpversion(), '5.2.0', 'ge') ) {
   		setcookie($auth_cookie_name, $auth_cookie, $expire, $VanillaCookiePath, $VanillaCookieDomain, $secure, true);
   		setcookie($auth_cookie_name, $auth_cookie, $expire, $VanillaCookiePath, $VanillaCookieDomain, $secure, true);
   		setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, $VanillaCookiePath, $VanillaCookieDomain, false, true);
   		if ( COOKIEPATH != SITECOOKIEPATH )
   			setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, $VanillaCookiePath, $VanillaCookieDomain, false, true);
   	} else {
   		$cookie_domain = $VanillaCookieDomain;
   		if ( !empty($cookie_domain) )
   			$cookie_domain .= '; HttpOnly';
   		setcookie($auth_cookie_name, $auth_cookie, $expire, $VanillaCookiePath, $cookie_domain, $secure);
   		setcookie($auth_cookie_name, $auth_cookie, $expire, $VanillaCookiePath, $cookie_domain, $secure);
   		setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, $VanillaCookiePath, $cookie_domain);
   		if ( COOKIEPATH != SITECOOKIEPATH )
   			setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, $VanillaCookiePath, $cookie_domain);
   	}
   }
}

if ( !function_exists('wp_clear_auth_cookie') ) {
   /**
    * Removes all of the cookies associated with authentication.
    *
    * @since 2.5
    */
   function wp_clear_auth_cookie() {
   	do_action('clear_auth_cookie');
   	
   	$VanillaCookiePath = '/';
   	$VanillaCookieDomain = vf_get_option('vanilla_cookie_domain');
   
   	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
   	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
   	setcookie(AUTH_COOKIE, ' ', time() - 31536000, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
   	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
   	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
   	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
   
   	// Old cookies
   	setcookie(AUTH_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
   	setcookie(AUTH_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
   	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
   	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
   
   	// Even older cookies
   	setcookie(USER_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
   	setcookie(PASS_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
   	setcookie(USER_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
   	setcookie(PASS_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
   
   	// WordPress' new Vanilla SSO cookies
   	setcookie(AUTH_COOKIE, ' ', time() - 31536000, $VanillaCookiePath, $VanillaCookieDomain);
   	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, $VanillaCookiePath, $VanillaCookieDomain);
   	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, $VanillaCookiePath, $VanillaCookieDomain);
   	setcookie(USER_COOKIE, ' ', time() - 31536000, $VanillaCookiePath, $VanillaCookieDomain);
   	setcookie(PASS_COOKIE, ' ', time() - 31536000, $VanillaCookiePath, $VanillaCookieDomain);
   	
   	// Destroy Vanilla's session cookie as well
   	setcookie(VANILLA_COOKIE, ' ', time() - 31536000, $VanillaCookiePath, $VanillaCookieDomain);
   }
}