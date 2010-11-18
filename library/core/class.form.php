<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Helps with the rendering of form controls that link directly to a data model.
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 */

/**
 * Helps with the rendering of form controls that link directly to a data model.
 *
 * @package Garden
 * @todo reviews damien's conversion to phpdoc
 * @todo change formatting of tables in documentation
 */
class Gdn_Form {

   /// =========================================================================
   /// 1. UI Components: Methods that return xhtml form elements.
   /// =========================================================================

   /**
    * Returns the xhtml for a button.
    *
    * @param string $ButtonCode The translation code for the text on the button.
    * @param array $Attributes An associative array of attributes for the button. Here is a list of
    * "special" attributes and their default values:
    * Attribute  Options                        Default
    * ------------------------------------------------------------------------
    * Type       The type of submit button      'submit'
    * Value      Ignored for $ButtonCode        $ButtonCode translated
    *
    * @return string
    */
   public function Button($ButtonCode, $Attributes = FALSE) {
      $Type = ArrayValueI('type', $Attributes);
      if ($Type === FALSE) $Type = 'submit';

      $CssClass = ArrayValueI('class', $Attributes);
      if ($CssClass === FALSE) $Attributes['class'] = 'Button';

      $Return = '<input type="' . $Type . '"';
      $Return .= $this->_IDAttribute($ButtonCode, $Attributes);
      $Return .= $this->_NameAttribute($ButtonCode, $Attributes);
      $Return .= ' value="' . T($ButtonCode) . '"';
      $Return .= $this->_AttributesToString($Attributes);
      $Return .= " />\n";
      return $Return;
   }

   /**
    * Returns the xhtml for a standard calendar input control.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in $this->_DataArray.
    * @param array $Attributes An associative array of attributes for the input. ie. onclick, class, etc
    * @return string
    * @todo Create calendar helper
    */
   public function Calendar($FieldName, $Attributes = FALSE) {
      // TODO: CREATE A CALENDAR HELPER CLASS AND LOAD/REFERENCE IT HERE.
      // THE CLASS SHOULD BE DECLARED WITH:
      //  if (!class_exists('Calendar') {
      // AT THE BEGINNING SO OTHERS CAN OVERRIDE THE DEFAULT CALENDAR WITH ONE
      // OF THEIR OWN.
      $Class = ArrayValueI(
         'class', $Attributes, FALSE);
      if ($Class === FALSE) $Attributes['class'] = 'DateBox';

      // IN THE MEANTIME...
      return $this->Input($FieldName, 'text', $Attributes);
   }

   /**
    * Returns the xhtml for a standard date input control.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in $this->_DataArray.
    * @param array $Attributes An associative array of attributes for the input. ie. onclick, class,
    * etc. A special attribute for this field is YearRange, specified in
    * yyyy-yyyy format. The default value for YearRange is 1900-2008 (aka
    * current year).
    *
    * @return string
    */
   public function Date($FieldName, $Attributes = FALSE) {
      $YearRange = ArrayValueI('yearrange', $Attributes, FALSE);
      $StartYear = 0;
      $EndYear = 0;
      if ($YearRange !== FALSE) {
         if (preg_match("/^[\d]{4}-{1}[\d]{4}$/i", $YearRange) == 1) {
            $StartYear = substr($YearRange, 0, 4);
            $EndYear = substr($YearRange, 5);
         }
      }
      if ($YearRange === FALSE || $StartYear > $EndYear) {
         $StartYear = 1900;
         $EndYear = date('Y');
      }

      $Months = array_map('T',
         explode(',', 'Month,Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec'));

      $Days = array();
      $Days[] = T('Day');
      for($i = 1; $i < 32; ++$i) {
         $Days[] = $i;
      }

      $Years = array();
      $Years[0] = T('Year');
      for($i = $EndYear; $i >= $StartYear; --$i) {
         $Years[$i] = $i;
      }

      $CssClass = ArrayValueI('class', $Attributes, '');
      $Attributes['class'] = trim($CssClass . ' Month');
      $Return = $this->DropDown($FieldName . '_Month', $Months, $Attributes);
      $Attributes['class'] = trim($CssClass . ' Day');
      $Return .= $this->DropDown($FieldName . '_Day', $Days, $Attributes);
      $Attributes['class'] = trim($CssClass . ' Year');

      return $Return . $this->DropDown($FieldName . '_Year', $Years, $Attributes) . '<input type="hidden" name="DateFields[]" value="' .
          $FieldName . '" />';
   }

   /**
    * Returns the xhtml for a standard checkbox input tag.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in $this->_DataArray.
    *
    * @param string $Label A label to place next to the checkbox.
    * @param array $Attributes An associative array of attributes for the input. ie. onclick, class, etc
    * @return string
    */
   public function CheckBox($FieldName, $Label = '', $Attributes = FALSE) {
      $Value = ArrayValueI('value', $Attributes, 'TRUE');
      $Attributes['value'] = $Value;
      // 2009-04-02 - mosullivan - cannot consider all checkbox values to be boolean
      // if (ForceBool($this->GetValue($FieldName)) == ForceBool($Value)) $Attributes['checked'] = 'checked';
      if ($this->GetValue($FieldName) == $Value)
         $Attributes['checked'] = 'checked';

      $Input = $this->Input($FieldName, 'checkbox', $Attributes);
      if ($Label != '') $Input = '<label for="' . ArrayValueI('id', $Attributes,
         $this->EscapeID($FieldName, FALSE)) . '" class="CheckBoxLabel">' . $Input . ' ' .
          T($Label) . '</label>';

      return $Input;
   }

