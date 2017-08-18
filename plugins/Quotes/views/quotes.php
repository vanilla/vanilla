<?php if (!defined('APPLICATION')) exit(); ?>
    <h2 class="H"><?php echo $this->data('Title'); ?></h2>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <?php if ($this->data('ForceEditing') && $this->data('ForceEditing') != FALSE) { ?>
            <div
                class="Warning"><?php echo sprintf(t("You are editing %s's quote settings"), $this->data('ForceEditing')); ?></div>
        <?php } ?>
        <li>
            <?php
            echo $this->Form->label('Quote Folding', 'QuoteFolding');
            echo wrap(t('How many levels deep should we start folding up quote trees?'), 'div');
            echo $this->Form->dropDown('QuoteFolding', $this->data('QuoteFoldingOptions'));
            ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save', '', ['class' => 'Button Primary']);
