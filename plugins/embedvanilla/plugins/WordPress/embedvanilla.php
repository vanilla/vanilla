<?php
/*
Plugin Name: Embed Vanilla
Plugin URI: http://vanillaforums.org/addons/
Description: Turns WordPress into a Vanilla-loving temptress.
Version: 1.0.1
Author: Mark O'Sullivan
Author URI: http://www.markosullivan.ca/

Copyright 2009 Mark O'Sullivan
This file is part of the Embed Vanilla for WordPress.
The Embed Vanilla plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The Embed Vanilla plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with the Embed Vanilla plugin.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] vanillaforums [dot] com
*/

/* Create a placeholder page and template for the forum when the plugin is enabled. */
register_activation_hook(__FILE__, 'embed_vanilla_activate');
function embed_vanilla_activate() {
	embed_vanilla_configure_container();
}

/* Configure the page and template to contain the Vanilla forum */
function embed_vanilla_configure_container() {
	$PostID = get_option('vanilla_embed_postid');
	$VanillaPost = get_post($PostID);
	// PostID not set or not related to an existing page? Generate the page now and apply template.
	if (!is_numeric($PostID) || $PostID <= 0 || !$VanillaPost) {
		// Copy the vanilla template to the current theme
		$TemplateToUse = 'vanilla.php';
		try {
			copy(__DIR__.'/templates/vanilla.php', get_template_directory().'/vanilla.php');
		} catch (Exception $e) {
			$TemplateToUse = false;
		}
		
		$PostID = wp_insert_post(array('post_name' => 'discussions', 'post_title' => 'Discussions', 'post_type' => 'page', 'post_status' => 'publish', 'comment_status' => 'closed'));
		if ($TemplateToUse)
			update_post_meta($PostID, '_wp_page_template', $TemplateToUse);
			
		update_option('vanilla_embed_postid', $PostID);
	}
	return $PostID;
}

/* Replace the page content with the vanilla embed code if this is the forum page. */
add_filter('the_content', 'embed_vanilla_content');
function embed_vanilla_content($content) {
	global $post;
	if ($post->ID == get_option('vanilla_embed_postid')) {
		$content = stripslashes(get_option('vanilla_embed_code'));
	}

	return $content;
}

/* Add admin menu option */
add_action('admin_menu', 'vanilla_embed_menu');
function vanilla_embed_menu() {
   add_submenu_page('plugins.php', 'VanillaEmbed', '&lt;Embed&gt; Vanilla', 'administrator', 'vanilla-embed', 'vanilla_embed_options');
}

/* Handle saving the permalink via ajax */
add_action('wp_ajax_vanilla_embed_edit_slug', 'vanilla_embed_edit_slug');
function vanilla_embed_edit_slug() {
	$PostID = embed_vanilla_configure_container();
	check_ajax_referer('samplepermalink', 'samplepermalinknonce');
	$Slug = isset($_POST['new_slug'])? $_POST['new_slug'] : null;
	wp_update_post(array('ID' => $PostID, 'post_name' => $Slug));
	die(get_sample_permalink_html($PostID, 'Discussion Forum', $Slug));
}

