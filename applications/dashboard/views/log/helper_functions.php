<?php if (!defined('APPLICATION')) exit;

function pluralCount($count, $type) {
    $pluralCodes = ['Activity' => '%s Activities'];
    $singleCode = '%s '.Gdn_Form::labelCode($type);
    return plural($count, $singleCode, val($type, $pluralCodes, $singleCode.'s'));
}

function otherRecordsMeta($data) {
    if (!val('_Data', $data)) {
        return '';
    }

    $result = '<div><b>'.t('Other Records').':</b></div><div>';
    $_Data = val('_Data', $data);
    foreach ($_Data as $type => $rows) {
        $result .= '<span class="Meta"><span class="Meta-Value">'.
            pluralCount(count($rows), $type).
            '</span></span>';
    }
    $result .= '</div>';
    return $result;
}
