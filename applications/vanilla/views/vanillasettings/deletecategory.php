<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t('Delete Category'); ?></h1>
<?php

/** @var VanillaSettingsController $this */
/** @var Gdn_Form $form */
$form = $this->Form;
$subcategories = $this->data('Subcategories');

echo $form->open();
echo $form->errors();
if (is_object($this->OtherCategories)) {
    ?>
    <?php
    if ($this->OtherCategories->numRows() == 0) {
        ?>
        <div class="padded"><?php echo t('Are you sure you want to delete this category?'); ?></div>
    <?php
    } else { ?>
        <ul>
            <li class="form-group">
                <div class="input-wrap">
                <?php echo $form->radio('ContentAction', 'Move content from this category to a replacement category.', ['value' => 'move']); ?>
                </div>
                <div class="input-wrap">
                <?php echo $form->radio('ContentAction', 'Delete all the content in this category', ['value' => 'delete']); ?>
                </div>
            </li>
            <li id="ReplacementCategory" class="form-group">
                <div class="label-wrap">
                    <?php
                    echo $form->label('Replacement Category', 'ReplacementCategoryID');
                    ?>
                    <div id="ReplacementWarning" class="info"><div
                            class="text-danger"><?php echo t('<strong>Heads Up!</strong> Moving discussions into a replacement category can result in discussions vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></div>
                    </div>
                </div>
                <div class="input-wrap">
                <?php echo $form->dropDown(
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
            <li id="DeleteCategory">
            <?php if ($this->data('DiscussionsCount') || $subcategories) { ?>
                <div class="alert alert-danger padded">
                <?php if ($this->data('DiscussionsCount')) { ?>
                    <p>
                        <?php printf(t('<strong>%s</strong> discussions will be deleted. There will be no way to restore them. They will not go in the changelog!'), $this->data('DiscussionsCount')); ?>
                    </p>
                <?php
                }
                if ($subcategories) {
                ?>
                    <p>
                        <?php printf(t('<strong>%s</strong> Sub-categories will be deleted!'), count($subcategories)); ?>
                        <a tabindex="0" role="button" class="js-category-list-toggle" data-show="Show the category list" data-hide="Hide the category list">Show the category list</a>.
                    </p>
                    <div class="js-category-list">
                        <ul>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <li><?php echo $subcategory['Name'] ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php } ?>
                </div>
            <?php } ?>
                <div class="form-group">
                    <div class="input-wrap">
                        <?php echo $form->checkBox('ConfirmDelete', 'I understand and want to delete the category and all its content.'); ?>
                    </div>
                </div>
            </li>
        </ul>

    <?php } ?>
    <?php echo $form->close('Proceed'); ?>
<?php
}
