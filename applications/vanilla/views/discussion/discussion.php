<?php if (!defined('APPLICATION')) exit(); 
$UserPhotoFirst = C('Vanilla.Comment.UserPhotoFirst', TRUE);

$Discussion = $this->Data('Discussion');
$Author = Gdn::UserModel()->GetID($Discussion->InsertUserID); // UserBuilder($Discussion, 'Insert');

// Prep event args.
$CssClass = CssClass($Discussion, FALSE);
$this->EventArguments['Discussion'] = &$Discussion;
$this->EventArguments['Author'] = &$Author;
$this->EventArguments['CssClass'] = &$CssClass;

// DEPRECATED ARGUMENTS (as of 2.1)
$this->EventArguments['Object'] = &$Discussion; 
$this->EventArguments['Type'] = 'Discussion';

// Discussion template event
$this->FireEvent('BeforeDiscussionDisplay');
?>
<div id="<?php echo 'Discussion_'.$Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
   <div class="Discussion">
      <div class="Item-Header DiscussionHeader">
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               if ($UserPhotoFirst) {
                  echo UserPhoto($Author);
                  echo UserAnchor($Author, 'Username');
               } else {
                  echo UserAnchor($Author, 'Username');
                  echo UserPhoto($Author);
               }
               echo FormatMeAction($Discussion);
               ?>
            </span>
            <span class="AuthorInfo">
               <?php
               echo WrapIf(htmlspecialchars(GetValue('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
               echo WrapIf(htmlspecialchars(GetValue('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
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
               echo DateUpdated($Discussion, array('<span class="MItem">', '</span>'));
            ?>
            <?php
            // Include source if one was set
            if ($Source = GetValue('Source', $Discussion))
               echo ' '.Wrap(sprintf(T('via %s'), T($Source.' Source', $Source)), 'span', array('class' => 'MItem MItem-Source')).' ';
            
            // Category
            if (C('Vanilla.Categories.Use')) {
               echo ' <span class="MItem Category">';
               echo ' '.T('in').' ';
               echo Anchor(htmlspecialchars($this->Data('Discussion.Category')), CategoryUrl($this->Data('Discussion.CategoryUrlCode')));
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
            <?php 
            $this->FireEvent('AfterDiscussionBody');
            WriteReactions($Discussion);
            ?>
         </div>
      </div>
   </div>
</div>
