<?php
/**
 * Functions related to general administration of this plugin: defining the
 * forum url, creating a new forum, forum administration, etc.
 */

// Init plugin options to white list our options
function vf_admin_init() {
  register_setting(VF_OPTIONS_NAME, VF_OPTIONS_NAME, 'vf_validate_options');

  $page = vf_get_value('page', $_GET);
  if (in_array($page, array('vf-admin-handle', 'vf-embed-handle', 'vf-widgets-handle'))) {
	 // This will add /wp-content/plugins/vanillaforums/assets/vanillaforums.js to the current page
	 wp_enqueue_script(
		'vanillaforums',
		plugins_url('/VanillaForums/assets/vanillaforums.js'),
		array('jquery'),
		'1.0'
	 );
  
	 // This will add /wp-content/plugins/vanillaforums/assets/vanillaforums.css to the current page
	 wp_enqueue_style(
		'vanillaforums',
		plugins_url('/VanillaForums/assets/vanillaforums.css'),
		array(),
		'1.0'
	 );
  }
}

function vf_add_vanilla_menu() {
  add_menu_page('Vanilla Forum', 'Vanilla Forum', 'manage_options', 'vf-admin-handle', 'vf_admin_page', plugins_url('VanillaForums/assets/transparent.png'));
  add_submenu_page('vf-admin-handle', 'Forum Administration', 'Forum Administration', 'manage_options', 'vf-admin-handle', 'vf_admin_page');
  
  // Don't show the various forum pages unless the forum url has been properly defined.
  if (vf_get_option('url', '') != '') {
	 add_submenu_page('vf-admin-handle', '&lt;Embed&gt; Vanilla', '&lt;Embed&gt; Vanilla', 'manage_options', 'vf-embed-handle', 'vf_embed_admin_page');
	 add_submenu_page('vf-admin-handle', 'Widgets', 'Widgets', 'manage_options', 'vf-widgets-handle', 'vf_widgets_admin_page');
	 // add_submenu_page('vf-admin-handle', 'Single Sign-on', 'Single Sign-on', 'manage_options', 'vf-sso-handle', 'vf_sso_admin_page');
  }
}

function vf_admin_page() {
  // Check that the user has the required capability 
  if (!current_user_can('manage_options'))
	 wp_die(__('You do not have sufficient permissions to access this page.'));
  
  $options = get_option(VF_OPTIONS_NAME);
  $url = vf_get_value('url', $options);
?>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('Vanilla Forum Administration'); ?></h2>
   <p>Use this page to configure your Vanilla Forum to work with WordPress.</p>
	<?php if ($url == '') { ?>
	 <div class="GetVanilla">
		<h2>Don't have a Vanilla Forum yet?</h2>
		<a href="http://vanillaforums.com" target="_blank"><span>Get one in under 60 seconds!</span></a>
	 </div>
	<?php
	}
	vf_open_form('url-form');
	?>
		<strong>Tell WordPress where your Vanilla Forum is located</strong>
		<input name="<?php echo vf_get_option_name('url'); ?>" value="<?php echo $url; ?>" class="InputBox" />
		<em>Paste the url to your Vanilla forum here (eg. http://yourdomain.com/forum)</em>
      <p class="submit"><input type="submit" name="save" value="<?php _e('Validate &amp; Save'); ?>" /></p>
   </form>
</div>
<?php
}