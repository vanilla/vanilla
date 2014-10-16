<?php if (!defined('APPLICATION')) exit();

function smarty_function_dazwrapper_posthead($Params, &$Smarty) {
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

	// from the close </title> tag to the end of the header
	$start = strpos($wrapperHtml, '</title>') + strlen('</title>');
	$length = strpos($wrapperHtml, '<!--ENDHEADER-->') - $start;
	$header = substr($wrapperHtml, $start, $length);
	return $header;
}
