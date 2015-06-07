<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
$NewOrDraft = !isset($this->Comment) || property_exists($this->Comment, 'DraftID') ? TRUE : FALSE;
$Editing = isset($this->Comment);

$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm FormTitleWrapper';
$this->fireEvent('BeforeCommentForm');
?>
<div class="<?php echo $this->EventArguments['FormCssClass']; ?>">
    <h2 class="H"><?php echo t($Editing ? 'Edit Comment' : 'Leave a Comment'); ?></h2>

    <div class="CommentFormWrap">
        <?php if (Gdn::session()->isValid()): ?>
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
                    echo $this->Form->open(array('id' => 'Form_Comment'));
                    echo $this->Form->errors();
                    //               $CommentOptions = array('MultiLine' => true, 'format' => valr('Comment.Format', $this));
                    $this->fireEvent('BeforeBodyField');

                    echo $this->Form->bodyBox('Body', array('Table' => 'Comment', 'tabindex' => 1, 'FileUpload' => true));

                    echo '<div class="CommentOptions List Inline">';
                    $this->fireEvent('AfterBodyField');
                    echo '</div>';


                    echo "<div class=\"Buttons\">\n";
                    $this->fireEvent('BeforeFormButtons');
                    $CancelText = t('Home');
                    $CancelClass = 'Back';
                    if (!$NewOrDraft || $Editing) {
                        $CancelText = t('Cancel');
                        $CancelClass = 'Cancel';
                    }

                    echo '<span class="'.$CancelClass.'">';
                    echo anchor($CancelText, '/');

                    if ($CategoryID = $this->data('Discussion.CategoryID')) {
                        $Category = CategoryModel::categories($CategoryID);
                        if ($Category)
                            echo ' <span class="Bullet">•</span> '.anchor(htmlspecialchars($Category['Name']), $Category['Url']);
                    }

                    echo '</span>';

                    $ButtonOptions = array('class' => 'Button Primary CommentButton');
                    $ButtonOptions['tabindex'] = 2;
                    /*
                    Caused non-root users to not be able to add comments. Must take categories
                    into account. Look at CheckPermission for more information.
                    if (!Gdn::session()->checkPermission('Vanilla.Comment.Add'))
                       $ButtonOptions['Disabled'] = 'disabled';
                    */

                    if (!$Editing && $Session->isValid()) {
                        echo ' '.anchor(t('Preview'), '#', 'Button PreviewButton')."\n";
                        echo ' '.anchor(t('Edit'), '#', 'Button WriteButton Hidden')."\n";
                        if ($NewOrDraft)
                            echo ' '.anchor(t('Save Draft'), '#', 'Button DraftButton')."\n";
                    }
                    if ($Session->isValid())
                        echo $this->Form->button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
                    else {
                        $AllowSigninPopup = c('Garden.SignIn.Popup');
                        $Attributes = array('tabindex' => '-1');
                        if (!$AllowSigninPopup)
                            $Attributes['target'] = '_parent';

                        $AuthenticationUrl = SignInUrl($this->data('ForeignUrl', '/'));
                        $CssClass = 'Button Primary Stash';
                        if ($AllowSigninPopup)
                            $CssClass .= ' SignInPopup';

                        echo anchor(t('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
                    }

                    $this->fireEvent('AfterFormButtons');
                    echo "</div>\n";
                    echo $this->Form->close();
                    //               echo '</div>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
