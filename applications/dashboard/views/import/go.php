<?php if (!defined('APPLICATION')) exit();
$this->AddSideMenu();
?>
<style> .Complete { text-decoration: line-through; }</style>
<h2><?php echo T('Import'); ?></h2>
<?php
echo $this->Form->Errors();

$CurrentStep = GetValue('CurrentStep', $this->Data, 0);
$Steps = GetValue('Steps', $this->Data, array());
$Complete = FALSE;

if($CurrentStep > 0 && !array_key_exists($CurrentStep, $Steps)) {
   $Complete = TRUE;
   echo '<div class="Info">',
   T('Garden.Import.Complete.Description', 'You have successfully completed an import.
   Remember to go into <a href="/dashboard/role">Roles & Permissions</a> and setup your role permissions.
   Click <b>Finished</b> when you are ready.'),
   '</div>';
}
?>

<ol>
   <?php
   foreach($Steps as $Number => $Name) {
      echo '<li ', ($CurrentStep > $Number ? 'class="Complete"' : ''), '>',
      T('Garden.Import.Steps.'.$Name, _SpacifyCamelCase($Name));

      if($Number == $CurrentStep) {
         $Message = GetValue('CurrentStepMessage', $this->Data);
         echo '<div><span class="Progress">&nbsp;</span>';
         if($Message)
            echo ' ',Wrap($Message, 'span');
         echo '</div>';
         $ErrorType = $this->Data['ErrorType'];
         if($ErrorType) {
            $ViewLocation = $this->FetchViewLocation(strtolower($ErrorType), 'import', 'dashboard');
            if(file_exists($ViewLocation))
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
      for($i = 0; $i < strlen($Str); $i++) {
         $c = substr($Str, $i, 1);
         if($Result && strtoupper($c) === $c && strtoupper($Str[$i - 1]) != $Str[$i - 1])
            $Result .= ' ';
         $Result .= $c;
      }
      return $Result;
   }
   ?>
</ol>
<?php

if($Complete) {
   include($this->FetchViewLocation('stats', 'import', 'dashboard'));
   echo Anchor(T('Finished'), 'dashboard/import/restart', 'Button');
} else {
   echo '<noscript><div>',
   Anchor(T('Continue'), strtolower($this->Application).'/import/go', 'Button'),
   '</div></noscript>';
}