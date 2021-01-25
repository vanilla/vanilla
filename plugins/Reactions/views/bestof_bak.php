<?php if (!defined('APPLICATION')) exit(); ?>
<?php echo wrap($this->data('Title'), 'h1 class="H"'); ?>
<div class="BestOfData">
   <?php echo Gdn_Theme::module('BestOfFilterModule'); ?>
   <div class="DataList BestOfList">
      <?php include_once('bestoflist.php'); ?>
   </div>
   <?php echo PagerModule::write(['MoreCode' => 'Load More']); ?>
   <div class="LoadingMore"></div>
</div>