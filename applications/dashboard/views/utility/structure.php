<?php if (!defined('APPLICATION')) exit();
echo wrap(t($this->data('Title')), 'h1');
echo '<div class="Wrap">';
echo $this->Form->errors();

if ($this->Data['Status'])
    echo '<div class="Info">', t($this->Data['Status']), '</div>';

if (array_key_exists('CapturedSql', $this->Data)) {
    $CapturedSql = (array)$this->Data['CapturedSql'];
    $Url = 'dashboard/utility/structure/'.$this->Data['ApplicationName'].'/0/'.(int)$this->Data['Drop'].'/'.(int)$this->Data['Explicit'];

    if (count($CapturedSql) > 0) {
        ?>
        <div class="Info"><?php echo t('The following structure changes are required for your database.'); ?></div>
        <?php
        echo '<pre class="Sql">';
        $First = TRUE;
        foreach ($this->Data['CapturedSql'] as $Sql) {
            if ($First)
                $First = FALSE;
            else
                echo "\n\n";

            $Sql = trim(trim($Sql), ';').';';
            echo htmlspecialchars($Sql);
        }

        echo '</pre>';
    } else if ($this->Data['CaptureOnly']) {
        ?>
        <div
            class="Info"><?php echo t('There are no database structure changes required. There may, however, be data changes.'); ?></div>
    <?php
    }
    echo '<div class="Buttons">',
    anchor(t('Run structure & data scripts'), $Url, 'Button', array('style' => 'font-size: 16px;')),
    ' ',
    anchor(t('Rescan'), 'dashboard/utility/structure/all', 'Button', array('style' => 'font-size: 16px;')),
    '</div>';
}
echo '</div>';
