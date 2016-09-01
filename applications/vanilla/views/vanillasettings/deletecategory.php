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
        <div class="alert alert-danger padded"><?php echo t('All discussions in this category will be permanently deleted.'); ?></div>

        <?php
        // Only show the delete discussions checkbox if we're deleting a non-parent category.
        if ($this->Category->AllowDiscussions == '1') {
            ?>
            <li class="form-group row">
                <div class="input-wrap">
                <?php echo $this->Form->CheckBox('DeleteDiscussions', "Move discussions in this category to a replacement category.", array('value' => '1')); ?>
                </div>
            </li>
        <?php }
        ?>
        <li id="ReplacementCategory" class="form-group row">
            <div class="label-wrap">
            <?php
            echo $this->Form->label('Replacement Category', 'ReplacementCategoryID');
            if ($this->Category->AllowDiscussions == '1') {
                ?>
                <div id="ReplacementWarning" class="info"><div
                        class="text-danger"><?php echo t('<strong>Heads Up!</strong> Moving discussions into a replacement category can result in discussions vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></div>
                </div>
                <?php
            } ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown(
                'ReplacementCategoryID',
                $this->OtherCategories,
                array(
                    'ValueField' => 'CategoryID',
                    'TextField' => 'Name',
                    'IncludeNull' => TRUE
                ));
            ?>
            </div>
        </li>
        </ul>

    <?php } ?>
    <div class="form-footer js-modal-footer">
    <?php echo $this->Form->close('Proceed'); ?>
    </div>
<?php
}
