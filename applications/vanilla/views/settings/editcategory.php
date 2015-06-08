<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
<h1><?php echo t('Edit Category'); ?></h1>
<ul>
    <li>
        <?php
        echo $this->Form->label('Category', 'Name');
        echo $this->Form->textBox('Name');
        ?>
    </li>
    <li id="UrlCode">
        <?php
        echo wrap(t('Category Url:'), 'strong');
        echo ' ';
        echo Gdn::request()->Url('categories', true);
        echo '/';
        echo wrap(htmlspecialchars($this->Form->getValue('UrlCode')));
        echo $this->Form->textBox('UrlCode');
        echo '/';
        echo anchor(t('edit'), '#', 'Edit');
        echo anchor(t('OK'), '#', 'Save SmallButton');
        ?>
    </li>
    <li>
        <?php
        echo $this->Form->label('Description', 'Description');
        echo $this->Form->textBox('Description', array('MultiLine' => TRUE));
        ?>
    </li>
    <li>
        <?php
        echo $this->Form->label('Css Class', 'CssClass');
        echo $this->Form->textBox('CssClass', array('MultiLine' => FALSE));
        ?>
    </li>
    <li>
        <?php
        echo $this->Form->label('Photo', 'PhotoUpload');
        if ($Photo = $this->Form->getValue('Photo')) {
            echo img(Gdn_Upload::url($Photo));
            echo '<br />'.anchor(t('Delete Photo'),
                    CombinePaths(array('vanilla/settings/deletecategoryphoto', $this->Category->CategoryID, Gdn::session()->TransientKey())),
                    'SmallButton Danger PopConfirm');
        }
        echo $this->Form->Input('PhotoUpload', 'file');
        ?>
    </li>
    <?php
    echo $this->Form->Simple(
        $this->data('_ExtendedFields', array()),
        array('Wrap' => array('', '')));
    ?>
    <li>
        <?php
        echo $this->Form->label('Display As', 'DisplayAs');
        echo $this->Form->DropDown('DisplayAs', array('Default' => 'Default', 'Categories' => 'Categories', 'Discussions' => 'Discussions', 'Heading' => 'Heading'));
        ?>
    </li>
    <li>
        <?php
        echo $this->Form->CheckBox('HideAllDiscussions', 'Hide from the recent discussions page.');
        ?>
    </li>
    <?php if ($this->ShowCustomPoints): ?>
        <li>
            <?php
            echo $this->Form->CheckBox('CustomPoints', 'Track points for this category separately.');
            ?>
        </li>
    <?php endif; ?>
    <li>
        <?php
        echo $this->Form->CheckBox('Archived', 'This category is archived.');
        ?>
    </li>
    <?php $this->fireEvent('AfterCategorySettings'); ?>
    <li>
        <?php
        if (count($this->PermissionData) > 0) {
            echo $this->Form->CheckBox('CustomPermissions', 'This category has custom permissions.');

            echo '<div class="CategoryPermissions">';

            if (count($this->data('DiscussionTypes')) > 1) {
                echo '<div class="P DiscussionTypes">';
                echo $this->Form->label('Discussion Types');
                foreach ($this->data('DiscussionTypes') as $Type => $Row) {
                    echo $this->Form->CheckBox("AllowedDiscussionTypes[]", val('Plural', $Row, $Type), array('value' => $Type));
                }
                echo '</div>';
            }

            echo $this->Form->Simple(
                $this->data('_PermissionFields', array()),
                array('Wrap' => array('', ''), 'ItemWrap' => array('<div class="P">', '</div>')));

            echo t('Check all permissions that apply for each role');
            echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
            echo '</div>';
        }
        ?>
    </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
