<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo Gdn::Session()->UserID == $this->User->UserID ? T('My Preferences') : T('Edit Preferences'); ?></h2>
<div class="Preferences">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
$this->FireEvent("BeforePreferencesRender");
foreach ($this->PreferenceGroups as $PreferenceGroup => $Preferences) {
   echo Wrap(T($PreferenceGroup), 'h3');
   ?>
   <table class="PreferenceGroup">
      <thead>
         <tr>
         <?php
         $CountTypes = 0;
         foreach ($this->PreferenceTypes[$PreferenceGroup] as $PreferenceType) {
            echo Wrap(T($PreferenceType), 'td', array('class' => 'PrefCheckBox'));
            $CountTypes++;
         }
         echo Wrap('&nbsp;', 'td');
         ?>
         </tr>
      </thead>
      <tbody>
         <?php
            foreach ($Preferences as $Names) {
               echo '<tr>';
               $LastName = '';
               $i = 0;
               foreach ($Names as $Name) {
                  echo Wrap($this->Form->CheckBox($Name, '', array('value' => '1')), 'td', array('class' => 'PrefCheckBox'));
                  $LastName = $Name;
                  $i++;
               }
               for(;$i < $CountTypes; $i++) {
                  echo Wrap('&#160;', 'td', array('class' => 'PrefCheckBox'));
               }

               $Desc = $this->Preferences[$PreferenceGroup][$LastName];
               if (is_array($Desc))
                  $Desc = $Desc[0];
               echo Wrap($Desc, 'td', array('class' => 'Description'));
               echo '</tr>';
            }
         ?>
      </tbody>
   </table>
<?php
}  
echo $this->Form->Close(T('Save Preferences'));
$this->FireEvent("AfterPreferencesRender");
?>
</div>