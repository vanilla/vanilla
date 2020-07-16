<?php if (!defined('APPLICATION')) { exit(); } ?>
<h1><?php echo $this->data('Title') ?></h1>
<div class="">
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<div class="P">
    <?php
    echo '<i>'.t('Did this answer the question?').'</i>';
    echo htmlspecialchars($this->Form->getFormValue('QnA'));
    echo $this->Form->radioList('QnA', $this->data('_QnAs'), ['list' => true]);
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
