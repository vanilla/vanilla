<?php if (!defined('APPLICATION')) exit();
if (!function_exists('WriteReactions'))
   include $this->fetchViewLocation('reaction_functions', 'reactions', 'dashboard');

foreach ($this->data('Data', []) as $Record) {
   writeImageItem($Record, 'Tile ImageWrap Invisible');
}