   /**
    * Returns the xhtml for a list of checkboxes.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in a user junction table.
    * ie. LUM_UserRole.RoleID
    *
    * @param mixed $DataSet The data to fill the options in the select list. Either an associative
    * array or a database dataset. ie. RoleID, Name from LUM_Role.
    *
    * @param mixed $ValueDataSet The data that should be checked in $DataSet. Either an associative array
    * or a database dataset. ie. RoleID from LUM_UserRole for a single user.
    *
    * @param array $Attributes  An associative array of attributes for the select. Here is a list of
    * "special" attributes and their default values:
    * Attribute   Options                        Default
    * ------------------------------------------------------------------------
    * ValueField  The name of the field in       'value'
    *             $DataSet that contains the
    *             option values.
    * TextField   The name of the field in       'text'
    *             $DataSet that contains the
    *             option text.
    *
    * @return string
    */
   public function CheckBoxList($FieldName, $DataSet, $ValueDataSet, $Attributes) {
      $Return = '';
      // If the form hasn't been posted back, use the provided $ValueDataSet
      if ($this->IsPostBack() === FALSE) {
         if ($ValueDataSet === NULL) {
            $CheckedValues = $this->GetValue($FieldName);
         } else {
            $CheckedValues = $ValueDataSet;
            if (is_object($ValueDataSet))
               $CheckedValues = ConsolidateArrayValuesByKey($ValueDataSet->ResultArray(), $FieldName);
         }
      } else {
         $CheckedValues = $this->GetFormValue($FieldName, array());
      }
      $i = 1;
      if (is_object($DataSet)) {
         $ValueField = ArrayValueI('ValueField', $Attributes, 'value');
         $TextField = ArrayValueI('TextField', $Attributes, 'text');
         foreach($DataSet->Result() as $Data) {
            $Instance = $Attributes;
            $Instance = RemoveKeyFromArray($Instance,
               array('TextField', 'ValueField'));
            $Instance['value'] = $Data->$ValueField;
            $Instance['id'] = $FieldName . $i;
            if (is_array($CheckedValues) && in_array($Data->$ValueField,
               $CheckedValues)) {
               $Instance['checked'] = 'checked';
            }

            $Return .= '<li>' . $this->CheckBox($FieldName . '[]',
               $Data->$TextField, $Instance) . "</li>\n";
            ++$i;
         }
      } elseif (is_array($DataSet)) {
         foreach($DataSet as $Text => $ID) {
            $Instance = $Attributes;
            $Instance = RemoveKeyFromArray($Instance,
               array('TextField', 'ValueField'));
            $Instance['id'] = $FieldName . $i;
            if (is_numeric($Text)) $Text = $ID;

            $Instance['value'] = $ID;
            if (is_array($CheckedValues) && in_array($ID, $CheckedValues)) {
               $Instance['checked'] = 'checked';
            }

            $Return .= '<li>' . $this->CheckBox($FieldName . '[]', $Text,
               $Instance) . "</li>\n";
            ++$i;
         }
      }
      return '<ul class="'.ConcatSep(' ', 'CheckBoxList', GetValue('listclass', $Attributes)).'">' . $Return . '</ul>';
   }

   /**
    * Returns the xhtml for a list of checkboxes; sorted into groups related to
    * the TextField value of the dataset.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in a user junction table.
    * ie. LUM_UserRole.RoleID
    *
    * @param mixed $DataSet The data to fill the options in the select list. Either an associative
    * array or a database dataset. ie. RoleID, Name from LUM_Role.
    *
    * @param mixed $ValueDataSet The data that should be checked in $DataSet. Either an associative array
    * or a database dataset. ie. RoleID from LUM_UserRole for a single user.
    *
    * @param array $Attributes An associative array of attributes for the select. Here is a list of
    * "special" attributes and their default values:
    *
    * Attribute   Options                        Default
    * ------------------------------------------------------------------------
    * ValueField  The name of the field in       'value'
    *             $DataSet that contains the
    *             option values.
    * TextField   The name of the field in       'text'
    *             $DataSet that contains the
    *             option text.
    *
    * @return string
    */
   public function CheckBoxGrid($FieldName, $DataSet, $ValueDataSet, $Attributes) {
      $Return = '';
      $CheckedValues = $ValueDataSet;
      if (is_object($ValueDataSet)) $CheckedValues = ConsolidateArrayValuesByKey(
         $ValueDataSet->ResultArray(), $FieldName);

      $i = 1;
      if (is_object($DataSet)) {
         $ValueField = ArrayValueI('ValueField', $Attributes, 'value');
         $TextField = ArrayValueI('TextField', $Attributes, 'text');
         $LastGroup = '';
         $Group = array();
         $Rows = array();
         $Cols = array();
         $CheckBox = '';
         foreach($DataSet->Result() as $Data) {
            // Define the checkbox
            $Instance = $Attributes;
            $Instance = RemoveKeyFromArray($Instance, array('TextField', 'ValueField'));
            $Instance['value'] = $Data->$ValueField;
            $Instance['id'] = $FieldName . $i;
            if (is_array($CheckedValues) && in_array($Data->$ValueField,
               $CheckedValues)) {
               $Instance['checked'] = 'checked';
            }
            $CheckBox = $this->CheckBox($FieldName . '[]', '', $Instance);

            // Organize the checkbox into an array for this group
            $CurrentTextField = $Data->$TextField;
            $aCurrentTextField = explode('.', $CurrentTextField);
            $aCurrentTextFieldCount = count($aCurrentTextField);
            $GroupName = array_shift($aCurrentTextField);
            $ColName = array_pop($aCurrentTextField);
            if ($aCurrentTextFieldCount >= 3) {
               $RowName = implode('.', $aCurrentTextField);
               if ($GroupName != $LastGroup && $LastGroup != '') {
                  // Render the last group
                  $Return .= $this->GetCheckBoxGridGroup(
                     $LastGroup,
                     $Group,
                     $Rows,
                     $Cols);

                  // Clean out the $Group array & Rowcount
                  $Group = array();
                  $Rows = array();
                  $Cols = array();
               }

               if (array_key_exists($ColName, $Group) === FALSE || is_array($Group[$ColName]) === FALSE) {
                  $Group[$ColName] = array();
                  if (!in_array($ColName, $Cols))
                     $Cols[] = $ColName;
                     
               }

               if (!in_array($RowName, $Rows))
                  $Rows[] = $RowName;

               $Group[$ColName][$RowName] = $CheckBox;
               $LastGroup = $GroupName;
            }
            ++$i;
         }
      }
      /*elseif (is_array($DataSet)) {
         foreach ($DataSet as $Text => $ID) {
            $Instance = $Attributes;
            $Instance = RemoveKeyFromArray($Instance, array('TextField', 'ValueField'));
            $Instance['id'] = $FieldName.$i;
            if (is_numeric($Text))
               $Text = $ID;

            $Instance['value'] = $ID;
            if (in_array($ID, $CheckedValues))
               $Instance['checked'] = 'checked';

            $Return .= $this->CheckBox($FieldName.'[]', $Text, $Instance)."\n";
            $i++;
         }
      }
      */
      return $Return . $this->GetCheckBoxGridGroup($LastGroup, $Group, $Rows, $Cols);
   }
   
   public function CheckBoxGridGroups($Data, $FieldName) {
      $Result = '';
      foreach($Data as $GroupName => $GroupData) {
         $Result .= $this->CheckBoxGridGroup($GroupName, $GroupData, $FieldName) . "\n";
      }
      return $Result;
   }
   
   public function CheckBoxGridGroup($GroupName, $Data, $FieldName) {
      // Get the column and row info.
      $Columns = $Data['_Columns'];
      ksort($Columns);
      $Rows = $Data['_Rows'];
      ksort($Rows);
      unset($Data['_Columns'], $Data['_Rows']);
      
      if(array_key_exists('_Info', $Data)) {
         $GroupName = $Data['_Info']['Name'];
         unset($Data['_Info']);
      }
      
      $Result = '<table class="CheckBoxGrid">';
      // Append the header.
      $Result .= '<thead><tr><th>'.T($GroupName).'</th>';
      $Alt = TRUE;
      foreach($Columns as $ColumnName => $X) {
         $Result .=
            '<td'.($Alt ? ' class="Alt"' : '').'>'
            . T($ColumnName)
            . '</td>';
            
         $Alt = !$Alt;
      }
      $Result . '</tr></thead>';
      
      // Append the rows.
      $Result .= '<tbody>';
      foreach($Rows as $RowName => $X) {
         $Result .= '<tr><th>';
         
         // If the row name is still seperated by dots then put those in spans.
         $RowNames = explode('.', $RowName);
         for($i = 0; $i < count($RowNames) - 1; ++$i) {
            $Result .= '<span class="Parent">'.T($RowNames[$i]).'</span>';
         }
         $Result .= T($RowNames[count($RowNames) - 1]).'</th>';
         // Append the columns within the rows.
         $Alt = TRUE;
         foreach($Columns as $ColumnName => $Y) {
            $Result .= '<td'.($Alt ? ' class="Alt"' : '').'>';
            // Check to see if there is a row corresponding to this area.
            if(array_key_exists($RowName.'.'.$ColumnName, $Data)) {
               $CheckBox = $Data[$RowName.'.'.$ColumnName];
               $Attributes = array('value' => $CheckBox['PostValue']);
               if($CheckBox['Value'])
                  $Attributes['checked'] = 'checked';
                  
               $Result .= $this->CheckBox($FieldName.'[]', '', $Attributes);
            } else {
               $Result .= ' ';
            }        
            $Result .= '</td>';
               
            $Alt = !$Alt;
         }
         $Result .= '</tr>';
      }
      $Result .= '</tbody></table>';
      return $Result;
   }

