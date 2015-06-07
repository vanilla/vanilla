<?php if (!defined('APPLICATION')) exit();
$this->addSideMenu();
?>
    <h1><?php echo t('Import'); ?></h1>
<?php
$CurrentStep = val('CurrentStep', $this->Data, 0);
$Complete = FALSE;
if ($CurrentStep < 1) {
    // The import hasn't started yet.
    echo '<div class="Info">',
    t('Garden.Import.Info', 'You\'re almost ready to start the import.
Please review the information below and click <b>Start Import</b> to begin the import.'),
    '</div>';
} else {
    $Steps = val('Steps', $this->Data, array());
    if (count($Steps) > 0 && !array_key_exists($CurrentStep, $Steps)) {
        // The import is complete.
        $Complete = TRUE;
        // The import is complete.
        echo '<div class="Info">',
        t('Garden.Import.Complete.Description', 'You have successfully completed an import.
   Click <b>Finished</b> when you are ready.'),
        '</div>';
        echo Gdn::slice('/dashboard/role/defaultroleswarning');
    } else {
        // The import is in process.
        echo '<div class="Info">',
        t('Garden.Import.Continue.Description', 'It appears as though you are in the middle of an import.
   Please choose one of the following options.'),
        '</div>';
    }
}

include($this->fetchViewLocation('stats', 'import', 'dashboard'));

if ($CurrentStep < 1)
    echo anchor(t('Start Import'), 'dashboard/import/go', 'Button'),
    ' ',
    anchor(t('Restart'), 'dashboard/import/restart', 'Button');
elseif (!$Complete)
    echo anchor(t('Continue Import'), 'dashboard/import/go', 'Button'),
    ' ',
    anchor(t('Restart'), 'dashboard/import/restart', 'Button');
else
    echo anchor(t('Finished'), 'dashboard/import/restart', 'Button');

