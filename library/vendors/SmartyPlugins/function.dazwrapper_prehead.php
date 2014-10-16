<?php if (!defined('APPLICATION')) exit();

function smarty_function_dazwrapper_prehead($Params, &$Smarty) {
	if ( ! $Smarty->get_template_vars('DAZ_Wrapper')) {
		$ch = curl_init(Gdn::Config('Daz.RootUrl') . Gdn::Config('Daz.WrapperUrl'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$wrapperHtml = curl_exec($ch);
		curl_close($ch);

		$Smarty->assign('DAZ_Wrapper', $wrapperHtml);
	}
	else {
		$wrapperHtml = $Smarty->get_template_vars('DAZ_Wrapper');
	}

	// from the start to just before the open <title> tag
	$header = substr($wrapperHtml, 0, strpos($wrapperHtml, '<title>'));
	return $header;
}