   /**
    * Returns a checkbox table.
    *
    * @param string $GroupName The name of the checkbox table (the text that appears in the top-left
    * cell of the table). This value will be passed through the T()
    * function before render.
    *
    * @param array $Group An array of $PermissionName => $CheckBoxXhtml to be rendered within the
    * grid. This represents the final (third) part of the permission name
    * string, as in the "Edit" part of "Garden.Roles.Edit".
    * ie. 'Edit' => '<input type="checkbox" id="PermissionID"
    * name="Role/PermissionID[]" value="20" />';
    *
    * @param array $Rows An array of rows to appear in the grid. This represents the middle part
    * of the permission name, as in the "Roles" part of "Garden.Roles.Edit".
    *
    * @param array $Cols An array of columns to appear in the grid for each row. This (again)
    * represents the final part of the permission name, as in the "Edit" part
    * of "Garden.Roles.Edit".
    * ie. Row1 = array('Add', 'Edit', 'Delete');
    */
   public function GetCheckBoxGridGroup($GroupName, $Group, $Rows, $Cols) {
      $Return = '';
      $Headings = '';
      $Cells = '';
      $RowCount = count($Rows);
      $ColCount = count($Cols);
      for($j = 0; $j < $RowCount; ++$j) {
         $Alt = 1;
         for($i = 0; $i < $ColCount; ++$i) {
            $Alt = $Alt == 0 ? 1 : 0;
            $ColName = $Cols[$i];
            $RowName = $Rows[$j];

            if ($j == 0) $Headings .= '<td' . ($Alt == 0 ? ' class="Alt"' : '') .
                '>' . T($ColName) . '</td>';

            if (array_key_exists($RowName, $Group[$ColName])) {
               $Cells .= '<td' . ($Alt == 0 ? ' class="Alt"' : '') .
                   '>' . $Group[$ColName][$RowName] .
                   '</td>';
            } else {
               $Cells .= '<td' . ($Alt == 0 ? ' class="Alt"' : '') .
                   '>&nbsp;</td>';
            }
         }
         if ($Headings != '') $Return .= "<thead><tr><th>" . T($GroupName) . "</th>" .
             $Headings . "</tr></thead>\r\n<tbody>";

         $aRowName = explode('.', $RowName);
         $RowNameCount = count($aRowName);
         if ($RowNameCount > 1) {
            $RowName = '';
            for($i = 0; $i < $RowNameCount; ++$i) {
               if ($i < $RowNameCount - 1) $RowName .= '<span class="Parent">' .
                   T($aRowName[$i]) . '</span>';
               else $RowName .= T($aRowName[$i]);
            }
         } else {
            $RowName = T($RowName);
         }
         $Return .= '<tr><th>' . $RowName . '</th>' . $Cells . "</tr>\r\n";
         $Headings = '';
         $Cells = '';
      }
      return $Return == '' ? '' : '<table class="CheckBoxGrid">'.$Return.'</tbody></table>';
   }

   /**
    * Returns the closing of the form tag with an optional submit button.
    *
    * @param string $ButtonCode
    * @param string $Xhtml
    * @return string
    */
   public function Close($ButtonCode = '', $Xhtml = '', $Attributes = FALSE) {
      $Return = "</div>\n</form>";
      if ($Xhtml != '') $Return = $Xhtml . $Return;

      if ($ButtonCode != '') $Return = $this->Button($ButtonCode, $Attributes) . $Return;

      return $Return;
   }

   /**
    * Returns the xhtml for a select list.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in $this->_DataArray. ie. RoleID
    *
    * @param mixed $DataSet The data to fill the options in the select list. Either an associative
    * array or a database dataset.
    *
    * @param array $Attributes An associative array of attributes for the select. Here is a list of
    * "special" attributes and their default values:
    *
    *   Attribute   Options                        Default
    *   ------------------------------------------------------------------------
    *   ValueField  The name of the field in       'value'
    *               $DataSet that contains the
    *               option values.
    *   TextField   The name of the field in       'text'
    *               $DataSet that contains the
    *               option text.
    *   Value       A string or array of strings.  $this->_DataArray->$FieldName
    *   IncludeNull Include a blank row?           FALSE
    *
    * @return string
    */
   public function DropDown($FieldName, $DataSet, $Attributes = FALSE) {
      $Return = '<select';
      $Return .= $this->_IDAttribute($FieldName, $Attributes);
      $Return .= $this->_NameAttribute($FieldName, $Attributes);
      $Return .= $this->_AttributesToString($Attributes);
      $Return .= ">\n";
      $Value = ArrayValueI('Value', $Attributes);

      if ($Value === FALSE) $Value = $this->GetValue($FieldName);

      if (!is_array($Value)) $Value = array($Value);

      $IncludeNull = ArrayValueI('IncludeNull', $Attributes);
      if ($IncludeNull === TRUE) $Return .= "<option value=\"\"></option>\n";

      if (is_object($DataSet)) {
         $FieldsExist = FALSE;
         $ValueField = ArrayValueI('ValueField', $Attributes, 'value');
         $TextField = ArrayValueI('TextField', $Attributes, 'text');
         $Data = $DataSet->FirstRow();
         if (is_object($Data) && property_exists($Data, $ValueField) && property_exists(
            $Data, $TextField)) {
            foreach($DataSet->Result() as $Data) {
               $Return .= '<option value="' . $Data->$ValueField .
                   '"';
               if (in_array($Data->$ValueField, $Value)) $Return .= ' selected="selected"';

               $Return .= '>' . $Data->$TextField . "</option>\n";
            }
         }
      } elseif (is_array($DataSet)) {
         foreach($DataSet as $ID => $Text) {
            $Return .= '<option value="' . $ID . '"';
            if (in_array($ID, $Value)) $Return .= ' selected="selected"';

            $Return .= '>' . $Text . "</option>\n";
         }
      }
      $Return .= '</select>';
      return $Return;
   }

