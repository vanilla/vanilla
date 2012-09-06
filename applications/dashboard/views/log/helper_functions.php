<?php if (!defined('APPLICATION')) exit;

function PluralCount($Count, $Type) {
   $PluralCodes = array('Activity' => '%s Activities');
   $SingleCode = '%s '.Gdn_Form::LabelCode($Type);
   return Plural($Count, $SingleCode, GetValue($Type, $PluralCodes, $SingleCode.'s'));
}

function OtherRecordsMeta($Data) {
   if (!GetValue('_Data', $Data)) {
      return '';
   }
   
   $Result = '<div><b>'.T('Other Records').':</b></div><div>';
   $_Data = GetValue('_Data', $Data);
   foreach ($_Data as $Type => $Rows) {
      $Result .= '<span class="Meta"><span class="Meta-Value">'.
      PluralCount(count($Rows), $Type).
         '</span></span>';
   }
   $Result .= '</div>';
   return $Result;
}