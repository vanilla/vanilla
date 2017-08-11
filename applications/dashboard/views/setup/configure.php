<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open();
?>
    <div class="Title">
        <h1>
            <?php echo img('applications/dashboard/design/images/vanilla_logo.png', ['alt' => 'Vanilla']); ?>
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
                echo $this->Form->input('Database.Password', 'password');
                ?>
            </li>
            <?php if ($this->data('NoHtaccess')): ?>
                <li>
                    <div class="Box"><?php echo t('You are missing an <b>.htaccess</b> file.'); ?></div>
                    <?php if (file_exists(PATH_ROOT.'/.htaccess.dist')): ?>
                        <?php echo $this->Form->radioList('HtaccessAction', [
                            'dist' => 'Use Vanilla\'s .htaccess.',
                            'skip' => 'I don\'t need an .htaccess file.'
                        ], ['Default' => 'dist']); ?>
                    <?php else: ?>
                    <?php echo $this->Form->checkBox('HtaccessAction', t('I don\'t need an .htaccess file.'),['value' => 'skip']); ?>
                    <?php endif; ?>
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
                echo $this->Form->input('Password', 'password');
                ?>
            </li>
            <li class="Last">
                <?php
                echo $this->Form->label('Confirm Password', 'PasswordMatch');
                echo $this->Form->input('PasswordMatch', 'password');
                ?>
            </li>
        </ul>
        <div class="Button">
            <?php echo $this->Form->button('Continue &rarr;'); ?>
        </div>
    </div>
<?php
echo $this->Form->close();
