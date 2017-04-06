<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open(['id' => 'DiscussionAddTagForm']);
echo $form->errors();

echo '<p>'.t('Which tags would you like to add to the discussion?').'</p>';

echo '<div class="Form-Tags P">';
    // Tag text box
    echo $form->label('Tags', 'Tags');
    echo $form->textBox('Tags', array('data-tags' => ''));
echo '</div>';

?>
<?php
echo '<div class="Buttons Buttons-Confirm">';
echo    $this->Form->button('Save', array('class' => 'btn btn-primary'));
echo    $this->Form->button('Cancel', array('type' => 'button', 'class' => 'btn Close'));
echo '</div>';
echo $form->close();
