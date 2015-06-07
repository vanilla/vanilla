<?php if (!defined('APPLICATION')) exit();

if ($this->data('_CssClass')) {
    if ($this->data('_Value')) {
        echo ' <span class="'.$this->data('_CssClass').'">'.$this->data('_Value').'</span>';
    }
} else {
    echo $this->data('_Value');
}
