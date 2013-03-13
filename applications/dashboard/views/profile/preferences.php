<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   table.PreferenceGroup {
      width: 500px;
   }
   thead td {
      vertical-align: bottom;
      text-align: center;
   }
   table.PreferenceGroup thead .TopHeading {
      border-bottom: none;
   }
   table.PreferenceGroup thead .BottomHeading {
      border-top: none;
   }
   td.PrefCheckBox {
      width: 50px;
		text-align: center;
   }
   table.PreferenceGroup tbody tr:hover td {
      background: #efefef;
   }
   .Info {
      width: 486px;
   }
</style>
<h2 class="H"><?php echo $this->Data('Title');  ?></h2>
<div class="Preferences">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
$this->FireEvent("BeforePreferencesRender");

foreach ($this->Data('PreferenceGroups') as $PreferenceGroup => $Preferences) {
   echo Wrap(T($PreferenceGroup == 'Notifications' ? 'General' : $PreferenceGroup), 'h3');
   ?>
   <table class="PreferenceGroup">
      <thead>
         <tr>
         <?php
         echo Wrap(T('Notification'), 'td', array('style' => 'text-align: left'));
         
         $CountTypes = 0;
         foreach ($this->Data("PreferenceTypes.{$PreferenceGroup}") as $PreferenceType) {
            echo Wrap(T($PreferenceType), 'td', array('class' => 'PrefCheckBox'));
            $PreferenceTypeOrder[$PreferenceType] = $CountTypes;
            $CountTypes++;
         }
         ?>
         </tr>
      </thead>
      <tbody>
         <?php
            foreach ($Preferences as $Names) {
               // Make sure there are preferences.
               $ConfigCount = 0;
               foreach ($Names as $Name) {
                  $CP = C('Preferences.'.$Name, '0');
                  if ($CP !== FALSE && $CP != 2)
                     $ConfigCount++;
               }
               if ($ConfigCount == 0)
                  continue;
               
               echo '<tr>';
               $Desc = GetValue($Name, $this->Data("PreferenceList.{$PreferenceGroup}"));
               if (is_array($Desc))
                  list($Desc, $Location) = $Desc;
               echo Wrap($Desc, 'td', array('class' => 'Description'));
               
               $LastName = '';
               $i = 0;
               foreach ($Names as $Name) {
                  $NameTypeExplode = explode(".", $Name);
                  $NameType = $NameTypeExplode[0];
                  $ConfigPref = C('Preferences.'.$Name, '0');
                  if ($ConfigPref === FALSE || $ConfigPref == 2) {
                     echo Wrap('&nbsp;', 'td', array('class' => 'PrefCheckBox'));
                  } else {
                  	if (count($Names) < $CountTypes) {
               			   $PreferenceTypeOrderCount = 0;
               			   foreach ($PreferenceTypeOrder as $PreferenceTypeName => $PreferenceTypeOrderValue) {
               			       if ($NameType == $PreferenceTypeName) {
               				   if ($PreferenceTypeOrderValue == $PreferenceTypeOrderCount) echo Wrap($this->Form->CheckBox($Name, '', array('value' => '1')), 'td', array('class' => 'PrefCheckBox'));
               			       } else echo Wrap('&nbsp;', 'td', array('class' => 'PrefCheckBox'));
               			       $PreferenceTypeOrderCount++;
               			   }
               		} else echo Wrap($this->Form->CheckBox($Name, '', array('value' => '1')), 'td', array('class' => 'PrefCheckBox'));
                  }
                  $LastName = $Name;
                  $i++;
               }
               
               echo '</tr>';
            }
         ?>
      </tbody>
   </table>
<?php
}
$this->FireEvent('CustomNotificationPreferneces');
echo $this->Form->Close('Save Preferences', '', array('class' => 'Button Primary'));
$this->FireEvent("AfterPreferencesRender");
?>
</div>