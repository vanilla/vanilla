<?php if (!defined('APPLICATION')) exit();

include_once Gdn::controller()->fetchViewLocation('helper_functions', 'categories', 'vanilla');
$categoryID = isset($this->_Sender->CategoryID) ? $this->_Sender->CategoryID : '';
$defaultParams = ['followed' => true, 'save' => 1, 'TransientKey' => $this->transientKey];
if ($this->Data && $this->Data->count() > 0) {
    ?>
    <div class="Box BoxFollowedCategories">
        <h4 id="BoxFollowedCategoriesTitle" class="BoxFilter-Heading">
            <?php echo t('Followed Categories'); ?>
        </h4>
        <?php writeCategoryList($this->Data->result()); ?>
        <?php if ($this->hasMoreResult): ?>
            <div class="MoreWrap">
                <?php echo wrap(
                    anchor(htmlspecialchars(t('View All')), 'categories?'.http_build_query($defaultParams)),
                    'span',
                    ['class' => 'MItem Category']
                ); ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}
