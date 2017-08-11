<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();

$CountAllowed = val('CountAllowed', $this->Data, 0);
$CountNotAllowed = val('CountNotAllowed', $this->Data, 0);
$CountCheckedDiscussions = val('CountCheckedDiscussions', $this->Data, 0);

if ($CountNotAllowed > 0) {
    echo wrap(sprintf(
        t('You do not have permission to move %1$s of the selected discussions.'),
        $CountNotAllowed
    ), 'p');

    echo wrap(sprintf(
        t('You are about to move %1$s of the %2$s of the selected discussions.'),
        $CountAllowed,
        $CountCheckedDiscussions
    ), 'p');
} else {
    echo wrap(sprintf(
        t('You are about to move %s.'),
        plural($CountCheckedDiscussions, '%s discussion', '%s discussions')
    ), 'p');
}
?>
    <ul>
        <li>
            <?php
            echo '<p><div class="Category">';
            echo $this->Form->label('Category', 'CategoryID'), ' ';
            echo $this->Form->categoryDropDown();
            echo '</div></p>';
            ?>
        </li>
        <li>
            <?php
            echo '<p>'.
                $this->Form->checkBox('RedirectLink', 'Leave a redirect link.').
                '</p>';
            ?>
        </li>
    </ul>
<?php
echo $this->Form->close('Move');