/* Admin page */
function vanilla_embed_options() {
	$PostID = embed_vanilla_configure_container();
   if (isset($_POST['save'])) {
      if (function_exists('current_user_can') && !current_user_can('manage_options'))
         die(__('Permission Denied'));

      $VanillaEmbedCode = stripslashes(array_key_exists('vanilla_embed_code', $_POST) ? $_POST['vanilla_embed_code'] : '');
      update_option('vanilla_embed_code', $VanillaEmbedCode);
   } else {
      $VanillaEmbedCode = stripslashes(get_option('vanilla_embed_code'));
		$VanillaPost = get_post($PostID);
      $VanillaPageName = $VanillaPost->post_title;
   }
      
?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	editPermalink = function(post_id) {
		var i, c = 0, e = $('#editable-post-name'), revert_e = e.html(), real_slug = $('#post_name'), revert_slug = real_slug.val(), b = $('#edit-slug-buttons'), revert_b = b.html(), full = $('#editable-post-name-full').html();
	
		$('#view-post-btn').hide();
		b.html('<a href="#" class="save button">Save</a> <a class="cancel" href="#">Cancel</a>');
		b.children('.save').click(function() {
			var new_slug = e.children('input').val();
			$.post(ajaxurl, {
				action: 'vanilla_embed_edit_slug',
				post_id: post_id,
				new_slug: new_slug,
				new_title: $('#title').val(),
				samplepermalinknonce: $('#samplepermalinknonce').val()
			}, function(data) {
				$('#edit-slug-box').html(data);
				b.html(revert_b);
				real_slug.attr('value', new_slug);
				makeSlugeditClickable();
				$('#view-post-btn').show();
			});
			return false;
		});
	
		$('.cancel', '#edit-slug-buttons').click(function() {
			$('#view-post-btn').show();
			e.html(revert_e);
			b.html(revert_b);
			real_slug.attr('value', revert_slug);
			return false;
		});
	
		for ( i = 0; i < full.length; ++i ) {
			if ( '%' == full.charAt(i) )
				c++;
		}
	
		slug_value = ( c > full.length / 4 ) ? '' : full;
		e.html('<input type="text" id="new-post-slug" value="'+slug_value+'" />').children('input').keypress(function(e){
			var key = e.keyCode || 0;
			// on enter, just save the new slug, don't save the post
			if ( 13 == key ) {
				b.children('.save').click();
				return false;
			}
			if ( 27 == key ) {
				b.children('.cancel').click();
				return false;
			}
			real_slug.attr('value', this.value);
		}).focus();
	}
	
	makeSlugeditClickable = function() {
		$('#editable-post-name').click(function() {
			$('#edit-slug-buttons').children('.edit-slug').click();
		});
	}
	makeSlugeditClickable();

});
</script>
<style type="text/css">
form strong {
    display: block;
	 padding-top: 10px;
}
#edit-slug-box {
	font-size: 11px;
	padding: 8px;
	margin: 0;
	height: auto;
	background: #fff;
	border: 1px inset #888;
	display: inline-block;
	min-width: 384px;
}
#edit-slug-box strong {
	display: none;
	font-size: 12px;
	color: #333;
}
#editable-post-name input {
	width: 120px;
}
form em {
    display: block;
    color: #555;
    font-size: 11px;
}
textarea,
textarea:focus {
    font-family: monospace;
    color: #000;
    height: 50px;
    width: 400px;
    font-size: 12px;
    line-height: 13px;
    padding: 2px;
    border-radius: 0;
    -moz-border-radius: 0;
    -webkit-border-radius: 0;
    border: 1px inset #888;
}
input.InputBox,
input.InputBox:focus {
    width: 400px;
	 border: 1px solid #888;
}
.GetVanilla {
	padding: 10px 0 26px;
}
.GetVanilla h2 {
	margin: 0;
	padding: 0 0 16px;
}
.GetVanilla a {
   margin: 0;
	box-shadow: 0px 0px 2px #999;
	-moz-box-shadow: 0px 0px 2px #999;
	-webkit-box-shadow: 0px 0px 2px #999;  
   border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	background:url("../wp-content/plugins/EmbedVanilla/bg-button-blue.png") repeat-x scroll left top transparent;
	border:1px solid #0F7FE6;
	color:#003673;
	cursor:pointer;
	font-size:12px;
	font-weight:bold;
	padding:6px 10px 6px 6px;
	text-decoration:none;
	text-shadow:0 1px 0 #B7F5FD;
}
.GetVanilla a span {
	padding-left: 20px;
	background: url('../wp-content/plugins/EmbedVanilla/logo.png') no-repeat 0 0;
}
.GetVanilla a:hover {
	border: 1px solid #0B64C6;
	color: #001F44;
	text-shadow:0 1px 0 #EEFFFF;
}
.GetVanilla a:focus {
	background: #81CFF6;
	border:1px solid #0B64C6;
	color:#001F44;
	text-shadow:0 1px 0 #EEFFFF;
}


</style>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('&lt;Embed&gt; Vanilla'); ?></h2>
	<?php
	if (isset($_POST['save'])) {
		echo '<div class="updated" id="message"><p>Your changes have been saved.</p></div>';
	}
	?>
   <p>Use this page to embed your Vanilla Forum into WordPress.</p>
	<?php
	if (substr($VanillaEmbedCode, 0, 7) != '<script') {
		echo "<div class=\"GetVanilla\">
			<h2>Don't have a Vanilla Forum yet?</h2>
			<a href=\"http://vanillaforums.com\" target=\"_blank\"><span>Get one in under 60 seconds!</span></a>
		</div>";
	}
	?>
   <form method="post">
		<strong>Forum &lt;Embed&gt; Code</strong>
		<textarea id="EmbedCode" name="vanilla_embed_code"><?php echo $VanillaEmbedCode; ?></textarea>
		<em>Paste the forum embed code from your Vanilla forum here.</em>

		<strong>Forum Location in WordPress</strong>
		<em>Define where to access your Vanilla Forum within WordPress.</em>
		<?php
		/*
		<input type="text" class="InputBox" name="vanilla_page_name" value="<?php echo $VanillaPageName; ?>" />
		*/
		?>
		<div id="edit-slug-box"><?php echo get_sample_permalink_html($PostID); ?></div>
		<?php wp_nonce_field( 'samplepermalink', 'samplepermalinknonce', false ); ?>
		</div>
      <p class="submit"><input type="submit" name="save" value="<?php _e('Save &raquo;'); ?>" /></p>
   </form>
</div>
<?php
}