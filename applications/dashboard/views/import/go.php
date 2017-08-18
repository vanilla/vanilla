<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo t('Import'); ?></h1>

    <style> .Complete {
            text-decoration: line-through;
        }</style>
<?php
echo $this->Form->errors();

$CurrentStep = val('CurrentStep', $this->Data, 0);
$Steps = val('Steps', $this->Data, []);
$Complete = FALSE;

if ($CurrentStep > 0 && !array_key_exists($CurrentStep, $Steps)) {
    $Complete = TRUE;
    echo '<div class="alert alert-success padded">',
    sprintf(t('Garden.Import.Complete.Description', 'You have successfully completed an import.
   Remember to visit <a href="%s">Dashboard &gt; Roles & Permissions</a> to set up your role permissions.
   Click <b>Finished</b> when you are ready.'), url('/dashboard/role')),
    '</div>';
}
?>
    <div class="js-import-steps padded">
        <ol>
            <?php
            foreach ($Steps as $Number => $Name) {
                echo '<li ', ($CurrentStep > $Number ? 'class="Complete"' : ''), '>',
                t('Garden.Import.Steps.'.$Name, _SpacifyCamelCase($Name));

                if ($Number == $CurrentStep) {
                    $Message = val('CurrentStepMessage', $this->Data);
                    echo '<div><span class="progress">&#160;</span>';
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
            function _SpacifyCamelCase($str) {
                $result = '';
                for ($i = 0; $i < strlen($str); $i++) {
                    $c = substr($str, $i, 1);
                    if ($result && strtoupper($c) === $c && strtoupper($str[$i - 1]) != $str[$i - 1])
                        $result .= ' ';
                    $result .= $c;
                }
                return $result;
            }

            ?>
        </ol>
    </div>
<?php
if ($Complete) {
    include($this->fetchViewLocation('stats', 'import', 'dashboard'));
    echo '<div class="form-footer">'.anchor(t('Finished'), 'dashboard/import/restart/'.urlencode(Gdn::session()->transientKey()), 'btn btn-primary').'</div>';
} else {
    echo '<noscript><div class="form-footer">',
    anchor(t('Continue'), strtolower($this->Application).'/import/go/'.urlencode(Gdn::session()->transientKey()), 'btn btn-primary'),
    '</div></noscript>';
} ?>
