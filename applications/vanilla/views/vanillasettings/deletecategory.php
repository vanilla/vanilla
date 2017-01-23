<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t('Delete Category'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
if (is_object($this->OtherCategories)) {
    ?>
    <?php
    if ($this->OtherCategories->numRows() == 0) {
        ?>
        <div class="padded"><?php echo t('Are you sure you want to delete this category?'); ?></div>
    <?php
    } else { ?>
        <div class="alert alert-danger padded"><?php echo t('All content in this category will be permanently deleted.'); ?></div>
        <ul>
            <li class="form-group">
                <div class="input-wrap">
                <?php echo $this->Form->checkBox('MoveContent', "Move content in this category to a replacement category.", ['value' => '1']); ?>
                </div>
            </li>
            <li id="ReplacementCategory" class="form-group">
                <div class="label-wrap">
                    <?php
                    echo $this->Form->label('Replacement Category', 'ReplacementCategoryID');
                    ?>
                    <div id="ReplacementWarning" class="info"><div
                            class="text-danger"><?php echo t('<strong>Heads Up!</strong> Moving discussions into a replacement category can result in discussions vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></div>
                    </div>
                </div>
                <div class="input-wrap">
                <?php echo $this->Form->dropDown(
                    'ReplacementCategoryID',
                    $this->OtherCategories,
                    [
                        'ValueField' => 'CategoryID',
                        'TextField' => 'Name',
                        'IncludeNull' => true
                    ]);
                ?>
                </div>
            </li>
        </ul>

    <?php } ?>
    <?php echo $this->Form->close('Proceed'); ?>
<?php
}
