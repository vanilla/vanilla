<?php

/**
 * Widget administration page.
 */
function vf_widgets_admin_page() {
	?>
<div class="wrap">
   <div id="icon-options-general" class="icon32"><br /></div>
   <h2><?php _e('Forum Widgets'); ?></h2>
   <p>Your Vanilla Forum provides some great widgets you can use in your WordPress blog:</p>
	<ul>
		<li><strong>Vanilla Discussions Widget</strong> allows you to display recent discussions in your Vanilla Forum in your WordPress site. You define how many discussions to show, and you can even filter to specific discussion categories.</li>
		<li><strong>Vanilla Activity Widget</strong> allows you to display recent activity in your forum (user registrations, status updates, etc). You define how many activities to show, and you can even filter to activities from users in specific roles (administrator activity, for example).</li>
		<li><strong>Vanilla Recently Active Users Widget</strong> allows you to display a list of users who have been recently active on your forum. You define how many users to show.</li>
	</ul>
	<p>All of these widgets are available on your <a href="./widgets.php">WordPress Widget Management page</a>.</p>
</div>
	<?php
}


// Put functions into one big function we'll call at the plugins_loaded
// action. This ensures that all required plugin functions are defined.
function vf_widgets_init() {

	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if (!function_exists('wp_register_sidebar_widget') )
		return;

// Recent Discussions Widget
	function vf_widget_discussions($args) {
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		$options = get_option(VF_OPTIONS_NAME);
		$title = vf_get_value('widget-discussions-title', $options, '');
		$categoryid = (int)vf_get_value('widget-discussions-categoryid', $options, '');
		$count = (int)vf_get_value('widget-discussions-count', $options, '');
		if ($count < 5)
			$count = 5;
			
		$url = vf_get_value('url', $options, '');
		$link_url = vf_get_link_url($options);
		$resturl = array($url, '?p=discussions.json');
		if ($categoryid > 0)
			$resturl = array($url, '?p=categories/'.$categoryid.'.json');
			
		$DataName = $categoryid > 0 ? 'DiscussionData' : 'Discussions';
		
		// Retrieve the latest discussions from the Vanilla API
		$resturl = vf_combine_paths($resturl, '/');
		$data = json_decode(vf_rest($resturl));
		if (!is_object($data))
			return;

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget . $before_title . $title . $after_title;
		echo '<ul>';
		$i = 0;
		foreach ($data->$DataName as $Discussion) {
			$i++;
			if ($i > $count)
				break;
			
			echo '<li><a href="'.vf_combine_paths(array($link_url, 'discussion/'.$Discussion->DiscussionID.'/'.vf_format_url($Discussion->Name)), '/').'">'.$Discussion->Name.'</a></li>';
		}
		echo '</ul>';
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function vf_widget_discussions_control() {
		// Get our options and see if we're handling a form submission.
		$options = get_option(VF_OPTIONS_NAME);
		$title = vf_get_value('widget-discussions-title', $options, 'Recent Discussions');
		$categoryid = (int)vf_get_value('widget-discussions-categoryid', $options);
		$count = (int)vf_get_value('widget-discussions-count', $options, 10);
		if ($_POST['widget-discussions-submit']) {
			// Remember to sanitize and format use input appropriately.
			$title = strip_tags(stripslashes($_POST['widget-discussions-title']));
			$categoryid = (int)vf_get_value('widget-discussions-categoryid', $_POST);
			$count = (int)vf_get_value('widget-discussions-count', $_POST);
			$options['widget-discussions-title'] = $title;
			$options['widget-discussions-categoryid'] = $categoryid;
			$options['widget-discussions-count'] = $count;
			update_option(VF_OPTIONS_NAME, $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($title, ENT_QUOTES);
		
		// Retrieve & build the category dropdown
		$resturl = vf_get_value('url', $options, '');
		$resturl = vf_combine_paths(array($resturl, '?p=categories.json'), '/');
		$category_data = json_decode(vf_rest($resturl));
		$select_options = vf_get_select_option('All Categories', '0', $categoryid);
		if (is_object($category_data)) {
			foreach ($category_data->Categories as $Category) {
				$select_options .= vf_get_select_option($Category->Name, $Category->CategoryID, $categoryid);
			}
		}
		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p><label for="widget-discussions-title">' . __('Title:') . ' <input style="width: 100%;" id="widget-discussions-title" name="widget-discussions-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p><label for="widget-discussions-categoryid">' . __('Filter to Category:') . ' <select id="widget-discussions-categoryid" name="widget-discussions-categoryid">'.$select_options.'</select></label></p>';
		echo '<p><label for="widget-discussions-count">' . __('Number of Discussions to show:') . ' <input style="width: 40px;" id="widget-discussions-count" name="widget-discussions-count" type="text" value="'.$count.'" /></label></p>';
		echo '<input type="hidden" id="widget-discussions-submit" name="widget-discussions-submit" value="1" />';
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	wp_register_sidebar_widget('vf-widget-discussions', 'Vanilla Discussions', 'vf_widget_discussions', array('description' => 'Recent discussions in your Vanilla Forum.'));

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	wp_register_widget_control('vf-widget-discussions', 'Vanilla Discussions', 'vf_widget_discussions_control');


// Recent Activity Widget
	function vf_widget_activities($args) {
		extract($args);

		$options = get_option(VF_OPTIONS_NAME);
		$title = vf_get_value('widget-activities-title', $options, '');
		// $roleid = (int)vf_get_value('widget-activities-roleid', $options, '');
		$count = (int)vf_get_value('widget-activities-count', $options, '');
		if ($count < 5)
			$count = 5;
			
		$url = vf_get_value('url', $options, '');
		$link_url = vf_get_link_url($options);
		$resturl = array($url, '?p=activity.json');
		// if ($roleid > 0)
		// 	$resturl = array($url, 'activities/'.$roleid.'.json');
			
		// Retrieve the latest discussions from the Vanilla API
		$resturl = vf_combine_paths($resturl, '/');
		$data = json_decode(vf_rest($resturl));
		if (!is_object($data))
			return;

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget . $before_title . $title . $after_title;
		echo '<ul>';
		$i = 0;
		foreach ($data->ActivityData as $Activity) {
			$i++;
			if ($i > $count)
				break;
			
			echo '<li>'.vf_format_activity($Activity, $link_url).'</li>';
		}
		echo '</ul>';
		echo $after_widget;
	}

	function vf_widget_activities_control() {
		// Get our options and see if we're handling a form submission.
		$options = get_option(VF_OPTIONS_NAME);
		$title = vf_get_value('widget-activities-title', $options, 'Recent Forum Activity');
		// $roleid = (int)vf_get_value('widget-activities-roleid', $options);
		$count = (int)vf_get_value('widget-activities-count', $options, 10);
		if ($_POST['widget-activities-submit']) {
			// Remember to sanitize and format use input appropriately.
			$title = strip_tags(stripslashes($_POST['widget-activities-title']));
			// $roleid = (int)vf_get_value('widget-activities-roleid', $_POST);
			$count = (int)vf_get_value('widget-activities-count', $_POST);
			$options['widget-activities-title'] = $title;
			// $options['widget-activities-roleid'] = $roleid;
			$options['widget-activities-count'] = $count;
			update_option(VF_OPTIONS_NAME, $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($title, ENT_QUOTES);
		
		// TODO: Retrieve & build the role dropdown. At time of this writing, there is no way to get the roles from Vanilla.
		/*
		$resturl = vf_get_value('url', $options, '');
		$resturl = vf_combine_paths(array($resturl, 'settings/roles.json'), '/');
		$role_data = json_decode(vf_rest($resturl));
		$select_options = vf_get_select_option('All Roles', '0', $roleid);
		if (is_object($role_data)) {
			foreach ($role_data->Categories as $Role) {
				$select_options .= vf_get_select_option($Role->Name, $Role->CategoryID, $roleid);
			}
		}
		*/
		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p><label for="widget-activities-title">' . __('Title:') . ' <input style="width: 100%;" id="widget-activities-title" name="widget-activities-title" type="text" value="'.$title.'" /></label></p>';
		// echo '<p><label for="widget-activities-roleid">' . __('Filter to Role:') . ' <select id="widget-discussions-categoryid" name="widget-activities-roleid">'.$select_options.'</select></label></p>';
		echo '<p><label for="widget-activities-count">' . __('Number of activities to show:') . ' <input style="width: 40px;" id="widget-activities-count" name="widget-activities-count" type="text" value="'.$count.'" /></label></p>';
		echo '<input type="hidden" id="widget-activities-submit" name="widget-activities-submit" value="1" />';
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	wp_register_sidebar_widget('vf-widget-activities', 'Vanilla Activity', 'vf_widget_activities', array('description' => 'Recent activity happening on your Vanilla Forum (eg. new users, status updates, etc).'));

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	wp_register_widget_control('vf-widget-activities', 'Vanilla Recent Activity', 'vf_widget_activities_control');


// Recently Active Users Widget
	function vf_widget_users($args) {
		extract($args);

		$options = get_option(VF_OPTIONS_NAME);
		$title = vf_get_value('widget-users-title', $options, '');
		$count = (int)vf_get_value('widget-users-count', $options, '');
		$width = (int)vf_get_value('widget-users-iconwidth', $options, 32);
		if ($count < 5)
			$count = 5;
			
		$url = vf_get_value('url', $options, '');
		$link_url = vf_get_link_url($options);
		$resturl = array($url, '?p=user/summary.json');
			
		// Retrieve the latest discussions from the Vanilla API
		$resturl = vf_combine_paths($resturl, '/');
		$data = json_decode(vf_rest($resturl));
		if (!is_object($data))
			return;

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget . $before_title . $title . $after_title;
		echo '<div class="ForumUsers">';
		$i = 0;
		foreach ($data->UserData as $User) {
			$i++;
			if ($i > $count)
				break;
			
			$User->IconWidth = $width;
			echo vf_user_photo($User, $link_url).' ';
		}
		echo '</div>';
		echo $after_widget;
	}

	function vf_widget_users_control() {
		// Get our options and see if we're handling a form submission.
		$options = get_option(VF_OPTIONS_NAME);
		$title = vf_get_value('widget-users-title', $options, 'Recently Active Users');
		$count = (int)vf_get_value('widget-users-count', $options, 10);
		$width = (int)vf_get_value('widget-users-iconwidth', $options, 32);
		if ($_POST['widget-users-submit']) {
			// Remember to sanitize and format use input appropriately.
			$title = strip_tags(stripslashes($_POST['widget-users-title']));
			$count = (int)vf_get_value('widget-users-count', $_POST);
			$width = (int)vf_get_value('widget-users-iconwidth', $_POST);
			$options['widget-users-title'] = $title;
			$options['widget-users-count'] = $count;
			$options['widget-users-iconwidth'] = $width;
			update_option(VF_OPTIONS_NAME, $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($title, ENT_QUOTES);
		
		echo '<p><label for="widget-users-title">' . __('Title:') . ' <input style="width: 100%;" id="widget-users-title" name="widget-users-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p><label for="widget-users-count">' . __('Number of users to show:') . ' <input style="width: 40px;" id="widget-users-count" name="widget-users-count" type="text" value="'.$count.'" /></label></p>';
		echo '<p><label for="widget-users-iconwidth">' . __('Icon width:') . ' <input style="width: 40px;" id="widget-users-iconwidth" name="widget-users-iconwidth" type="text" value="'.$width.'" />px</label></p>';
		echo '<input type="hidden" id="widget-users-submit" name="widget-users-submit" value="1" />';
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	wp_register_sidebar_widget('vf-widget-users', 'Vanilla Users', 'vf_widget_users', array('description' => 'Icons of recently active users in your Vanilla Forum.'));

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	wp_register_widget_control('vf-widget-users', 'Vanilla Recently Active User', 'vf_widget_users_control');
}

