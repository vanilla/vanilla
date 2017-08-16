<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

$CountCheckedComments = val('CountCheckedComments', $this->Data, 0);

echo wrap(sprintf(
    t('Split %s to new discussion', 'You have chosen to split %s into a new discussion.'),
    plural($CountCheckedComments, '%s comment', '%s comments')
), 'p');
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('New Discussion Topic', 'Name');
            echo $this->Form->textBox('Name');
            ?>
        </li>
        <?php if ($this->ShowCategorySelector === TRUE) { ?>
            <li>
                <?php
                echo '<p><div class="Category">';
                echo $this->Form->label('Category', 'CategoryID'), ' ';
                echo $this->Form->categoryDropDown();
                echo '</div></p>';
                ?>
            </li>
        <?php } ?>
    </ul>
<?php
echo $this->Form->close('Create New Discussion');
