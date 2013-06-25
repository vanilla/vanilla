<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$NewOrDraft = !isset($this->Comment) || property_exists($this->Comment, 'DraftID') ? TRUE : FALSE;
$Editing = isset($this->Comment);

$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm FormTitleWrapper';
$this->FireEvent('BeforeCommentForm');
?>
<div class="<?php echo $this->EventArguments['FormCssClass']; ?>">
   <h2 class="H"><?php echo T($Editing ? 'Edit Comment' : 'Leave a Comment'); ?></h2>
   <div class="CommentFormWrap">
      <?php if (Gdn::Session()->IsValid()): ?>
      <div class="Form-HeaderWrap">
         <div class="Form-Header">
            <span class="Author">
               <?php
               WriteCommentFormHeader();
               ?>
            </span>
         </div>
      </div>
      <?php endif; ?>
      <div class="Form-BodyWrap">
         <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
               <?php
               echo $this->Form->Open(array('id' => 'Form_Comment'));
               echo $this->Form->Errors();
//               $CommentOptions = array('MultiLine' => TRUE, 'format' => GetValueR('Comment.Format', $this));
               $this->FireEvent('BeforeBodyField');
               
               echo $this->Form->BodyBox('Body', array('Table' => 'Comment', 'tabindex' => 1));
               
               echo '<div class="CommentOptions List Inline">';
               $this->FireEvent('AfterBodyField');
               echo '</div>';
               
               
               echo "<div class=\"Buttons\">\n";
               $this->FireEvent('BeforeFormButtons');
               $CancelText = T('Home');
               $CancelClass = 'Back';
               if (!$NewOrDraft || $Editing) {
                  $CancelText = T('Cancel');
                  $CancelClass = 'Cancel';
               }

               echo '<span class="'.$CancelClass.'">';
               echo Anchor($CancelText, '/');

               if ($CategoryID = $this->Data('Discussion.CategoryID')) {
                  $Category = CategoryModel::Categories($CategoryID);
                  if ($Category)
                     echo ' <span class="Bullet">•</span> '.Anchor(htmlspecialchars($Category['Name']), $Category['Url']);
               }

               echo '</span>';

               $ButtonOptions = array('class' => 'Button Primary CommentButton');
               $ButtonOptions['tabindex'] = 2;
               /*
               Caused non-root users to not be able to add comments. Must take categories
               into account. Look at CheckPermission for more information.
               if (!Gdn::Session()->CheckPermission('Vanilla.Comment.Add'))
                  $ButtonOptions['Disabled'] = 'disabled';
               */

               if (!$Editing && $Session->IsValid()) {
                  echo ' '.Anchor(T('Preview'), '#', 'Button PreviewButton')."\n";
                  echo ' '.Anchor(T('Edit'), '#', 'Button WriteButton Hidden')."\n";
                  if ($NewOrDraft)
                     echo ' '.Anchor(T('Save Draft'), '#', 'Button DraftButton')."\n";
               }
               if ($Session->IsValid())
                  echo $this->Form->Button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
               else {
                  $AllowSigninPopup = C('Garden.SignIn.Popup');
                  $Attributes = array('tabindex' => '-1');
                  if (!$AllowSigninPopup)
                     $Attributes['target'] = '_parent';

                  $AuthenticationUrl = SignInUrl($this->Data('ForeignUrl', '/'));
                  $CssClass = 'Button Primary Stash';
                  if ($AllowSigninPopup)
                     $CssClass .= ' SignInPopup';

                  echo Anchor(T('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
               }

               $this->FireEvent('AfterFormButtons');
               echo "</div>\n";
               echo $this->Form->Close();
//               echo '</div>';
               ?>
            </div>
         </div>
      </div>
   </div>
</div>