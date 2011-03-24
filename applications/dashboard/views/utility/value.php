<?php if (!defined('APPLICATION')) exit();

if ($this->Data('_CssClass')) {
   if ($this->Data('_Value')) {
      echo ' <span class="'.$this->Data('_Value').'">'.$this->Data('_Value').'</span>';
   }
} else {
   echo $this->Data('_Value');
}