<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo t('Add Category'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->hidden('ParentCategoryID');
?>
<?php
Gdn_Theme::assetBegin('Help');
    echo wrap(sprintf(t('About %s'), t('Categories')), 'h2');
    echo t('Categories are used to organize discussions.', 'Categories allow you to organize your discussions.');
Gdn_Theme::assetEnd();
?>
<ul>
    <li class="form-group row">
        <div class="label-wrap">
        <?php echo $this->Form->label('Category', 'Name'); ?>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->textBox('Name'); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
            <?php echo wrap(t('Category Url:'), 'strong'); ?>
        </div>
        <div id="UrlCode" class="input-wrap category-url-code">
            <?php
            echo '<div class="text-control-height">';
            echo Gdn::request()->Url('categories', true);
            echo '/';
            echo wrap(htmlspecialchars($this->Form->getValue('UrlCode')));
            echo '</div>';
            echo $this->Form->textBox('UrlCode');
            echo ($this->Form->getValue('UrlCode')) ? '/' : '';
            echo anchor(t('edit'), '#', 'Edit btn btn-link');
            echo anchor(t('OK'), '#', 'Save btn btn-primary');
            ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
        <?php echo $this->Form->label('Description', 'Description'); ?>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->textBox('Description', array('MultiLine' => TRUE)); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
        <?php echo $this->Form->label('Css Class', 'CssClass'); ?>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->textBox('CssClass', array('MultiLine' => FALSE)); ?>
        </div>
    </li>
    <li class="form-group row">
        <div class="label-wrap">
        <?php echo $this->Form->label('Photo', 'PhotoUpload');
        if ($Photo = $this->Form->getValue('Photo')) {
            echo img(Gdn_Upload::url($Photo));
            echo '<br />'.anchor(t('Delete Photo'),
                    CombinePaths(array('vanilla/settings/deletecategoryphoto', $this->Category->CategoryID, Gdn::session()->TransientKey())),
                    'SmallButton Danger PopConfirm');
        } ?>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->fileUpload('PhotoUpload'); ?>
        </div>
    </li>
    <?php echo $this->Form->Simple(
        $this->data('_ExtendedFields', array()));
    ?>
    <li class="form-group row">
        <div class="label-wrap">
        <?php echo $this->Form->label('Display As', 'DisplayAs'); ?>
        </div>
        <div class="input-wrap">
        <?php echo $this->Form->DropDown('DisplayAs', array('Default' => 'Default', 'Categories' => 'Categories', 'Discussions' => 'Discussions', 'Heading' => 'Heading'), ['Wrap' => true]); ?>
        </div>
    </li>
    <li class="form-group row">
        <?php echo $this->Form->toggle('HideAllDiscussions', 'Hide from the recent discussions page.'); ?>
    </li>
    <?php if ($this->ShowCustomPoints): ?>
        <li class="form-group row">
            <?php echo $this->Form->toggle('CustomPoints', 'Track points for this category separately.'); ?>
        </li>
    <?php endif; ?>
    <?php if (count($this->PermissionData) > 0) { ?>
        <li id="Permissions" class="form-group row">
            <?php echo $this->Form->toggle('CustomPermissions', 'This category has custom permissions.'); ?>
        </li>
    <?php } ?>
</ul>
<?php
echo '<div class="CategoryPermissions">';
if (count($this->data('DiscussionTypes')) > 1) {
    echo '<div class="P DiscussionTypes form-group row">';
    echo '<div class="label-wrap">';
    echo $this->Form->label('Discussion Types');
    echo '</div>';
    echo '<div class="checkbox-list input-wrap">';
    foreach ($this->data('DiscussionTypes') as $Type => $Row) {
        echo $this->Form->CheckBox("AllowedDiscussionTypes[]", val('Plural', $Row, $Type), array('value' => $Type));
    }
    echo '</div>';
    echo '</div>';
}

echo $this->Form->Simple(
    $this->data('_PermissionFields', array()),
    array('Wrap' => array('<div class="form-group row">', '</div>'), 'ItemWrap' => array('<div class="input-wrap">', '</div>')));

echo '<div class="padded">'.sprintf(t('%s: %s'), t('Check all permissions that apply for each role'), '').'</div>';
echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
echo '</div>';
?>
<div class="form-footer js-modal-footer">
<?php echo $this->Form->close('Save'); ?>
</div>
