<?php if (!defined('APPLICATION')) exit(); 
$UserPhotoFirst = C('Vanilla.Comment.UserPhotoFirst', TRUE);

$Discussion = $this->Data('Discussion');
$Author = Gdn::UserModel()->GetID($Discussion->InsertUserID); // UserBuilder($Discussion, 'Insert');

// Prep event args
$this->EventArguments['Discussion'] = &$Discussion;
$this->EventArguments['Author'] = &$Author;

// DEPRECATED ARGUMENTS (as of 2.1)
$this->EventArguments['Object'] = &$Discussion; 
$this->EventArguments['Type'] = 'Discussion';

?>
<div id="<?php echo 'Discussion_'.$Discussion->DiscussionID; ?>" class="<?php echo CssClass($Discussion); ?>">
   <div class="Discussion">
      <div class="Item-Header DiscussionHeader">
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               if ($UserPhotoFirst) {
                  echo UserPhoto($Author);
                  echo UserAnchor($Author);
               } else {
                  echo UserAnchor($Author);
                  echo UserPhoto($Author);
               }
               ?>
            </span>
            <span class="AuthorInfo">
               <?php
               echo WrapIf(htmlspecialchars(GetValue('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
               $this->FireEvent('AuthorInfo'); 
               ?>
            </span>
         </div>
         <div class="Meta DiscussionMeta">
            <span class="MItem DateCreated">
               <?php
               echo Anchor(Gdn_Format::Date($Discussion->DateInserted, 'html'), $Discussion->Url, 'Permalink', array('rel' => 'nofollow'));
               ?>
            </span>
            <?php
            // Category
            if (C('Vanilla.Categories.Use')) {
               echo ' <span class="MItem Category">';
               echo ' '.T('in').' ';
               echo Anchor($this->Data('Discussion.Category'), CategoryUrl($this->Data('Discussion.CategoryUrlCode')));
               echo '</span> ';
            }
            $this->FireEvent('DiscussionInfo');
            $this->FireEvent('AfterDiscussionMeta'); // DEPRECATED
            ?>
         </div>
      </div>
      <?php $this->FireEvent('BeforeDiscussionBody'); ?>
      <div class="Item-BodyWrap">
         <div class="Item-Body">
            <div class="Message">   
               <?php
                  echo FormatBody($Discussion);
               ?>
            </div>
         </div>
      </div>
      <?php 
      $this->FireEvent('AfterDiscussionBody');
      WriteReactions($Discussion);
      ?>
   </div>
</div>