   /**
    * Returns the xhtml for a standard radio input tag.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in $this->_DataArray.
    *
    * @param string $Label A label to place next to the radio.
    * @param array $Attributes An associative array of attributes for the input. ie. onclick, class, etc
    * @return string
    */
   public function Radio($FieldName, $Label = '', $Attributes = FALSE) {
      $Value = ArrayValueI('value', $Attributes, 'TRUE');
      $Attributes['value'] = $Value;
      $FormValue = $this->GetValue($FieldName, ArrayValueI('default', $Attributes));
      if ($FormValue == $Value) $Attributes['checked'] = 'checked';

      // DEBUG:
      // echo '<div>Value: '.$Value.' = FormValue: '.$FormValue.'; Default: '.ArrayValueI('default', $Attributes).'</div>';


      $Input = $this->Input($FieldName, 'radio', $Attributes);
      if ($Label != '') $Input = '<label for="' . ArrayValueI('id', $Attributes,
         $this->EscapeID($FieldName, FALSE)) . '" class="RadioLabel">' . $Input . ' ' .
          T($Label) . '</label>';

      return $Input;
   }

   /**
    * Returns the xhtml for a radio button list.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    * should related directly to a field name in $this->_DataArray. ie. RoleID
    *
    * @param mixed $DataSet The data to fill the options in the select list. Either an associative
    * array or a database dataset.
    *
    * @param array $Attributes An associative array of attributes for the list. Here is a list of
    * "special" attributes and their default values:
    *
    *   Attribute   Options                        Default
    *   ------------------------------------------------------------------------
    *   ValueField  The name of the field in       'value'
    *               $DataSet that contains the
    *               option values.
    *   TextField   The name of the field in       'text'
    *               $DataSet that contains the
    *               option text.
    *   Value       A string or array of strings.  $this->_DataArray->$FieldName
    *   Default     The default value.             empty
    *   IncludeNull Include a blank row?           FALSE
    * @return string
    */
   public function RadioList($FieldName, $DataSet, $Attributes = FALSE) {
      $List = GetValue('list', $Attributes);
      $Return = '';

      if ($List) {
         $Return .= '<ul'.(isset($Attributes['listclass']) ? " class=\"{$Attributes['listclass']}\"" : '').'>';
         $LiOpen = '<li>';
         $LiClose = '</li>';
      } else {
         $LiOpen = '';
         $LiClose = '';
      }

      if (is_object($DataSet)) {
         $ValueField = ArrayValueI('ValueField', $Attributes, 'value');
         $TextField = ArrayValueI('TextField', $Attributes, 'text');
         $Data = $DataSet->FirstRow();
         if (property_exists($Data, $ValueField) && property_exists($Data,
            $TextField)) {
            foreach($DataSet->Result() as $Data) {
               $Attributes['value'] = $Data->$ValueField;

               $Return .= $LiOpen.$this->Radio($FieldName, $Data->$TextField, $Attributes).$LiClose;
            }
         }
      } elseif (is_array($DataSet)) {
         foreach($DataSet as $ID => $Text) {
            $Attributes['value'] = $ID;
            $Return .= $LiOpen.$this->Radio($FieldName, $Text, $Attributes).$LiClose;
         }
      }

      if ($List)
         $Return .= '</ul>';

      return $Return;
   }

   /**
    * Returns the xhtml for all form-related errors that have occurred.
    *
    * @return string
    */
   public function Errors() {
      $Return = '';
      if (is_array($this->_ValidationResults) && count($this->_ValidationResults) > 0) {
         $Return = "<div class=\"Messages Errors\">\n<ul>\n";
         foreach($this->_ValidationResults as $FieldName => $Problems) {
            $Count = count($Problems);
            for($i = 0; $i < $Count; ++$i) {
               if (substr($Problems[$i], 0, 1) == '@')
                  $Return .= '<li>'.substr($Problems[$i], 1)."</li>\n";
               else
                  $Return .= '<li>' . sprintf(
                     T($Problems[$i]),
                     T($FieldName)) . "</li>\n";
            }
         }
         $Return .= "</ul>\n</div>\n";
      }
      return $Return;
   }

   /**
    * Returns the xhtml for all hidden fields.
    *
    * @todo reviews damien's summary of this Form::GetHidden()
    * @return string
    */
   public function GetHidden() {
      $Return = '';
      if (is_array($this->HiddenInputs)) {
         foreach($this->HiddenInputs as $Name => $Value) {
            $Return .= $this->Hidden($Name, array('value' => $Value));
         }
         // Clean out the array
         // mosullivan - removed cleanout so that entry forms can all have the same hidden inputs added once on the entry/index view.
         // TODO - WATCH FOR BUGS BECAUSE OF THIS CHANGE.
         // $this->HiddenInputs = array();
      }
      return $Return;
   }


   /**
    * Returns the xhtml for a hidden input.
    *
    * @param string $FieldName The name of the field that is being hidden/posted with this input. It
    * should related directly to a field name in $this->_DataArray.
    * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
    * class, etc
    * @return string
    */
   public function Hidden($FieldName, $Attributes = FALSE) {
      $Return = '<input type="hidden"';
      $Return .= $this->_IDAttribute($FieldName, $Attributes);
      $Return .= $this->_NameAttribute($FieldName, $Attributes);
      $Return .= $this->_ValueAttribute($FieldName, $Attributes);
      $Return .= $this->_AttributesToString($Attributes);
      $Return .= ' />';
      return $Return;
   }

   /**
    * Returns the xhtml for a label element.
    *
    * @param string $TranslationCode The code to be translated and presented within the label tag.
    * @param string $FieldName The name of the field that the label is for.
    * @param array $Attributes An associative array of attributes for the input that the label is for.
    * This is only available in case the related input has a custom id
    * specified in the attributes array.
    *
    * @return string
    */
   public function Label($TranslationCode, $FieldName = '', $Attributes = FALSE) {
      if ($FieldName == '')
         return '<label'.$this->_AttributesToString($Attributes).'>' . T($TranslationCode) . "</label>\r\n";
      else
         return '<label for="' . ArrayValueI('id', $Attributes, $this->EscapeID($FieldName, FALSE)) . '"'.$this->_AttributesToString($Attributes).'>' . T($TranslationCode) . "</label>\r\n";
   }

