<?php
/**
 * Utility & hook functions.
 */


/**
 * Retrieve an option value from wordpress.
 *
 * @param string $option_name The name of the option to retrieve.
 * @param string $default_value The default value to return if $option_name is not defined.
 */
function vf_get_option($option_name, $default_value = FALSE) {
	$vf_options = get_option(VF_OPTIONS_NAME);
	return vf_get_value($option_name, $vf_options, $default_value);
}

function vf_get_option_name($option_name) {
	return VF_OPTIONS_NAME.'['.$option_name.']';
}

/**
 * Saves an option value to wordpress.
 *
 * @param string $option_name The name of the option to save.
 * @param string $option_value The value to save.
 */
function vf_update_option($option_name, $option_value) {
	$options = get_option(VF_OPTIONS_NAME);
	if (!is_array($options))
		$options = array();
		
	$options[$option_name] = $option_value;
	$return = update_option(VF_OPTIONS_NAME, $options);
}

/**
 * Return the value from an associative array or an object.
 *
 * @param string $Key The key or property name of the value.
 * @param mixed $Collection The array or object to search.
 * @param mixed $Default The value to return if the key does not exist.
 */
function vf_get_value($Key, &$Collection, $Default = FALSE) {
	$Result = $Default;
	if(is_array($Collection) && array_key_exists($Key, $Collection)) {
		$Result = $Collection[$Key];
	} elseif(is_object($Collection) && property_exists($Collection, $Key)) {
		$Result = $Collection->$Key;
	}
		
	return $Result;
}

/**
 * Returns the result of a REST request to $Url.
 *
 * @param string $Url The url to make a REST request to.
 */
function vf_rest($Url) {
	try {
		$C = curl_init();
		curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($C, CURLOPT_URL, $Url);
		$Contents = curl_exec($C);
 
		if ($Contents === FALSE)
			$Contents = curl_error($C);
 
		$Info = curl_getinfo($C);
		if (strpos(vf_get_value('content_type', $Info, ''), '/javascript') !== FALSE) {
			$Result = json_decode($Contents, TRUE);
			if (is_array($Result) && isset($Result['Exception']) && isset($Result['Code'])) {
				curl_close($C);
				throw new Exception($Result['Exception'], $Result['Code']);
			}
		} else {
			$Result = $Contents;
		}
		curl_close($C);
		return $Result;
	} catch (Exception $ex) {
		return $ex;
	}
}

/**
 * Takes an array of path parts and concatenates them using the specified
 * delimiter. Delimiters will not be duplicated. Example: all of the
 * following arrays will generate the path "/path/to/vanilla/applications/dashboard"
 * array('/path/to/vanilla', 'applications/dashboard')
 * array('/path/to/vanilla/', '/applications/dashboard')
 * array('/path', 'to', 'vanilla', 'applications', 'dashboard')
 * array('/path/', '/to/', '/vanilla/', '/applications/', '/dashboard')
 * 
 * @param array $paths The array of paths to concatenate.
 * @param string $delimiter The delimiter to use when concatenating. Defaults to system-defined directory separator.
 * @returns The concatentated path.
 */
function vf_combine_paths($paths, $delimiter = DS) {
	if (is_array($paths)) {
		$munged_path = implode($delimiter, $paths);
		$munged_path = str_replace(array($delimiter.$delimiter.$delimiter, $delimiter.$delimiter), array($delimiter, $delimiter), $munged_path);
		return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $munged_path);
	} else {
		return $paths;
	}
}

/**
 * Writes out the opening of an options form.
 */
function vf_open_form($formname) {
	echo '<form method="post" action="options.php">';
	echo '<input type="hidden" name="'.vf_get_option_name('form-name').'" value="'.$formname.'" />';
	settings_fields(VF_OPTIONS_NAME);
	settings_errors();
}
function vf_close_form() {
	echo '</form>';
}

/**
 * Validates options being saved for Vanilla Forums. WordPress is a bit hinky
 * here, so we use hidden inputs to identify the forum being saved and validate
 * the inputs accordingly. This is a catch-all validation for all forms.
 */
