<?php if (!defined('APPLICATION')) { exit(); } ?>
<h1><?php echo $this->data('Title') ?></h1>
<div class="">
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<div class="P">
    <?php
//   echo $this->Form->label();
    echo $this->Form->radioList('Type', $this->data('_Types'), ['list' => true]);
    ?>
</div>

<?php
echo '<div class="Buttons Buttons-Confirm">',
    $this->Form->button(t('OK')), ' ',
    $this->Form->button(t('Cancel'), ['type' => 'button', 'class' => 'Button Close']),
    '</div>';
echo $this->Form->close();
?>
</div>
