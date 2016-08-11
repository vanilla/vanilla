<div class="Configuration">
    <div class="ConfigurationForm">
        <ul>
            <li class="form-group row">
                <div class="label-wrap-wide">
                    <?php echo $this->Form->label('API Status'); ?>
                </div>
                <div class="input-wrap-right">
                    <div class="Async js-popin" rel="statistics/verify"></div>
                </div>
            </li>
            <li class="form-group row">
                <div class="label-wrap">
                    <?php echo $this->Form->label('Application ID', 'InstallationID'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->textBox('InstallationID'); ?>
                </div>
            </li>
            <li class="form-group row">
                <div class="label-wrap">
                    <?php echo $this->Form->label('Application Secret', 'InstallationSecret'); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $this->Form->textBox('InstallationSecret'); ?>
                </div>
            </li>
        </ul>
        <div class="form-footer js-modal-footer">
            <?php echo $this->Form->button('Save', array('class' => 'Button')); ?>
        </div>
    </div>
</div>
<?php echo $this->Form->close(); ?>
