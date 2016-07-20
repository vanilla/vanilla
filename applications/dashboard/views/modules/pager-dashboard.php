<?php
$pager = Gdn::controller()->data('Pager');
// Get total page count, allowing override
$pageCount = ceil($pager->TotalRecords / $pager->Limit);
$currentPage = PageNumber($pager->Offset, $pager->Limit);

$pagerString = '<div class="pager">';
if ($currentPage >= $pageCount) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$pagerString .=  '<div class="pager-count">';
$pagerString .= sprintf(t('Page %s of %s'), $currentPage, $pageCount ? $pageCount : 1);
$pagerString .=  '</div>';

$pagerString .= '<div class="btn-group">';
// Previous
if ($currentPage == 1) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$pagerString .= anchor(dashboardSymbol("chevron-left"), $pager->PageUrl($currentPage - 1), $disabled.' Previous pager-previous btn btn-icon-border', array('rel' => 'prev', 'aria-label' => 'Previous page'));

// Next
if ($currentPage >= $pageCount) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$pagerString .= anchor(dashboardSymbol("chevron-right"), $pager->PageUrl($currentPage + 1), $disabled.' Next pager-next btn btn-icon-border', array('rel' => 'next', 'aria-label' => 'Next page')); // extra sprintf parameter in case old url style is set
$pagerString .= '</div></div>';

echo '<div class="pager-wrap">'.$pagerString.'</div>';
