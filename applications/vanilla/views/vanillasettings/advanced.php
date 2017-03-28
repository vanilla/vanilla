<?php if (!defined('APPLICATION')) exit();
helpAsset(t('Need More Help?'), anchor(t("Video tutorial on advanced settings"), 'settings/tutorials/category-management-and-advanced-settings'));
?>
<h1><?php echo t('Advanced'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <?php echo $this->Form->toggle('Vanilla.AdminCheckboxes.Use', 'Enable checkboxes on discussions and comments.'); ?>
        </li>
        <li class="form-group">
            <?php
            $Options = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '0' => 'No limit'];
            $Fields = ['TextField' => 'Code', 'ValueField' => 'Code'];
            ?>
            <div class="label-wrap">
            <?php
            echo $this->Form->label('Maximum Category Display Depth', 'Vanilla.Categories.MaxDisplayDepth');
            echo wrap(
                t('CategoryMaxDisplayDepth.Notes', 'Nested categories deeper than this depth will be placed in a comma-delimited list.'),
                'div',
                ['class' => 'info']
            );
            ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown('Vanilla.Categories.MaxDisplayDepth', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group">
            <?php
            $Options = array('10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '40' => '40', '50' => '50', '100' => '100');
            $Fields = array('TextField' => 'Code', 'ValueField' => 'Code');
            ?>
            <div class="label-wrap">
            <?php echo $this->Form->label('Discussions per Page', 'Vanilla.Discussions.PerPage'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown('Vanilla.Discussions.PerPage', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Comments per Page', 'Vanilla.Comments.PerPage'); ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown('Vanilla.Comments.PerPage', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group">
            <?php
            $Options = array('0' => t('Authors may never edit'),
                '350' => sprintf(t('Authors may edit for %s'), t('5 minutes')),
                '900' => sprintf(t('Authors may edit for %s'), t('15 minutes')),
                '3600' => sprintf(t('Authors may edit for %s'), t('1 hour')),
                '14400' => sprintf(t('Authors may edit for %s'), t('4 hours')),
                '86400' => sprintf(t('Authors may edit for %s'), t('1 day')),
                '604800' => sprintf(t('Authors may edit for %s'), t('1 week')),
                '2592000' => sprintf(t('Authors may edit for %s'), t('1 month')),
                '-1' => t('Authors may always edit'));
            $Fields = array('TextField' => 'Text', 'ValueField' => 'Code'); ?>
            <div class="label-wrap">
            <?php
            echo $this->Form->label('Discussion & Comment Editing', 'Garden.EditContentTimeout');
            echo wrap(t('EditContentTimeout.Notes', 'If a user is in a role that has permission to edit content, those permissions will override this.'), 'div', array('class' => 'info'));
            ?>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->DropDown('Garden.EditContentTimeout', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Max Comment Length', 'Vanilla.Comment.MaxLength'); ?>
            <div class="info"><?php echo t("It is a good idea to keep the maximum number of characters allowed in a comment down to a reasonable size."); ?></div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Vanilla.Comment.MaxLength', array('class' => 'InputBox SmallInput')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Min Comment Length', 'Vanilla.Comment.MinLength'); ?>
            <div class="info"><?php echo t("You can specify a minimum comment length to discourage short comments."); ?></div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Vanilla.Comment.MinLength', array('class' => 'InputBox SmallInput')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
            <?php echo $this->Form->label('Trusted Domains', 'Garden.TrustedDomains'); ?>
                <div class="info">
                    <p>
                    <?php
                    echo t(
                        'You can specify a whitelist of trusted domains.',
                        'You can specify a whitelist of trusted domains (ex. yourdomain.com) that are safe for redirects and embedding.'
                    );
                    ?>
                    </p>
                    <p><strong><?php echo t('Note'); ?>:</strong> <?php echo t('Specify one domain per line. All subdomains are allowed if none is specified.'); ?></p>
                </div>
            </div>
            <div class="input-wrap">
            <?php echo $this->Form->textBox('Garden.TrustedDomains', ['MultiLine' => true]); ?>
            </div>
        </li>
    </ul>
<?php echo $this->Form->close('Save'); ?>
