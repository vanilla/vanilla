<?php if (!defined('APPLICATION')) exit();
/* @var \UtilityController $this */
echo $this->Form->open();
?>
    <div class="Title">
        <h1>
            <?php echo img('applications/dashboard/design/images/vanilla_logo.png', ['alt' => 'Vanilla']); ?>
            <p>Database Update</p>
        </h1>
    </div>
<?php if ($this->data('Success', false)) { ?>
    <div class="Container"><div class="Notice">The update was successful.</div></div>
<?php } else { ?>
    <div class="Form">
        <?php echo $this->Form->errors(); ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label('Update Token');

                echo '<div class="Description">',
                    'Enter your <a href="https://docs.vanillaforums.com/developer/installation/self-hosting/#update-token">update token</a> below.';

                if ($this->data('_isAdmin')) {
                    echo ' Since you are are an administrator you can leave this field blank.';
                }
                echo '</div>';

                echo $this->Form->textBox('updateToken');
                ?>
            </li>
        </ul>
        <div class="Button">
            <?php echo $this->Form->button('Update'); ?>
        </div>
    </div>
    <?php
}
echo $this->Form->close();
