<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
if ($this->data('warnings')) {
    echo '<div class="padded alert alert-warning"><ul>';

    foreach ($this->data('warnings') as $warning) {
        echo '<li>'.htmlspecialchars($warning)."</li>\n";
    }

    echo '</ul></div>';
}

$hidden = $this->data('_Hidden', false);
echo $this->Form->open(), $this->Form->errors();
//Add defaults as hidden so that we receive a value on post
if($hidden) {
    foreach($hidden as $field => $value){
       echo '<input type="hidden" name="'.$field.'" value="'.$value.'">';
    }
}
echo $this->Form->simple($this->data('_Controls'));

echo '<div class="js-modal-footer form-footer buttons">';
echo $this->Form->button('Generate Client ID and Secret', ['Name' => 'Generate', 'class' => 'btn btn-secondary js-generate']);
echo $this->Form->button('Save');
echo '</div>';

echo $this->Form->close();
