<?php if (!defined('APPLICATION')) exit();
if (!function_exists('WriteReactions'))
   include $this->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');

foreach ($this->data('Data', []) as $Record) {
   writeImageItem($Record, 'Tile ImageWrap Invisible');
}