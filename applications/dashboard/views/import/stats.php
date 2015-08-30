<?php if (!defined('APPLICATION')) exit(); ?>
<table class="AltColumns">
    <?php
    $Header = array();
    $ImportPaths = $this->data('ImportPaths');
    if (is_array($ImportPaths))
        $Filename = val($this->data('ImportPath'), $ImportPaths);
    else
        $Filename = '';
    //$Filename = val('OriginalFilename', $this->Data);
    if ($Filename)
        $Header[T('Source')] = $Filename;

    $Header = array_merge($Header, (array)GetValue('Header', $this->Data, array()));
    $Stats = (array)GetValue('Stats', $this->Data, array());
    $Info = array_merge($Header, $Stats);
    foreach ($Info as $Name => $Value) {
        switch ($Name) {
            case 'Orphaned Comments':
            case 'Orphaned Discussions':
                $Value .= ' '.anchor(
                        t('Click here to fix.'),
                        Gdn::request()->Url('dba/fixinsertuserid')
                    );
                break;
            default:
                $Name = htmlspecialchars($Name);
                $Value = htmlspecialchars($Value);

                if (substr_compare('Time', $Name, 0, 4, true) == 0) {
                    $Value = Gdn_Timer::FormatElapsed($Value);
                }
        }

        echo "<tr><th>$Name</th><td class=\"Alt\">$Value</td></tr>\n";
    }

    if ($this->data('GenerateSQL')) {
        echo "<tr><th>".t('Special')."</th><td class=\"Alt\">".t('Generate import SQL only')."</td></tr>\n";
    }
    ?>
</table>
