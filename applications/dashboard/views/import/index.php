<?php if (!defined('APPLICATION')) exit();
$this->addSideMenu();
?>
<div class="Help Aside">
    <?php
    echo '<h2>', t('Need More Help?'), '</h2>';
    echo '<ul>';
    echo '<li>', anchor(t('Importing to Vanilla'), 'http://docs.vanillaforums.com/developers/importing/'), '</li>';
    echo '<li>', anchor(t('The Vanilla 2 Exporter'), 'http://vanillaforums.org/addon/porter-core'), '</li>';
    echo '</ul>';
    ?>
</div>
<h1><?php echo t('Import'); ?></h1>
<?php
echo '<div class="Info">'
    .sprintf(t('Garden.Import.Description', 'Use this page to import data from another forum that was exported using Vanilla Porter. For more information <a href="%s">see our importing documentation</a>.'), 'http://docs.vanillaforums.com/developers/importing/')
    .Wrap(t('You can place files in your /uploads folder.', 'If your file is too
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
<div class="Messages Errors">
    <ul>
        <li><?php echo t('Garden.Import.Overwrite.Description', 'Warning: All data in this forum will be overwritten.'); ?></li>
    </ul>
</div>
<?php
echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
<ul>
    <li>
        <?php
        echo wrap(t('Select the import source'), 'label');
        foreach ($this->data('ImportPaths') as $Path => $Text) {
            echo '<div>',
            $this->Form->Radio('PathSelect', $Text, array('value' => $Path)),
            '</div>';
        }
        ?>
        <?php
        $OriginalFilename = val('OriginalFilename', $this->Data);

        echo '<div>';
        if (count($this->data('ImportPaths')) > 0)
            echo $this->Form->Radio('PathSelect', $this->Form->Input('ImportFile', 'file'), array('value' => 'NEW'));
        else
            echo $this->Form->Input('ImportFile', 'file');
        echo '</div>';

        if ($OriginalFilename) {
            echo ' ', t('Current File:').' '.htmlspecialchars($OriginalFilename);
        }
        ?>
    </li>
    <li>
        <?php
        //echo $this->Form->Radio('Overwrite', t('Garden.Import.Overwrite', 'Overwrite this forum.'), array('value' => 'overwrite', 'default' => 'overwrite'));
        echo wrap(t('Garden.Import.InputInstructions', 'Enter the email of the admin user from the data being imported.  Your current password will become this user\'s password.'), 'div');

        echo $this->Form->label('Email', 'Email'),
        $this->Form->textBox('Email');
        ?>
    </li><?php /*
	<li>
		<?php
		echo $this->Form->Radio('Overwrite', t('Garden.Import.Merge', 'Merge with this forum.'), array('value' => 'merge'));
		echo '<div class="Info">',
		T('Garden.Import.Merge.Description', 'This will merge all of the user and discussion data from the import into this forum.
<b>Warning: If you merge the same data twice you will get duplicate discussions.</b>'),
		'</div>';

		?>
	</li> */ ?>
</ul>
<h3><?php echo t('Advanced Options'); ?></h3>
<ul>
    <li>
        <?php
        echo $this->Form->CheckBox('GenerateSQL', 'Generate import SQL only');
        ?>
    </li>
</ul>
<div class="Buttons">
    <?php echo $this->Form->close('Start'); ?>
</div>
