<?php
/**
 * Embed Vanilla Functions
 */

/**
 * Embed Vanilla administration page.
 */
function vf_embed_admin_page() {
  // Check that the user has the required capability 
  if (!current_user_can('manage_options'))
	 wp_die(__('You do not have sufficient permissions to access this page.'));
  
  $post_id = vf_configure_embed_container();
  $options = get_option(VF_OPTIONS_NAME);
  $embed_code = vf_get_value('embed-code', $options);
  $vanilla_post = get_post($PostID);
?>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('&lt;Embed&gt; Vanilla'); ?></h2>
   <p>Use this page to embed your Vanilla Forum into WordPress.</p>
	<?php vf_open_form('embed-form'); ?>
		<strong>Forum Location in WordPress</strong>
		<em>Define where to access your Vanilla Forum within WordPress.</em>
		<div id="edit-slug-box"><?php echo get_sample_permalink_html($post_id); ?></div>
		<?php wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false ); ?>
		<em>You can further customize the page that contains your forum <a href="./post.php?post=<?php echo $post_id; ?>&action=edit">here</a>.</em>

		<strong>Forum &lt;Embed&gt; Code</strong>
		<textarea id="EmbedCode" name="<?php echo vf_get_option_name('embed-code'); ?>"><?php echo $embed_code; ?></textarea>
		<em>You can make changes to your forum embed code here (optional).</em>
      <p class="submit"><input type="submit" name="save" value="<?php _e('Save Changes'); ?>" /></p>
		</div>
   </form>
</div>
<?php
}

/**
 * Create a page for embedding the forum, give it a default name, url, and template.
 */
function vf_configure_embed_container() {
	$post_id = vf_get_option('embed-post-id');
	$post = get_post($post_id);
	// PostID not set or not related to an existing page? Generate the page now and apply template.
	if (!is_numeric($post_id) || $post_id <= 0 || !$post) {
		$post_id = wp_insert_post(array('post_name' => 'discussions', 'post_title' => 'Discussions', 'post_type' => 'page', 'post_status' => 'publish', 'comment_status' => 'closed'));
		vf_update_option('embed-post-id', $post_id);
	}
	// Copy the vanilla template to the current theme
	$template_to_use = 'embed_template.php';
	try {
      $filepath = __DIR__.'/templates/'.$template_to_use;
      if (file_exists($filepath))                                                                                                        
         copy($filepath, get_template_directory().'/'.$template_to_use);
		else
			$template_to_use = false;
	} catch (Exception $e) {
		$template_to_use = false;
	}
	if ($template_to_use)
		update_post_meta($post_id, '_wp_page_template', $template_to_use);

	return $post_id;
}

/**
 * Replace the page content with the vanilla embed code if viewing the page that
 * is supposed to contain the forum.
 *
 * @param string $content The content of the current page.
 */
function vf_embed_content($content) {
	global $post;
	if ($post->ID == vf_get_option('embed-post-id'))
		$content = stripslashes(vf_get_option('embed-code'));

	return $content;
}

/**
 * Handle saving the permalink via ajax.
 */
function vf_embed_edit_slug() {
	$post_id = vf_configure_embed_container();
	check_ajax_referer('samplepermalink', 'samplepermalinknonce');
	$slug = isset($_POST['new_slug'])? $_POST['new_slug'] : null;
	wp_update_post(array('ID' => $post_id, 'post_name' => $slug));
	die(get_sample_permalink_html($post_id, 'Discussion Forum', $slug));
}

