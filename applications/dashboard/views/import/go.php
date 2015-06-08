<?php if (!defined('APPLICATION')) exit();
$this->addSideMenu();
?>
    <style> .Complete {
            text-decoration: line-through;
        }</style>
    <h1><?php echo t('Import'); ?></h1>
<?php
echo $this->Form->errors();

$CurrentStep = val('CurrentStep', $this->Data, 0);
$Steps = val('Steps', $this->Data, array());
$Complete = FALSE;

if ($CurrentStep > 0 && !array_key_exists($CurrentStep, $Steps)) {
    $Complete = TRUE;
    echo '<div class="Info">',
    sprintf(t('Garden.Import.Complete.Description', 'You have successfully completed an import.
   Remember to visit <a href="%s">Dashboard &gt; Roles & Permissions</a> to set up your role permissions.
   Click <b>Finished</b> when you are ready.'), url('/dashboard/role')),
    '</div>';

    echo Gdn::slice('/dashboard/role/defaultroleswarning');
}
?>
    <div class="Info">
        <ol>
            <?php
            foreach ($Steps as $Number => $Name) {
                echo '<li ', ($CurrentStep > $Number ? 'class="Complete"' : ''), '>',
                t('Garden.Import.Steps.'.$Name, _SpacifyCamelCase($Name));

                if ($Number == $CurrentStep) {
                    $Message = val('CurrentStepMessage', $this->Data);
                    echo '<div><span class="Progress">&#160;</span>';
                    if ($Message)
                        echo ' ', wrap($Message, 'span');
                    echo '</div>';
                    $ErrorType = $this->Data['ErrorType'];
                    if ($ErrorType) {
                        $ViewLocation = $this->fetchViewLocation(strtolower($ErrorType), 'import', 'dashboard');
                        if (file_exists($ViewLocation))
                            include($ViewLocation);
                    }
                }

                echo '</li>';
            }

            /**
             * Add spaces to a camel case word by putting a space before every capital letter.
             */
            function _SpacifyCamelCase($Str) {
                $Result = '';
                for ($i = 0; $i < strlen($Str); $i++) {
                    $c = substr($Str, $i, 1);
                    if ($Result && strtoupper($c) === $c && strtoupper($Str[$i - 1]) != $Str[$i - 1])
                        $Result .= ' ';
                    $Result .= $c;
                }
                return $Result;
            }

            ?>
        </ol>
    </div>
<?php

if ($Complete) {
    include($this->fetchViewLocation('stats', 'import', 'dashboard'));
    echo anchor(t('Finished'), 'dashboard/import/restart', 'Button');
} else {
    echo '<noscript><div>',
    anchor(t('Continue'), strtolower($this->Application).'/import/go', 'Button'),
    '</div></noscript>';
}
