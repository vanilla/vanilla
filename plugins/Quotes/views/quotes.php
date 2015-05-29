<?php if (!defined('APPLICATION')) exit(); ?>
    <h2 class="H"><?php echo $this->Data('Title'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
    <ul>
        <?php if ($this->Data('ForceEditing') && $this->Data('ForceEditing') != FALSE) { ?>
            <div
                class="Warning"><?php echo sprintf(T("You are editing %s's quote settings"), $this->Data('ForceEditing')); ?></div>
        <?php } ?>
        <li>
            <?php
            echo $this->Form->Label('Quote Folding', 'QuoteFolding');
            echo Wrap(t('How many levels deep should we start folding up quote trees?'), 'div');
            echo $this->Form->DropDown('QuoteFolding', $this->Data('QuoteFoldingOptions'));
            ?>
        </li>
    </ul>
<?php echo $this->Form->Close('Save', '', array('class' => 'Button Primary'));
