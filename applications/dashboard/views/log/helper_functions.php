<?php if (!defined('APPLICATION')) exit;

function pluralCount($Count, $Type) {
    $PluralCodes = array('Activity' => '%s Activities');
    $SingleCode = '%s '.Gdn_Form::LabelCode($Type);
    return plural($Count, $SingleCode, val($Type, $PluralCodes, $SingleCode.'s'));
}

function otherRecordsMeta($Data) {
    if (!val('_Data', $Data)) {
        return '';
    }

    $Result = '<div><b>'.t('Other Records').':</b></div><div>';
    $_Data = val('_Data', $Data);
    foreach ($_Data as $Type => $Rows) {
        $Result .= '<span class="Meta"><span class="Meta-Value">'.
            PluralCount(count($Rows), $Type).
            '</span></span>';
    }
    $Result .= '</div>';
    return $Result;
}