   /// <param name="DataObject" type="object">
   /// An object containing the properties that represent the field names being
   /// placed in the controls returned by $this.
   /// </param>
   /**
    * Returns the xhtml for the opening of the form (the form tag and all
    * hidden elements).
    *
    * @param array $Attributes An associative array of attributes for the form tag. Here is a list of
    *  "special" attributes and their default values:
    *
    *   Attribute  Options     Default
    *   ----------------------------------------
    *   method     get,post    post
    *   action     [any url]   [The current url]
    *   ajax       TRUE,FALSE  FALSE
    *
    * @return string
    *
    * @todo check that missing DataObject parameter
    */
   public function Open($Attributes = FALSE) {
      $Return = '<form';
      if ($this->InputPrefix != '') $Return .= $this->_IDAttribute($this->InputPrefix,
         $Attributes);

      // Method
      $MethodFromAttributes = ArrayValueI('method', $Attributes);
      $this->Method = $MethodFromAttributes === FALSE ? $this->Method : $MethodFromAttributes;

      // Action
      $ActionFromAttributes = ArrayValueI('action', $Attributes);
      if ($this->Action == '')
         $this->Action = Url();
         
      $this->Action = $ActionFromAttributes === FALSE ? $this->Action : $ActionFromAttributes;

      if (strcasecmp($this->Method, 'get') == 0) {
         // The path is not getting passed on get forms so put them in hidden fields.
         $Action = strrchr($this->Action, '?');
         $Exclude = GetValue('Exclude', $Attributes, array());
         if ($Action !== FALSE) {
            $this->Action = substr($this->Action, 0, -strlen($Action));
            parse_str(trim($Action, '?'), $Query);
            $Hiddens = '';
            foreach ($Query as $Key => $Value) {
               if (in_array($Key, $Exclude))
                  continue;
               $Key = Gdn_Format::Form($Key);
               $Value = Gdn_Format::Form($Value);
               $Hiddens .= "\n<input type=\"hidden\" name=\"$Key\" value=\"$Value\" />";
            }
         }
      }

      $Return .= ' method="' . $this->Method . '"'
         .' action="' . $this->Action . '"'
         .$this->_AttributesToString($Attributes)
         .">\n<div>\n";

      if (isset($Hiddens))
         $Return .= $Hiddens;

      // Postback Key - don't allow it to be posted in the url (prevents csrf attacks & hijacks)
      if ($this->Method != "get") {
         $Session = Gdn::Session();
         $Return .= $this->Hidden('TransientKey',
            array('value' => $Session->TransientKey()));
         // Also add a honeypot if Forms.HoneypotName has been defined
         $HoneypotName = Gdn::Config(
            'Garden.Forms.HoneypotName');
         if ($HoneypotName) $Return .= $this->Hidden($HoneypotName,
            array('Name' => $HoneypotName, 'style' => "display: none;"));
      }

      // Render all other hidden inputs that have been defined
      $Return .= $this->GetHidden();
      return $Return;
   }

   /**
    * Returns the xhtml for a text-based input.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    *  should related directly to a field name in $this->_DataArray.
    * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
    *  class, etc
    * @return string
    */
   public function TextBox($FieldName, $Attributes = FALSE) {
      if (!is_array($Attributes))
         $Attributes = array();
      
      $MultiLine = ArrayValueI('MultiLine', $Attributes);
      
      if ($MultiLine) {
         $Attributes['rows'] = ArrayValueI('rows', $Attributes, '6'); // For xhtml compliance
         $Attributes['cols'] = ArrayValueI('cols', $Attributes, '100'); // For xhtml compliance
      }

      $CssClass = ArrayValueI('class', $Attributes);
      if ($CssClass == FALSE) $Attributes['class'] = $MultiLine ? 'TextBox' : 'InputBox';
      $Return = $MultiLine === TRUE ? '<textarea' : '<input type="text"';
      $Return .= $this->_IDAttribute($FieldName, $Attributes);
      $Return .= $this->_NameAttribute($FieldName, $Attributes);
      $Return .= $MultiLine === TRUE ? '' : $this->_ValueAttribute($FieldName, $Attributes);
      $Return .= $this->_AttributesToString($Attributes);
      
      $Value = ArrayValueI('value', $Attributes, $this->GetValue($FieldName));
      
      $Return .= $MultiLine === TRUE ? '>' . htmlentities($Value, ENT_COMPAT, 'UTF-8') . '</textarea>' : ' />';
      return $Return;
   }

   /**
    * Returns the xhtml for a standard input tag.
    *
    * @param string $FieldName The name of the field that is being displayed/posted with this input. It
    *  should related directly to a field name in $this->_DataArray.
    * @param string $Type The type attribute for the input.
    * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
    *  class, etc
    * @return string
    */
   public function Input($FieldName, $Type = 'text', $Attributes = FALSE) {
      if ($Type == 'text' || $Type == 'password') {
         $CssClass = ArrayValueI('class', $Attributes);
         if ($CssClass == FALSE) $Attributes['class'] = 'InputBox';
      }
      $Return = '<input type="' . $Type . '"';
      $Return .= $this->_IDAttribute($FieldName, $Attributes);
      if ($Type == 'file') $Return .= Attribute('name',
         ArrayValueI('Name', $Attributes, $FieldName));
      else $Return .= $this->_NameAttribute($FieldName, $Attributes);

      $Return .= $this->_ValueAttribute($FieldName, $Attributes);
      $Return .= $this->_AttributesToString($Attributes);
      $Return .= ' />';
      if (strtolower($Type) == 'checkbox') {
         if (substr($FieldName, -2) == '[]') $FieldName = substr($FieldName, 0, -2);

         $Return .= '<input type="hidden" name="Checkboxes[]" value="' . $FieldName .
             '" />';
      }

      return $Return;
   }


   /// =========================================================================
   /// 2. UI Convenience: Convenience methods for adding to UI components.
   /// =========================================================================

   /**
    * A string identifying the action with which the form should be sent. 
    *
    * @var string
    */
   public $Action = '';

   /**
    * An associative array of all hidden inputs with their "Name" attribute as
    * the key.
    *
    * @var array
    */
   public $HiddenInputs;

   /**
    * All form related tags (form, input, select, textarea, [etc] will have
    * this value prefixed on their ID attribute. Default is "Form_". If the
    * id value is overridden with the Attribute collection for an element, this
    *  value will not be used.
    *
    * @var string
    */
   public $IDPrefix = 'Form_';

   /**
    * All form related tags (form, input, select, textarea, [etc] will have
    * this value prefixed on their name attribute. Default is "Form".
    * If a model is assigned, the model name is used instead.
    *
    * @var string
    */
   public $InputPrefix = 'Form';

   /**
    * A string identifying the method with which the form should be sent. Valid
    * choices are: post, get. "post" is default.
    *
    * @var string
    */
   public $Method = 'post';

   /**
    * An associative array of $FieldName => $ValidationFunctionName arrays that
    * describe how each field specified failed validation.
    *
    * @var array
    */
   protected $_ValidationResults;

   /**
    * Adds a hidden input value to the form.
    *
    * If the $ForceValue parameter remains FALSE, it will grab the value into the hidden input from the form
    * on postback. Otherwise it will always force the assigned value to the
    * input regardless of postback.
    *
    * @param string $FieldName The name of the field being added as a hidden input on the form.
    * @param string $Value The value being assigned in the hidden input. Unless $ForceValue is
    *  changed to TRUE, this field will be retrieved from the form upon
    *  postback.
    * @param boolean $ForceValue
    */
   public function AddHidden($FieldName, $Value = NULL, $ForceValue = FALSE) {
      if ($this->IsPostBack() && $ForceValue === FALSE)
         $Value = $this->GetFormValue($FieldName, $Value);

      $this->HiddenInputs[$FieldName] = $Value;
   }


