<?php if (!defined('APPLICATION')) exit(); 
$Discussion = $this->Data('Discussion');
?>
<div id="<?php echo 'Discussion_'.$Discussion->DiscussionID; ?>" class="Item Item-Discussion">
   <div class="Discussion-Header">
      <div class="Meta">
         <span class="Author">
            <?php
            echo UserPhoto($Discussion, array('Px' => 'Insert'));
            echo UserAnchor($Discussion, array('Px' => 'Insert'));
            ?>
         </span>
         <span class="MItem DateCreated">
            <?php
            echo Anchor(Gdn_Format::Date($Discussion->DateInserted, 'html'), $Discussion->Url, 'Permalink', array('rel' => 'nofollow'));
            ?>
         </span>
      </div>
   </div>
   <?php $this->FireEvent('BeforeDiscussionBody'); ?>
   <div class="Message">   
      <?php
         echo FormatBody($Discussion);
      ?>
   </div>
   <?php $this->FireEvent('AfterDiscussionBody'); ?>
</div>