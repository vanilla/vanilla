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

	// remove the magento #crumbs div
	$dom = new DOMDocument;
	libxml_use_internal_errors(true); // PHP DOM doesn't like HTML5
	$dom->loadHTML($wrapperHtml);
	libxml_clear_errors( );
	$xPath = new DOMXPath($dom);
	$nodes = $xPath->query('//*[@id="crumbs"]');
	if ($nodes->item(0)) {
		$nodes->item(0)->parentNode->removeChild($nodes->item(0));
	}
	$wrapperHtml = $dom->saveHTML( );

	// from the close </title> tag to the end of the header
	$start = strpos($wrapperHtml, '</title>') + strlen('</title>');
	$length = strpos($wrapperHtml, '<!--ENDHEADER-->') - $start;
	$header = substr($wrapperHtml, $start, $length);

	return $header;
}