   /**
    * Adds an error to the errors collection and optionally relates it to the
    * specified FieldName. Errors added with this method can be rendered with
    * $this->Errors().
    *
    * @param mixed $ErrorCode
    *  - <b>string</b>: The translation code that represents the error to display.
    *  - <b>Exception</b>: The exception to display the message for.
    * @param string $FieldName The name of the field to relate the error to.
    */
   public function AddError($Error, $FieldName = '') {
      if(is_string($Error))
         $ErrorCode = $Error;
      elseif(is_a($Error, 'Gdn_UserException')) {
         $ErrorCode = '@'.$Error->getMessage();
      } elseif(is_a($Error, 'Exception')) {
         // Strip the extra information out of the exception.
         $Parts = explode('|', $Error->getMessage());
         $Message = $Parts[0];
         if (count($Parts) >= 3)
            $FileSuffix = ": {$Parts[1]}->{$Parts[2]}(...)";
         else
            $FileSuffix = "";

         if(defined('DEBUG')) {
            $ErrorCode = '@<pre>'.
               $Message."\n".
               '## '.$Error->getFile().'('.$Error->getLine().")".$FileSuffix."\n".
               $Error->getTraceAsString().
               '</pre>';
         } else {
            $ErrorCode = '@'.strip_tags($Error->getMessage());
         }
      }
      
      if ($FieldName == '') $FieldName = '<General Error>';

      if (!is_array($this->_ValidationResults)) $this->_ValidationResults = array();

      if (!array_key_exists($FieldName, $this->_ValidationResults)) {
         $this->_ValidationResults[$FieldName] = array($ErrorCode);
      } else {
         if (!is_array($this->_ValidationResults[$FieldName])) $this->_ValidationResults[$FieldName] = array(
            $this->_ValidationResults[$FieldName],
            $ErrorCode);
         else $this->_ValidationResults[$FieldName][] = $ErrorCode;
      }
   }


   /// =========================================================================
   /// 3. Middle Tier: Methods & Properties that are used when interfacing with
   /// the model & db.
   /// =========================================================================


   /**
    * An associative array containing the key => value pairs being placed in
    * the controls returned by this object. This array is assigned by
    * $this->Open() or $this->SetData().
    *
    * @var object
    * @todo you probably mean array for type?
    */
   protected $_DataArray;

   /**
    * The model that enforces data rules on $this->_DataArray.
    *
    * @var object
    */
   protected $_Model;

   /**
    * A collection of IDs that have been created for form elements. This
    * private property is used to record all IDs so that duplicate IDs are not
    * added to the screen.
    *
    * @var array
    */
   private $_IDCollection = array();

   /**
    * An associative array of $Field => $Value pairs that represent data posted
    * from the form in the $_POST or $_GET collection (depending on which
    * method was specified for sending form data in $this->Method). This array
    * is populated with and can be accessed by $this->FormValues(), and
    * individual values can be retrieved from it with
    * $this->GetFormValue($FieldName).
    *
    * @var array
    */
   private $_FormValues;

   /**
    * Constructor
    *
    * @param string $TableName
    */
   public function __construct($TableName = '') {
      if ($TableName != '') {
         $TableModel = new Gdn_Model($TableName);
         $this->SetModel($TableModel);
      }
   }

   /**
    * Returns a boolean value indicating if the current page has an
    * authenticated postback. It validates the postback by looking at a
    * transient value that was rendered using $this->Open() and submitted with
    * the form. Ref: http://en.wikipedia.org/wiki/Cross-site_request_forgery
    *
    * @return boolean
    */
   public function AuthenticatedPostBack() {
      // Commenting this out because, technically, a get request is not a "postback".
      // And since I typically use AuthenticatedPostBack to validate that a form has
      // been posted back a get request should not be considered an authenticated postback.
      //if ($this->Method == "get") {
      // forms sent with "get" method do not require authentication.
      //   return TRUE;
      //} else {
      $KeyName = $this->InputPrefix . '/TransientKey';
      $PostBackKey = isset($_POST[$KeyName]) ? $_POST[$KeyName] : FALSE;
      $Session = Gdn::Session();
      // DEBUG:
      //echo '<div>KeyName: '.$KeyName.'</div>';
      //echo '<div>PostBackKey: '.$PostBackKey.'</div>';
      //echo '<div>TransientKey: '.$Session->TransientKey().'</div>';
      //echo '<div>AuthenticatedPostBack: ' . ($Session->ValidateTransientKey($PostBackKey) ? 'Yes' : 'No');
      //die();
      return $Session->ValidateTransientKey($PostBackKey);
      //}
   }

   /**
    * Returns a count of the number of errors that have occurred.
    *
    * @return int
    */
   public function ErrorCount() {
      if (!is_array($this->_ValidationResults)) $this->_ValidationResults = array();

      return count($this->_ValidationResults);
   }

   /**
    * Returns the provided fieldname with non-alpha-numeric values stripped.
    *
    * @param string $FieldName The field name to escape.
    * @return string
    */
   public function EscapeFieldName($FieldName) {
      $Return = $this->InputPrefix;
      if ($Return != '') $Return .= '/';
      return $Return . $this->EscapeString($FieldName);
   }

   /**
    * Returns the provided fieldname with non-alpha-numeric values stripped and
    * $this->IDPrefix prepended.
    *
    * @param string $FieldName
    * @param bool $ForceUniqueID
    * @return string
    */
   public function EscapeID(
      $FieldName, $ForceUniqueID = TRUE) {
      $ID = $FieldName;
      if (substr($ID, -2) == '[]') $ID = substr($ID, 0, -2);

      $ID = $this->IDPrefix . Gdn_Format::AlphaNumeric(str_replace('.', '-dot-', $ID));
      $tmp = $ID;
      $i = 1;
      if ($ForceUniqueID === TRUE) {
         while(in_array($tmp, $this->_IDCollection)) {
            $tmp = $ID . $i;
            $i++;
         }
         $this->_IDCollection[] = $tmp;
      } else {
         // If not forcing unique (ie. getting the id for a label's "for" tag),
         // get the last used copy of the requested id.
         $Found = FALSE;
         while(in_array($tmp, $this->_IDCollection)) {
            $Found = TRUE;
            $tmp = $ID . $i;
            $i++;
         }
         if ($Found == TRUE && $i > 2) {
            $i = $i - 2;
            $tmp = $ID . $i;
         } else {
            $tmp = $ID;
         }
      }
      return $tmp;
   }

   /**
    * Gets the value associated with $FieldName from the sent form fields.
    * If $FieldName isn't found in the form, it returns $Default.
    *
    * @param string $FieldName The name of the field to get the value of.
    * @param mixed $Default The default value to return if $FieldName isn't found.
    * @return unknown
    */
   public function GetFormValue($FieldName, $Default = '') {
      return ArrayValue($FieldName, $this->FormValues(), $Default);
   }

   /**
    * Gets the value associated with $FieldName.
    *
    * If the form has been posted back, it will retrieve the value from the form.
    * If it hasn't been posted back, it gets the value from $this->_DataArray.
    * Failing either of those, it returns $Default.
    *
    * @param string $FieldName
    * @param mixed $Default
    * @return mixed
    *
    * @todo check returned value type
    */
   public function GetValue($FieldName, $Default = FALSE) {
      $Return = '';
      // Only retrieve values from the form collection if this is a postback.
      if ($this->IsPostBack()) {
         $Return = $this->GetFormValue($FieldName, $Default);
      } else {
         $Return = ArrayValue($FieldName, $this->_DataArray, $Default);
      }
      return $Return;
   }

