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
    echo $form->textBox('Tags', ['data-tags' => '']);
echo '</div>';

?>
<?php
echo '<div class="Buttons Buttons-Confirm">';
echo    $form->button('Save', ['class' => 'Button Primary']);
echo    $form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
echo '</div>';
echo $form->close();
