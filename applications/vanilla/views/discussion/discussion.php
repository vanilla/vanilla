<?php if (!defined('APPLICATION')) exit(); 

$Discussion = $this->Data('Discussion');
$Author = UserBuilder($Discussion, 'Insert');

// Prep event args
$this->EventArguments['Discussion'] = &$Discussion;
$this->EventArguments['Author'] = &$Author;

// DEPRECATED ARGUMENTS (as of 2.1)
$this->EventArguments['Object'] = &$Discussion; 
$this->EventArguments['Type'] = 'Discussion';

?>
<div id="<?php echo 'Discussion_'.$Discussion->DiscussionID; ?>" class="<?php echo CssClass($Discussion); ?>">
   <div class="DiscussionHeader">
      <div class="Meta">
         <span class="Author">
            <?php
            echo UserPhoto($Author);
            echo UserAnchor($Author);
            ?>
         </span>
         <span class="MItem DateCreated">
            <?php
            echo Anchor(Gdn_Format::Date($Discussion->DateInserted, 'html'), $Discussion->Url, 'Permalink', array('rel' => 'nofollow'));
            ?>
         </span>
         <?php
         // Category
         if (C('Vanilla.Categories.Use')) {
            echo ' <span class="Category">';
            echo ' '.T('in').' ';
            echo Anchor($this->Data('Discussion.Category'), 'categories/'.$this->Data('Discussion.CategoryUrlCode'));
            echo '</span> ';
         }
         ?>
         
         <?php $this->FireEvent('AfterDiscussionMeta'); ?>
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