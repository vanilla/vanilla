<?php if (!defined('APPLICATION')) exit();

/**
 * ToggleMenu Module
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class ToggleMenuModule extends Gdn_Module {
   
   private $_Labels = array();
   public function AddLabel($Name, $Code = '', $Url = '') {
      if ($Code == '')
         $Code = Gdn_Format::Url(ucwords(trim(Gdn_Format::PlainText($Name))));

      $this->_Labels[] = array('Name' => $Name, 'Code' => $Code, 'Url' => $Url);
   }
   
   private $_CurrentLabelCode = FALSE;
   public function CurrentLabelCode($Label = '') {
      if ($Label != '')
         $this->_CurrentLabelCode = $Label;
      
      // If the current code hasn't been assigned, use the first available label
      if (!$this->_CurrentLabelCode && count($this->_Labels) > 0)
         return $this->_Labels[0]['Code'];

      return $this->_CurrentLabelCode;
   }
   
   public function ToString() {
      $Return = '<ul class="FilterMenu ToggleMenu">';
      foreach ($this->_Labels as $Label) {
         $Url = GetValue('Url', $Label, '');
         if ($Url == '')
            $Url = '#';
         
         $Name = GetValue('Name', $Label, '');
         $Code = GetValue('Code', $Label, '');
         $Active = strcasecmp($Code, $this->CurrentLabelCode()) == 0;
         $CssClass = 'Handle-'.$Code;
         $AnchorClass = '';
         if ($Active) {
            $CssClass .= ' Active';
            $AnchorClass = 'TextColor';
         }

         $Return .= '<li class="'.$CssClass.'">';
            $Return .= Anchor($Name, $Url, $AnchorClass);
         $Return .= '</li>';
      }
      $Return .= '</ul>';   
      return $Return;
   }
}