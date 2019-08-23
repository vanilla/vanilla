<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open();
?>
    <div class="Title">
        <h1>
            <?php echo img('applications/dashboard/design/images/vanilla_logo.png', ['alt' => 'Vanilla']); ?>
            <p><?php echo "Db Update" ?></p>
        </h1>
    </div>
    <div class="Form">
        <?php echo $this->Form->errors(); ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label('Update Token');
                echo '<div class="Description">Enter your <a href="https://docs.vanillaforums.com/developer/installation/self-hosting/#update-token">update token</a> below.</div>';
                echo $this->Form->textBox('updateToken');
                ?>
            </li>
        </ul>
        <div class="Button">
            <?php echo $this->Form->button('Update'); ?>
        </div>
    </div>
<?php
echo $this->Form->close();
