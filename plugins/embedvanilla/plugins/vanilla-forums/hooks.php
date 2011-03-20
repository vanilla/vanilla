<?php

//only enqueue all the admin stuff if is_admin
if (is_admin()) {
	// Initialize admin settings
	add_action('admin_init', 'vf_admin_init');

	// Add menu options to dashboard
	add_action('admin_menu', 'vf_add_vanilla_menu');
}

// Replace the page content with the vanilla embed code if viewing the page that
// is supposed to contain the forum.
add_filter('the_content', 'vf_embed_content');

// Handle saving the permalink via ajax
add_action('wp_ajax_vf_embed_edit_slug', 'vf_embed_edit_slug');

$options = get_option(VF_OPTIONS_NAME);
$url = vf_get_value('url', $options);
if ($url != '') {
	// Add Vanilla Widgets to WordPress
	add_action('widgets_init', 'vf_widgets_init');
}