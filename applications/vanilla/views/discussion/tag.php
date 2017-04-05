<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open(['id' => 'DiscussionAddTagForm']);
echo $form->errors();

echo '<div class="Form-Tags P">';
    // Tag text box
    echo $this->Form->label('Tags', 'Tags');
    echo $this->Form->textBox('Tags', array('data-tags' => ''));
echo '</div>';

?>
<?php echo $this->Form->close('Save');
