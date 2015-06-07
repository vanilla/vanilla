<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php
        if (is_object($this->Message))
            echo t('Edit Message');
        else
            echo t('Add Message');
        ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Page', 'Location');
            echo $this->Form->DropDown('Location', $this->data('Locations'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Category', 'CategoryID');
            echo $this->Form->DropDown('CategoryID', $this->data('Categories'), array('IncludeNull' => t('All Categories')));
            echo $this->Form->CheckBox('IncludeSubcategories', 'Include Subcategories');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Position', 'AssetTarget');
            echo $this->Form->DropDown('AssetTarget', $this->AssetData);
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Message', 'Content');
            echo $this->Form->textBox('Content', array('MultiLine' => TRUE));
            ?>
        </li>
        <li class="MessageExamples">
            <?php
            $Style = ' style="display: inline; padding: 2px 6px 2px 6px; margin: 0 6px 0 0;"';
            echo $this->Form->label('Appearance', 'CssClass');
            echo $this->Form->Radio('CssClass', '', array('value' => 'CasualMessage'));
            echo '<div class="CasualMessage"'.$Style.'>'.t('Casual').'</div>';
            echo $this->Form->Radio('CssClass', '', array('value' => 'InfoMessage'));
            echo '<div class="InfoMessage"'.$Style.'>'.t('Information').'</div>';
            echo $this->Form->Radio('CssClass', '', array('value' => 'AlertMessage'));
            echo '<div class="AlertMessage"'.$Style.'>'.t('Alert').'</div>';
            echo $this->Form->Radio('CssClass', '', array('value' => 'WarningMessage'));
            echo '<div class="WarningMessage"'.$Style.'>'.t('Warning').'</div>';
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->CheckBox('AllowDismiss', 'Allow users to dismiss this message', array('value' => '1'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->CheckBox('Enabled', 'Enable this message', array('value' => '1'));
            ?>
        </li>
    </ul>
<?php echo $this->Form->close('Save');
