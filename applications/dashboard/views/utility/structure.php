<?php if (!defined('APPLICATION')) exit();
echo wrap(t($this->data('Title')), 'h1');
echo '<div class="Wrap">';
echo $this->Form->open();
echo $this->Form->errors();

echo wrapIf(t($this->data('Status')), 'div', ['class' => 'Info']);

switch ($this->data('Step')) {
    case 'scan':
        // Display the scan of the structure.
        if (!empty($this->Data['CapturedSql'])) {
            $CapturedSql = (array)$this->Data['CapturedSql'];
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
        }

        echo '<div class="Buttons">',
            $this->Form->button('Run', ['value' => t('Run structure & data scripts')]),
            ' ',
            $this->Form->button('Scan', ['value' => t('Rescan')]),
            '</div>';
        break;
    case 'run':
        // Display the status message from running the structure.
        if (!empty($this->Data['Issues'])) {
            echo '<pre class="Sql">';

            $first = true;
            foreach ($this->Data['Issues'] as $row) {
                if ($first) {
                    $first = false;
                } else {
                    echo "\n\n";
                }

                echo htmlspecialchars("/* {$row['table']}: {$row['message']} */\n");
                echo htmlspecialchars($row['sql']);
            }

            echo '</pre>';
        }

        echo '<div class="Buttons">',
            $this->Form->button('Scan', ['value' => t('Rescan')]),
            '</div>';
        break;
    case 'start':
    default:
        // Just display some information on running the structure.
        echo '<div class="Info">'.t('Scan your database for changes.').'</div>';

        echo '<div class="Buttons">',
            $this->Form->button('Scan'),
            '</div>';
}
echo $this->Form->close();
echo '</div>';