   /**
    * Checks $this->FormValues() to see if the specified button translation
    * code was submitted with the form (helps figuring out what button was
    *  pressed to submit the form when there is more than one button available).
    *
    * @param string $ButtonCode The translation code of the button to check for.
    * @return boolean
    */
   public function ButtonExists($ButtonCode) {
      $NameKey = $this->EscapeString($ButtonCode);
      return array_key_exists($NameKey, $this->FormValues()) ? TRUE : FALSE;
   }

   /**
    * Examines the sent form variable collection to see if any data was sent
    * via the form back to the server. Returns TRUE on if anything is found.
    *
    * @return boolean
    */
   public function IsPostBack() {
      /*
      2009-01-10 - $_GET should not dictate a "post" back.
      return count($_POST) > 0 ? TRUE : FALSE;
      
      2009-03-31 - switching back to "get" dictating a postback
      */
      $FormCollection = $this->Method == 'get' ? $_GET : $_POST;
      return count($FormCollection) > 0 || (is_array($this->_FormValues) && count($this->_FormValues) > 0) ? TRUE : FALSE;
   }

   /**
    * This is a convenience method so that you don't have to code this every time
    * you want to save a simple model's data.
    *
    * It uses the assigned model to save the sent form fields.
    * If saving fails, it populates $this->_ValidationResults with validation errors & related fields.
    *
    * @return unknown
    */
   public function Save() {
      $SaveResult = FALSE;
      if ($this->ErrorCount() == 0) {
         if (!isset($this->_Model)) trigger_error(
            ErrorMessage(
               "You cannot call the form's save method if a model has not been defined.",
               "Form", "Save"), E_USER_ERROR);

         $Args = array_merge(func_get_args(),
            array(
               NULL,
               NULL,
               NULL,
               NULL,
               NULL,
               NULL,
               NULL,
               NULL,
               NULL,
               NULL));
         $SaveResult = $this->_Model->Save($this->FormValues(), $Args[0], $Args[1],
            $Args[2], $Args[3], $Args[4], $Args[5], $Args[6], $Args[7],
            $Args[8], $Args[9]);
         if ($SaveResult === FALSE) {
            // NOTE: THE VALIDATION FUNCTION NAMES ARE ALSO THE LANGUAGE
            // TRANSLATIONS OF THE ERROR MESSAGES. CHECK THEM OUT IN THE LOCALE
            // FILE.
            $this->SetValidationResults($this->_Model->ValidationResults());
         }
      }
      return $SaveResult;
   }

   /**
    * @todo add documentation
    */
   public function SetValidationResults($ValidationResults) {
      if (!is_array($this->_ValidationResults)) $this->_ValidationResults = array();

      $this->_ValidationResults = array_merge($this->_ValidationResults, $ValidationResults);
   }

   /**
    * Sets the value associated with $FieldName from the sent form fields.
    * Essentially overwrites whatever was retrieved from the form.
    *
    * @param string $FieldName The name of the field to set the value of.
    * @param mixed $Value The new value of $FieldName.
    */
   public function SetFormValue($FieldName, $Value) {
      $this->FormValues();
      $this->_FormValues[$FieldName] = $Value;
   }
   
   /**
    * Sets the value associated with $FieldName.
    *
    * It sets the value in $this->_DataArray rather than in $this->_FormValues.
    *
    * @param string $FieldName
    * @param mixed $Default
    */
   public function SetValue($FieldName, $Value) {
      if (!is_array($this->_DataArray))
         $this->_DataArray = array();
      
      $this->_DataArray[$FieldName] = $Value;
   }   

   /**
    * If not saving data directly to the model, this method allows you to
    * utilize a model's schema to validate a form's inputs regardless.
    *
    * ie. A sign-in form that just needs to compare data to the model and still
    * enforce it's rules. Returns the number of errors that were recorded
    * through validation.
    *
    * @return int
    */
   public function ValidateModel() {
      $this->_Model->DefineSchema();
      if ($this->_Model->Validation->Validate($this->FormValues()) === FALSE) $this->_ValidationResults = $this->_Model->ValidationResults();
      return $this->ErrorCount();
   }

   /**
    * Validates a rule on the form and adds its result to the errors collection.
    *
    * @param string $FieldName The name of the field to validate.
    * @param string|array $Rule The rule to validate against.
    * @param string $CustomError A custom error string.
    * @return bool Whether or not the rule succeeded.
    *
    * @see Gdn_Validation::ValidateRule()
    */
   public function ValidateRule($FieldName, $Rule, $CustomError = '') {
      $Value = $this->GetFormValue($FieldName);
      $Valid = Gdn_Validation::ValidateRule($Value, $FieldName, $Rule, $CustomError);

      if ($Valid === TRUE)
         return TRUE;
      else {
         $this->AddError('@'.$Valid);
         return FALSE;
      }
      
   }

   /**
    * Assign a set of data to be displayed in the form elements.
    *
    * @param Ressource $Data A result resource or associative array containing data to be filled in
    */
   public function SetData($Data) {
      if (is_object($Data) === TRUE) {
         // If this is a result object (/garden/library/database/class.dataset.php)
         // retrieve it's values as arrays
         if ($Data instanceof DataSet) {
            $ResultSet = $Data->ResultArray();
            if (count($ResultSet) > 0)
               $this->_DataArray = $ResultSet[0];
               
         } else {
            // Otherwise assume it is an object representation of a data row.
            $this->_DataArray = Gdn_Format::ObjectAsArray($Data);
         }
      } else if (is_array($Data)) {
         $this->_DataArray = $Data;
      }
   }

   /**
    * Set the name of the model that will enforce data rules on $this->_DataArray.
    *
    * This value is also used to identify fields in the $_POST or $_GET
    * (depending on the forms method) collection when the form is submitted.
    *
    * @param object $Model The Model that will enforce data rules on $this->_DataArray. This value
    *  is passed by reference so any changes made to the model outside this
    *  object apply when it is referenced here.
    * @param Ressource $DataSet A result resource containing data to be filled in the form.
    */
   public function SetModel(&$Model, $DataSet = FALSE) {
      $this->_Model = &$Model;
      $this->InputPrefix = $this->_Model->Name;
      if ($DataSet !== FALSE) $this->SetData($DataSet);
   }

   /**
    * Takes an associative array of $Attributes and returns them as a string of
    * param="value" sets to be placed in an input, select, textarea, etc tag.
    *
    * @param array $Attributes An associative array of attribute key => value pairs to be converted to a
    *  string. A number of "reserved" keys will be ignored: 'id', 'name',
    *  'maxlength', 'value', 'method', 'action', 'type'.
    * @return string
    */
   protected function _AttributesToString($Attributes) {
      $Return = '';
      if (is_array($Attributes)) {
         foreach($Attributes as $Attribute => $Value) {
            // Ignore reserved attributes
            if (!in_array(
               strtolower($Attribute),
               array(
                  'id',
                  'name',
                  'value',
                  'method',
                  'action',
                  'type',
                  'multiline',
                  'default',
                  'textfield',
                  'valuefield',
                  'includenull'))) $Return .= ' ' . $Attribute .
                '="' . $Value . '"';
         }
      }
      return $Return;
   }

