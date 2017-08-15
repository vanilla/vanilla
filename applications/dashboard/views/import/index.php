<?php if (!defined('APPLICATION')) exit();

$links = '<ul>';
$links .= '<li>'.anchor(t('Importing to Vanilla'), 'http://docs.vanillaforums.com/developers/importing/').'</li>';
$links .= '<li>'.anchor(t('The Vanilla 2 Exporter'), 'https://open.vanillaforums.com/addon/porter-core').'</li>';
$links .= '</ul>';

helpAsset(t('Need More Help?'), $links);
echo heading(t('Import'));
echo '<div class="padded">'
    .sprintf(t('Garden.Import.Description', 'Use this page to import data from another forum that was exported using Vanilla Porter. For more information <a href="%s">see our importing documentation</a>.'), 'http://docs.vanillaforums.com/developers/importing/')
    .wrap(t('You can place files in your /uploads folder.', 'If your file is too
		large to upload directly to this page you can place it in your /uploads
		folder. Make sure the filename begins with the word <b>export</b> and ends
		with one of <b>.txt, .gz</b>.'), 'p')
    .'</div>';

if ($this->data('LoadSpeedWarning')) {
    echo '<div class="Warning">',
    t('Warning: Loading tables can be slow.', '<b>Warning</b>: Your server configuration does not support fast data loading.
If you are importing a very large file (ex. over 200,000 comments) you might want to consider changing your configuration. Click <a href="http://vanillaforums.com/porter">here</a> for more information.'),
    '</div>';
}
?>
<div class="alert alert-danger">
    <?php echo t('Garden.Import.Overwrite.Description', 'Warning: All data in this forum will be overwritten.'); ?>
</div>
<?php
echo $this->Form->open(['enctype' => 'multipart/form-data']);
echo $this->Form->errors();
?>
<ul>
    <li class="form-group">
        <?php echo $this->Form->labelWrap('Select the import source', 'PathSelect'); ?>
        <div class="input-wrap">
            <?php
            foreach ($this->data('ImportPaths') as $Path => $Text) {
                echo '<div>',
                $this->Form->radio('PathSelect', $Text, ['value' => $Path]),
                '</div>';
            }
            ?>
        </div>
    </li>
    <li class="form-group">
        <?php echo $this->Form->labelWrap('Upload a new import file', 'ImportFile'); ?>
        <div class="input-wrap">
        <?php
        if (count($this->data('ImportPaths')) > 0) {
            echo '<div class="hidden">';
            echo $this->Form->radio('PathSelect', '', ['value' => 'NEW', 'class' => 'js-new-path']);
            echo '</div>';
        }
        echo $this->Form->fileUpload('ImportFile');

        $OriginalFilename = val('OriginalFilename', $this->Data);
        if ($OriginalFilename) {
            echo ' ', t('Current File:').' '.htmlspecialchars($OriginalFilename);
        }
        ?>
        </div>
    </li>
    <li class="form-group">
        <div class="label-wrap">
            <?php echo $this->Form->label('Email', 'Email'); ?>
            <?php echo wrap(t('Garden.Import.InputInstructions', 'Enter the email of the admin user from the data being imported.  Your current password will become this user\'s password.'), 'div', ['class' => 'info']); ?>
        </div>
        <?php echo $this->Form->textBoxWrap('Email'); ?>
    </li>
</ul>
<section>
    <?php echo subheading(t('Advanced Options')); ?>
    <ul>
        <li class="form-group">
            <?php echo $this->Form->labelWrap('Generate import SQL only', 'GenerateSQL'); ?>
            <div class="input-wrap">
                <?php echo $this->Form->checkBox('GenerateSQL'); ?>
            </div>
        </li>
    </ul>
</section>
<?php echo $this->Form->close('Start'); ?>
