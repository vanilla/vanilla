<?php if (!defined('APPLICATION')) exit;

function PluralCount($Count, $Type) {
   $PluralCodes = array('Activity' => '%s Activities');
   $SingleCode = '%s '.Gdn_Form::LabelCode($Type);
   return Plural($Count, $SingleCode, GetValue($Type, $PluralCodes, $SingleCode.'s'));
}

function OtherRecordsMeta($Data) {
   if (!isset($Data['_Data'])) {
      return '';
   }
   
   
   $Result = '<div><b>'.T('Other Records').':</b></div><div>';
   $_Data = $Data['_Data'];
   foreach ($_Data as $Type => $Rows) {
      $Result .= '<span class="Meta"><span class="Meta-Value">'.
      PluralCount(count($Rows), $Type).
         '</span></span>';
   }
   $Result .= '</div>';
   return $Result;
}