   /**
    * If the form has been posted back, this method return an associative
    * array of $FieldName => $Value pairs which were sent in the form.
    *
    * Note: these values are typically used by the model and it's validation object.
    *
    * @return array
    */
   public function FormValues($NewValue = NULL) {
      if($NewValue !== NULL) {
         $this->_FormValues = $NewValue;
         return;
      }

      if (!is_array($this->_FormValues)) {
         $TableName = $this->InputPrefix;
         if(strlen($TableName) > 0)
            $TableName .= '/';
         $TableNameLength = strlen($TableName);
         $this->_FormValues = array();
         $Collection = $this->Method == 'get' ? $_GET : $_POST;
         $InputType = $this->Method == 'get' ? INPUT_GET : INPUT_POST;
         
         foreach($Collection as $Field => $Value) {
            $FieldName = substr($Field, $TableNameLength);
            $FieldName = $this->_UnescapeString($FieldName);
            if (substr($Field, 0, $TableNameLength) == $TableName) {
               if (is_array($Value)) {
                  $this->_FormValues[$FieldName] = filter_input(
                     $InputType,
                     $Field,
                     FILTER_SANITIZE_STRING,
                     FILTER_REQUIRE_ARRAY
                  );
               } else {
                  $this->_FormValues[$FieldName] = filter_input(
                     $InputType,
                     $Field
                  );
               }
            }
         }
         
         // Make sure that unchecked checkboxes get added to the collection
         if (array_key_exists('Checkboxes', $Collection)) {
            $UncheckedCheckboxes = $Collection['Checkboxes'];
            if (is_array($UncheckedCheckboxes) === TRUE) {
               $Count = count($UncheckedCheckboxes);
               for($i = 0; $i < $Count; ++$i) {
                  if (!array_key_exists($UncheckedCheckboxes[$i], $this->_FormValues))
                     $this->_FormValues[$UncheckedCheckboxes[$i]] = FALSE;
               }
            }
         }
         
         // Make sure that Date inputs (where the day, month, and year are
         // separated into their own dropdowns on-screen) get added to the
         // collection as a single field as well...
         if (array_key_exists(
            'DateFields', $Collection) === TRUE) {
            $DateFields = $Collection['DateFields'];
            if (is_array($DateFields) === TRUE) {
               $Count = count($DateFields);
               for($i = 0; $i < $Count; ++$i) {
                  if (array_key_exists(
                     $DateFields[$i],
                     $this->_FormValues) ===
                      FALSE) // Saving dates in the format: YYYY-MM-DD
                     $Year = ArrayValue(
                        $DateFields[$i] .
                         '_Year',
                        $this->_FormValues,
                        0);
                  $Month = ArrayValue(
                     $DateFields[$i] .
                         '_Month',
                        $this->_FormValues,
                        0);
                  $Day = ArrayValue(
                     $DateFields[$i] .
                         '_Day',
                        $this->_FormValues,
                        0);
                  $Month = str_pad(
                     $Month,
                     2,
                     '0',
                     STR_PAD_LEFT);
                  $Day = str_pad(
                     $Day,
                     2,
                     '0',
                     STR_PAD_LEFT);
                  $this->_FormValues[$DateFields[$i]] = $Year .
                      '-' .
                      $Month .
                      '-' .
                      $Day;
               }
            }
         }
      }

      // print_r($this->_FormValues);
      return $this->_FormValues;
   }

   public function FormDataSet() {
      if(is_null($this->_FormValues)) {
         $this->FormValues();
      }
      
      $Result = array(array());
      foreach($this->_FormValues as $Key => $Value) {
         if(is_array($Value)) {
            foreach($Value as $RowIndex => $RowValue) {
               if(!array_key_exists($RowIndex, $Result))
                  $Result[$RowIndex] = array($Key => $RowValue);
               else
                  $Result[$RowIndex][$Key] = $RowValue;
            }
         } else {
            $Result[0][$Key] = $Value;
         }
      }
      
      return $Result;
   }

   /**
    * Emptys the $this->_FormValues collection so that all form fields will load empty.
    */
   public function ClearInputs() {
      $this->_FormValues = array();
   }

   /**
    * Encodes the string in a php-form safe-encoded format.
    *
    * @param string $String The string to encode.
    * @return string
    */
   public function EscapeString($String) {
      $Array = FALSE;
      if (substr($String, -2) == '[]') {
         $String = substr($String, 0, -2);
         $Array = TRUE;
      }
      $Return = urlencode(str_replace(' ', '_', $String));
      if ($Array === TRUE) $Return .= '[]';

      return str_replace('.', '-dot-', $Return);
   }

   /**
    * Decodes the encoded string from a php-form safe-encoded format to the
    * format it was in when presented to the form.
    *
    * @param string $EscapedString
    * @return unknown
    */
   protected function _UnescapeString(
      $EscapedString) {
      $Return = str_replace('-dot-', '.', $EscapedString);
      return urldecode($Return);
   }

   /**
    * Creates an ID attribute for a form input and returns it in this format:
    *  [ id="IDNAME"]
    *
    * @param string $FieldName The name of the field that is being converted to an ID attribute.
    * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
    * class, etc. If $Attributes contains an 'id' key, it will override the
    * one automatically generated by $FieldName.
    * @return string
    */
   protected function _IDAttribute(
      $FieldName, $Attributes) {
      // ID from attributes overrides the default.
      return ' id="' . ArrayValueI('id', $Attributes,
         $this->EscapeID($FieldName)) . '"';
   }

   /**
    * Creates a NAME attribute for a form input and returns it in this format:
    * [ name="NAME"]
    *
    * @param string $FieldName The name of the field that is being converted to a NAME attribute.
    * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
    * class, etc. If $Attributes contains a 'name' key, it will override the
    * one automatically generated by $FieldName.
    * @return string
    */
   protected function _NameAttribute($FieldName, $Attributes) {
      // Name from attributes overrides the default.
      if(is_array($Attributes) && array_key_exists('Name', $Attributes)) {
         $Name = $Attributes['Name'];
      } else {
         $Name = $this->EscapeFieldName($FieldName);
      }
      if(empty($Name))
         $Result = '';
      else
         $Result = ' name="' . $Name . '"';

      return $Result;
   }

   /**
    * Creates a VALUE attribute for a form input and returns it in this format:
    * [ value="VALUE"]
    *
    * @param string $FieldName The name of the field that contains the value in $this->_DataArray.
    * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
    * class, etc. If $Attributes contains a 'value' key, it will override the
    * one automatically generated by $FieldName.
    * @return string
    */
   protected function _ValueAttribute($FieldName, $Attributes) {
      // Value from $Attributes overrides the datasource and the postback.
      return ' value="' . Gdn_Format::Form(ArrayValueI('value', $Attributes, $this->GetValue($FieldName))) . '"';
   }
}