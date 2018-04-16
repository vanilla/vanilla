<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::session();
$NewOrDraft = !isset($this->Comment) || property_exists($this->Comment, 'DraftID') ? true : false;
$Editing = isset($this->Comment);
$formCssClass = 'MessageForm CommentForm FormTitleWrapper';
$this->EventArguments['FormCssClass'] = &$formCssClass;
$this->fireEvent('BeforeCommentForm');
?>
<div class="<?php echo $formCssClass; ?>">
    <h2 class="H"><?php echo t($Editing ? 'Edit Comment' : 'Leave a Comment'); ?></h2>

    <div class="CommentFormWrap">
        <?php if (Gdn::session()->isValid()) : ?>
            <div class="Form-HeaderWrap">
                <div class="Form-Header">
            <span class="Author">
                <?php writeCommentFormHeader(); ?>
            </span>
                </div>
            </div>
        <?php endif; ?>
        <div class="Form-BodyWrap">
            <div class="Form-Body">
                <div class="FormWrapper FormWrapper-Condensed">
                    <?php
                    echo $this->Form->open(['id' => 'Form_Comment']);
                    echo $this->Form->errors();
                    $this->fireEvent('BeforeBodyField');

                    echo $this->Form->bodyBox('Body', ['Table' => 'Comment', 'FileUpload' => true]);

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
                    if ($this->data('Editor.BackLink')) {
                        echo ' <span class="Bullet">•</span> '.$this->data('Editor.BackLink') ;
                    }
                    echo '</span>';

                    $ButtonOptions = ['class' => 'Button Primary CommentButton'];
                    $ButtonOptions['tabindex'] = 1;

                    if (!$Editing && $Session->isValid()) {
                        echo ' '.anchor(t('Preview'), '#', 'Button PreviewButton')."\n";
                        echo ' '.anchor(t('Edit'), '#', 'Button WriteButton Hidden')."\n";
                        if ($NewOrDraft) {
                            echo ' '.anchor(t('Save Draft'), '#', 'Button DraftButton')."\n";
                        }
                    }

                    if ($Session->isValid()) {
                        echo $this->Form->button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
                    } else {
                        $AllowSigninPopup = c('Garden.SignIn.Popup');
                        $Attributes = ['tabindex' => '-1'];
                        if (!$AllowSigninPopup) {
                            $Attributes['target'] = '_parent';
                        }
                        $AuthenticationUrl = signInUrl($this->SelfUrl);
                        $CssClass = 'Button Primary Stash';
                        if ($AllowSigninPopup) {
                            $CssClass .= ' SignInPopup';
                        }
                        echo anchor(t('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
                    }

                    $this->fireEvent('AfterFormButtons');
                    echo "</div>\n";
                    echo $this->Form->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
