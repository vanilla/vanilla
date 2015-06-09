<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

if (!defined('APPLICATION')) {
    exit();
}
$UserPhotoFirst = c('Vanilla.Comment.UserPhotoFirst', true);

$Discussion = $this->data('Discussion');
$Author = Gdn::userModel()->getID($Discussion->InsertUserID); // UserBuilder($Discussion, 'Insert');

// Prep event args.
$CssClass = CssClass($Discussion, false);
$this->EventArguments['Discussion'] = &$Discussion;
$this->EventArguments['Author'] = &$Author;
$this->EventArguments['CssClass'] = &$CssClass;

// DEPRECATED ARGUMENTS (as of 2.1)
$this->EventArguments['Object'] = &$Discussion;
$this->EventArguments['Type'] = 'Discussion';

// Discussion template event
$this->fireEvent('BeforeDiscussionDisplay');
?>
<div id="<?php echo 'Discussion_'.$Discussion->DiscussionID; ?>" class="<?php echo $CssClass; ?>">
    <div class="Discussion">
        <div class="Item-Header DiscussionHeader">
            <div class="AuthorWrap">
            <span class="Author">
               <?php
               if ($UserPhotoFirst) {
                   echo userPhoto($Author);
                   echo userAnchor($Author, 'Username');
               } else {
                   echo userAnchor($Author, 'Username');
                   echo userPhoto($Author);
               }
               echo FormatMeAction($Discussion);
               ?>
            </span>
            <span class="AuthorInfo">
               <?php
               echo WrapIf(htmlspecialchars(val('Title', $Author)), 'span', array('class' => 'MItem AuthorTitle'));
               echo WrapIf(htmlspecialchars(val('Location', $Author)), 'span', array('class' => 'MItem AuthorLocation'));
               $this->fireEvent('AuthorInfo');
               ?>
            </span>
            </div>
            <div class="Meta DiscussionMeta">
            <span class="MItem DateCreated">
               <?php
               echo anchor(Gdn_Format::date($Discussion->DateInserted, 'html'), $Discussion->Url, 'Permalink', array('rel' => 'nofollow'));
               ?>
            </span>
                <?php
                echo DateUpdated($Discussion, array('<span class="MItem">', '</span>'));
                ?>
                <?php
                // Include source if one was set
                if ($Source = val('Source', $Discussion))
                    echo ' '.Wrap(sprintf(t('via %s'), t($Source.' Source', $Source)), 'span', array('class' => 'MItem MItem-Source')).' ';

                // Category
                if (c('Vanilla.Categories.Use')) {
                    echo ' <span class="MItem Category">';
                    echo ' '.t('in').' ';
                    echo anchor(htmlspecialchars($this->data('Discussion.Category')), CategoryUrl($this->data('Discussion.CategoryUrlCode')));
                    echo '</span> ';
                }
                $this->fireEvent('DiscussionInfo');
                $this->fireEvent('AfterDiscussionMeta'); // DEPRECATED
                ?>
            </div>
        </div>
        <?php $this->fireEvent('BeforeDiscussionBody'); ?>
        <div class="Item-BodyWrap">
            <div class="Item-Body">
                <div class="Message">
                    <?php
                    echo FormatBody($Discussion);
                    ?>
                </div>
                <?php
                $this->fireEvent('AfterDiscussionBody');
                WriteReactions($Discussion);
                if (val('Attachments', $Discussion)) {
                    WriteAttachments($Discussion->Attachments);
                }
                ?>
            </div>
        </div>
    </div>
</div>
