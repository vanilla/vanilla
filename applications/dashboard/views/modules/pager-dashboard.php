<?php
$pager = Gdn::controller()->data('Pager');

$pagerType = !($pager->TotalRecords) ? 'more' : 'numbered';

$hasNext = true;

// Get total page count, allowing override
$pageCount = ceil($pager->TotalRecords / $pager->Limit);
$currentPage = pageNumber($pager->Offset, $pager->Limit);

if ($pagerType === 'numbered' && $currentPage >= $pageCount) {
    $hasNext = false;
}
if ($pagerType === 'more' && ($pager->CurrentRecords === false || $pager->CurrentRecords < $pager->Limit)) {
    $hasNext = false;
}

$pagerString = '<div class="pager">';

$pagerCount =  '<div class="pager-count">';
$pagerCount .= sprintf(t('Page %s of %s'), $currentPage, $pageCount ? $pageCount : 1);
$pagerCount .=  '</div>';

if ($pagerType === 'more') {
    $pagerCount =  '<div class="pager-count">';
    $pagerCount .= sprintf(t('Page %s'), $currentPage);
    $pagerCount .=  '</div>';
}

$pagerString .= $pagerCount;

$pagerString .= '<nav class="btn-group">';
// Previous
if ($currentPage == 1) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$pagerString .= anchor(dashboardSymbol("chevron-left"), $pager->pageUrl($currentPage - 1), $disabled.' Previous pager-previous btn btn-icon-border', ['rel' => 'prev', 'aria-label' => 'Previous page']);

// Next
if (!$hasNext) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
$pagerString .= anchor(dashboardSymbol("chevron-right"), $pager->pageUrl($currentPage + 1), $disabled.' Next pager-next btn btn-icon-border', ['rel' => 'next', 'aria-label' => 'Next page']); // extra sprintf parameter in case old url style is set
$pagerString .= '</nav></div>';

echo '<div class="pager-wrap '.$pager->CssClass.'">'.$pagerString.'</div>';