function vf_validate_options($options) {
	$formname = vf_get_value('form-name', $options);
	$alloptions = get_option(VF_OPTIONS_NAME);
	if (!is_array($alloptions))
		$alloptions = array();
		
	switch ($formname) {
		case 'url-form':
			$url = vf_get_value('url', $options, '');
			$options = $alloptions;
			// Validate that there is a vanilla installation at the url, and grab the WebRoot from the source.
			$html = vf_rest($url);
			$wr_pos = strpos($html, 'WebRoot" value="');
			if ($wr_pos > 0) {
				$webroot = substr($html, $wr_pos + 16);
				$webroot = substr($webroot, 0, strpos($webroot, '"'));
				$options['url'] = $webroot;
				if (vf_get_value('embed-code', $options, '') == '') {
					// Set the embed_code if it is not already defined.
					$embedurl = vf_combine_paths(array($webroot, 'plugins/embedvanilla/remote.js'), '/');
					$options['embed-code'] = '<script type="text/javascript" src="'.$embedurl.'"></script>';
				}
				vf_configure_embed_container();
			} else {
				$options['url'] = '';
				add_settings_error('url', 'url', 'Forum url could not be validated. Are you sure you entered the correct web address of your forum?'); 
			}
			break;
		case 'embed-form':
			$embed_code = vf_get_value('embed-code', $options, '');
			$options = $alloptions;
			$url = vf_get_value('url', $options, '');
			if ($embed_code == '') {
				// Set the embed_code if it is not already defined.
				$embedurl = vf_combine_paths(array($url, 'plugins/embedvanilla/remote.js'), '/');
				$options['embed-code'] = '<script type="text/javascript" src="'.$embedurl.'"></script>';
			} else {
				$options['embed-code'] = $embed_code;
			}
			break;
		default:
			$options = array_merge($alloptions, $options);
			break;
	}
	
	return $options;
}

function vf_get_select_option($name, $value, $selected_value = '') {
	return '<option value="'.$value.'"'.($value == $selected_value ? ' selected="selected"' : '').'>'.$name.'</option>';
}


/**
 * The ActivityType table has some special sprintf search/replace values in the
 * FullHeadline and ProfileHeadline fields. The ProfileHeadline field is to be
 * used on this page (the user profile page). The FullHeadline field is to be
 * used on the main activity page. The replacement definitions are as follows:
 *  %1$s = ActivityName
 *  %2$s = ActivityName Possessive
 *  %3$s = RegardingName
 *  %4$s = RegardingName Possessive
 *  %5$s = Link to RegardingName's Wall
 *  %6$s = his/her
 *  %7$s = he/she
 *  %8$s = route & routecode
 *  %9$s = gender suffix (some languages require this).
 *
 * @param object $Activity An object representation of the activity being formatted.
 * @param string $Url The root url to the forum.
 * @return string
 */
