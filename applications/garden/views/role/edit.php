<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php
   if (is_object($this->Role))
      echo Gdn::Translate('Edit Role');
   else
      echo Gdn::Translate('Add Role');
?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Role Name', 'Name');
         echo $this->Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Description', 'Description');
         echo $this->Form->TextBox('Description', array('MultiLine' => TRUE));
      ?>
   </li>
   <?php
   if ($this->PermissionData->NumRows() > 0 || $this->HasJunctionPermissionData) {
      if ($this->Role && $this->Role->CanSession != '1') {
         ?>
         <li><p class="Warning"><?php echo Gdn::Translate('Heads Up! This is a special role that does not allow active sessions. For this reason, the permission options have been limited to "view" permissions.'); ?></p></li>
         <?php
      }
      ?>
      <li>
         <?php
            echo '<strong>'.Gdn::Translate('Check all permissions that apply to this role:').'</strong>';
            echo $this->Form->CheckBoxGrid("PermissionID", $this->PermissionData, $this->RolePermissionData, array('TextField' => 'Name', 'ValueField' => 'PermissionID'));
            
            foreach ($this->JunctionTableData as $Table => $Data) {
               $JunctionRowData = $Data['Rows'];
               $PermissionData = $Data['Permissions'];
               if (is_object($PermissionData)) {
                  foreach ($JunctionRowData->Result() as $JunctionRow) {
                     $i = 1;
                     $Group = array();
                     $Rows = array();
                     $Cols = array();
                     $CheckBox = '';
                     foreach($PermissionData->Result() as $Permission) {
                        $Value = $JunctionRow->JunctionID.'-'.$Permission->PermissionID;
                        $Attributes = array(
                           'value' => $Value,
                           'id' => 'JunctionPermissionID'.$i
                        );
                        
                        if (is_array($this->RoleJunctionPermissionData) && in_array($Value, $this->RoleJunctionPermissionData))
                           $Attributes['checked'] = 'checked';
   
                        $CheckBox = $this->Form->CheckBox(
                           'JunctionPermissionID[]',
                           '',
                           $Attributes
                        );
            
                        // Organize the checkbox into an array for this group
                        $aPermissionName = explode('.', $Permission->Name);
                        $ColName = array_pop($aPermissionName);
                        array_shift($aPermissionName);
                        $RowName = implode('.', $aPermissionName);
                        ++$i;
                        
                        if (array_key_exists($ColName, $Group) === FALSE || is_array($Group[$ColName]) === FALSE) {
                           $Group[$ColName] = array();
                           if (!in_array($ColName, $Cols))
                              $Cols[] = $ColName;
                        }
         
                        if (!in_array($RowName, $Rows))
                           $Rows[] = $RowName;
         
                        $Group[$ColName][$RowName] = $CheckBox;
                     }
                     echo $this->Form->GetCheckBoxGridGroup(
                        $JunctionRow->Name,
                        $Group,
                        $Rows,
                        $Cols
                     );
                  }
               }
            }
         ?>
      </li>
   <?php
   }
   ?>
</ul>
<?php echo $this->Form->Close('Save'); ?>