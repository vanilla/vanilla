<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open();
?>
    <div class="Title">
        <h1>
            <?php echo img('applications/dashboard/design/images/vanilla_logo.png', array('alt' => 'Vanilla')); ?>
            <p><?php echo sprintf(t('Version %s Installer'), APPLICATION_VERSION); ?></p>
        </h1>
    </div>
    <div class="Form">
        <?php echo $this->Form->errors(); ?>
        <ul>
            <li>
                <?php
                echo $this->Form->label('Database Host', 'Database.Host');
                echo $this->Form->textBox('Database.Host');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Database Name', 'Database.Name');
                echo $this->Form->textBox('Database.Name');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Database User', 'Database.User');
                echo $this->Form->textBox('Database.User');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Database Password', 'Database.Password');
                echo $this->Form->Input('Database.Password', 'password');
                ?>
            </li>
            <?php if ($this->data('NoHtaccess')): ?>
                <li>
                    <div
                        class="Box"><?php echo t('You are missing Vanilla\'s <b>.htaccess</b> file. Sometimes this file isn\'t copied if you are using ftp to upload your files because this file is hidden. Make sure you\'ve copied the <b>.htaccess</b> file before continuing.'); ?></div>
                    <?php
                    echo $this->Form->CheckBox('SkipHtaccess', t('Install Vanilla without a .htaccess file.'));
                    ?>
                </li>
            <?php endif; ?>
            <li class="Warning">
                <div>
                    <?php
                    echo t('Yes, the following information can be changed later.');
                    ?>
                </div>
            </li>
            <li>
                <?php
                echo $this->Form->label('Application Title', 'Garden.Title');
                echo $this->Form->textBox('Garden.Title');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Admin Email', 'Email');
                echo $this->Form->textBox('Email');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Admin Username', 'Name');
                echo $this->Form->textBox('Name');
                ?>
            </li>
            <li>
                <?php
                echo $this->Form->label('Admin Password', 'Password');
                echo $this->Form->Input('Password', 'password');
                ?>
            </li>
            <li class="Last">
                <?php
                echo $this->Form->label('Confirm Password', 'PasswordMatch');
                echo $this->Form->Input('PasswordMatch', 'password');
                ?>
            </li>
        </ul>
        <div class="Button">
            <?php echo $this->Form->button('Continue &rarr;'); ?>
        </div>
    </div>
<?php
echo $this->Form->close();