function vf_format_activity($Activity, $Url) {
	$ProfileUserID = -1;
	$ViewingUserID = -1;
	$GenderSuffixCode = 'First';
	$GenderSuffixGender = $Activity->ActivityGender;
	
	if ($ViewingUserID == $Activity->ActivityUserID) {
		$ActivityName = $ActivityNameP = 'You';
	} else {
		$ActivityName = $Activity->ActivityName;
		$ActivityNameP = vf_format_possessive($ActivityName);
		$GenderSuffixCode = 'Third';
	}
	if ($ProfileUserID != $Activity->ActivityUserID) {
		// If we're not looking at the activity user's profile, link the name
		$ActivityNameD = urlencode($Activity->ActivityName);
		$ActivityName = vf_anchor($ActivityName, '/profile/' . $Activity->ActivityUserID . '/' . $ActivityNameD, $Url);
		$ActivityNameP = vf_anchor($ActivityNameP, '/profile/' . $Activity->ActivityUserID  . '/' . $ActivityNameD, $Url);
		$GenderSuffixCode = 'Third';
	}
	$Gender = $Activity->ActivityGender == 'm' ? 'his' : 'her';
	$Gender2 = $Activity->ActivityGender == 'm' ? 'he' : 'she';
	if ($ViewingUserID == $Activity->RegardingUserID || ($Activity->RegardingUserID == '' && $Activity->ActivityUserID == $ViewingUserID)) {
		$Gender = $Gender2 = 'your';
	}

	$IsYou = FALSE;
	$RegardingName = $Activity->RegardingName == '' ? 'somebody' : $Activity->RegardingName;
	$RegardingNameP = vf_format_possessive($RegardingName);

	if ($Activity->ActivityUserID != $ViewingUserID)
		$GenderSuffixCode = 'Third';

	$RegardingWall = '';

	if ($Activity->ActivityUserID == $Activity->RegardingUserID) {
		// If the activityuser and regardinguser are the same, use the $Gender Ref as the RegardingName
		$RegardingName = $RegardingProfile = $Gender;
		$RegardingNameP = $RegardingProfileP = $Gender;
	} else if ($Activity->RegardingUserID > 0 && $ProfileUserID != $Activity->RegardingUserID) {
		// If there is a regarding user and we're not looking at his/her profile, link the name.
		$RegardingNameD = urlencode($Activity->RegardingName);
		if (!$IsYou) {
			$RegardingName = vf_anchor($RegardingName, '/profile/' . $Activity->RegardingUserID . '/' . $RegardingNameD, $Url);
			$RegardingNameP = vf_anchor($RegardingNameP, '/profile/' . $Activity->RegardingUserID . '/' . $RegardingNameD, $Url);
			$GenderSuffixCode = 'Third';
			$GenderSuffixGender = $Activity->RegardingGender;
		}
		$RegardingWall = vf_anchor('wall', '/profile/activity/' . $Activity->RegardingUserID . '/' . $RegardingNameD . '#Activity_' . $Activity->ActivityID, $Url);
	}
	if ($RegardingWall == '')
		$RegardingWall = 'wall';

	if ($Activity->Route == '') {
		if ($Activity->RouteCode)
			$Route = $Activity->RouteCode;
		else
			$Route = '';
	} else
		$Route = vf_anchor($Activity->RouteCode, $Activity->Route, $Url);

	// Translate the gender suffix.
	$GenderSuffixCode = "GenderSuffix.$GenderSuffixCode.$GenderSuffixGender";
	$GenderSuffix = $GenderSuffixCode;
	if ($GenderSuffix == $GenderSuffixCode)
		$GenderSuffix = ''; // in case translate doesn't support empty strings.

	$FullHeadline = $Activity->FullHeadline;
	$ProfileHeadline = $Activity->ProfileHeadline;
	$MessageFormat = ($ProfileUserID == $Activity->ActivityUserID || $ProfileUserID == '' ? $FullHeadline : $ProfileHeadline);
	
	return sprintf($MessageFormat, $ActivityName, $ActivityNameP, $RegardingName, $RegardingNameP, $RegardingWall, $Gender, $Gender2, $Route, $GenderSuffix);
}

function vf_anchor($text, $destination, $url = '') {
	$prefix = substr($destination, 0, 7);
	if (!in_array($prefix, array('https:/', 'http://', 'mailto:'))) {
		$url = $url == '' ? vf_get_option('url') : $url;
		$destination = vf_combine_paths(array($url, $destination), '/');
	}

	return '<a href="'.$destination.'">'.$text.'</a>';
}

function vf_format_possessive($word) {
   return substr($word, -1) == 's' ? $word."'" : $word."'s";
}

function vf_user_photo($User, $Url, $CssClass = '') {
	if ($User->Photo == '')
		$User->Photo = vf_combine_paths(array($Url, 'applications/dashboard/design/images/usericon.gif'), '/');
	
	$CssClass = $CssClass == '' ? '' : ' class="'.$CssClass.'"';
	$IsFullPath = strtolower(substr($User->Photo, 0, 7)) == 'http://' || strtolower(substr($User->Photo, 0, 8)) == 'https://'; 
	$PhotoUrl = ($IsFullPath) ? $User->Photo : vf_combine_paths(array($Url, 'uploads/'.vf_change_basename($User->Photo, 'n%s')), '/');
	return '<a href="'.vf_combine_paths(array($Url, '/profile/'.$User->UserID.'/'.urlencode($User->Name)), '/').'"'.$CssClass.' style="display: inline-block; margin: 0 2px 2px 0">'
		.'<img src="'.$PhotoUrl.'" alt="'.urlencode($User->Name).'" style="width: '.$User->IconWidth.'px; height: '.$User->IconWidth.'px; overflow: hidden; display: inline-block;" />'
		.'</a>';
}

/** Change the basename part of a filename for a given path.
 *
 * @param string $Path The path to alter.
 * @param string $NewBasename The new basename. A %s will be replaced by the old basename.
 * @return string
 */
function vf_change_basename($Path, $NewBasename) {
	$NewBasename = str_replace('%s', '$2', $NewBasename);
	$Result = preg_replace('/^(.*\/)?(.*?)(\.[^.]+)$/', '$1'.$NewBasename.'$3', $Path);
	return $Result;
}
