<?php if (!defined('APPLICATION')) exit();
echo heading(
    t('Manage Categories'),
    [
        [
            'text' => dashboardSymbol('settings'),
            'url' => '/vanilla/settings/categorysettings',
            'attributes' => [
                'class' => 'btn btn-icon-border js-modal',
                'aria-label' => t('Advanced Category Settings'),
                'data-reload-page-on-save' => false
            ]
        ],
        ['text' => t('Add Category'), 'url' => 'vanilla/settings/addcategory?parent='.$this->data('Category.CategoryID')],
    ]
);
writeCategoryBreadcrumbs($this->data('Ancestors', []));
?>

<div class="toolbar">
    <?php
    $filterOptions['hideContainerSelector'] = '.js-categories-list';
    echo categoryFilterBox($filterOptions);
    if ($this->data('UsePagination', false) === true) {
        PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']);
    }
    ?>
</div>
<div class="js-category-filter-container padded-top"></div>
<div class="js-nestable js-categories-list nestable padded-top" data-parent-id="<?php echo $this->data('ParentID', -1); ?>"><?php
    writeCategoryTree($this->data('Categories', []), $this->data('AllowSorting', true));
?></div>
