<?php if (!defined('APPLICATION')) exit(); ?>
<div class="table-wrap">
    <table class="table-data">
        <?php
        $Header = [];
        $ImportPaths = $this->data('ImportPaths');
        if (is_array($ImportPaths))
            $Filename = val($this->data('ImportPath'), $ImportPaths);
        else
            $Filename = '';
        //$Filename = val('OriginalFilename', $this->Data);
        if ($Filename)
            $Header[t('Source')] = $Filename;

        $Header = array_merge($Header, (array)getValue('Header', $this->Data, []));
        $Stats = (array)getValue('Stats', $this->Data, []);
        $Info = array_merge($Header, $Stats);
        foreach ($Info as $Name => $Value) {
            switch ($Name) {
                case 'Orphaned Comments':
                case 'Orphaned Discussions':
                    $Value .= ' '.anchor(
                            t('Click here to fix.'),
                            Gdn::request()->url('dba/fixinsertuserid')
                        );
                    break;
                default:
                    $Name = htmlspecialchars($Name);
                    $Value = htmlspecialchars($Value);

                    if (substr_compare('Time', $Name, 0, 4, true) == 0) {
                        $Value = Gdn_Timer::formatElapsed($Value);
                    }
            }

            echo "<tr><th>$Name</th><td class=\"Alt\">$Value</td></tr>\n";
        }

        if ($this->data('GenerateSQL')) {
            echo "<tr><th>".t('Special')."</th><td class=\"Alt\">".t('Generate import SQL only')."</td></tr>\n";
        }
        ?>
    </table>
</div>
