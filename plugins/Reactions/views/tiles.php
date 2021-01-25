<?php if (!defined('APPLICATION')) exit();
// Set the video embed size for this page explicitly (in memory only).
saveToConfig('Garden.Format.EmbedSize', '594x335', ['Save' => FALSE]);

if (!function_exists('WriteImageItem'))
   include $this->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');

echo wrap($this->data('Title'), 'h1 class="H"');
echo '<div class="BestOfWrap">';
   echo Gdn_Theme::module('BestOfFilterModule');
   echo '<div class="Tiles ImagesWrap">';
   foreach ($this->data('Data', []) as $Record) {
      writeImageItem($Record, 'Tile ImageWrap Invisible');
   }
   
   echo '</div>';
   echo PagerModule::write(['MoreCode' => 'Load More']); 
   echo '<div class="LoadingMore"></div>';
echo '</div>';