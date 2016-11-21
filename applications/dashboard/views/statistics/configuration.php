<div class="Configuration">
    <div class="ConfigurationForm">
        <ul>
            <li class="form-group">
                <div class="label-wrap-wide">
                    <?php echo $this->Form->label('API Status'); ?>
                </div>
                <div class="input-wrap-right">
                    <div class="Async js-popin" rel="statistics/verify"></div>
                </div>
            </li>
            <li class="form-group">
                <?php echo $this->Form->labelWrap('Application ID', 'InstallationID'); ?>
                <?php echo $this->Form->textBoxWrap('InstallationID'); ?>
            </li>
            <li class="form-group">
                <?php echo $this->Form->labelWrap('Application Secret', 'InstallationSecret'); ?>
                <?php echo $this->Form->textBoxWrap('InstallationSecret'); ?>
            </li>
        </ul>
        <div class="form-footer js-modal-footer">
            <?php echo $this->Form->button('Save'); ?>
        </div>
    </div>
</div>
