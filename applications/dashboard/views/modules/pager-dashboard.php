<?php
$pager = Gdn::controller()->data('Pager');
// Get total page count, allowing override
$PageCount = ceil($pager->TotalRecords / $pager->Limit);
$CurrentPage = PageNumber($pager->Offset, $pager->Limit);

$Pager = '<div class="pager btn-group">';
if ($CurrentPage >= $PageCount) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$Pager .=  '<div class="'.$disabled.' btn btn-secondary js-btn-page-selector js-pager">';
$Pager .= sprintf(t('Page %s of %s'), $CurrentPage, $PageCount ? $PageCount : 1);
$Pager .=  '</div>';

// Previous
if ($CurrentPage == 1) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$Pager .= anchor('', $pager->PageUrl($CurrentPage - 1), $disabled.' Previous btn btn-secondary icon icon-chevron-left', array('rel' => 'prev', 'aria-label' => 'Previous page'));

// Next
if ($CurrentPage >= $PageCount) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$PageParam = 'p'.($CurrentPage + 1);
$Pager .= anchor('', $pager->PageUrl($CurrentPage + 1), $disabled.' Next btn btn-secondary icon icon-chevron-right', array('rel' => 'next', 'aria-label' => 'Next page')); // extra sprintf parameter in case old url style is set
$Pager .= '</div>';

echo sprintf($pager->Wrapper, Attribute(array('class' => $pager->CssClass)), $Pager);
