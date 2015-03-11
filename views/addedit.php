<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->Data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->Open();
echo $Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $Form->Label('Name', 'Name');
         echo '<div class="Info2">', T('Enter a descriptive name.', 'Enter a descriptive name for the pocket. This name will not show up anywhere except when managing your pockets here so it is only used to help you remember the pocket.'), '</div>';
         echo $Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Body', 'Body');
         echo '<div class="Info2">', T('The text of the pocket.', 'Enter the text of the pocket. This will be output exactly as you type it so make sure that you enter valid HTML.'), '</div>';
         echo $Form->TextBox('Body', array('Multiline' => TRUE));
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Page', 'Page');
         echo '<div class="Info2">', T('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>';
         echo $Form->DropDown('Page', $this->Data('Pages'));
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Location', 'Location');
         echo '<div class="Info2">', T('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>';
         echo $Form->DropDown('Location', array_merge(array('' => '('.sprintf(T('Select a %s'), T('Location')).')'), $this->Data('LocationsArray')));
         // Write the help for each location type.
         foreach ($this->Data('Locations') as $Location => $Options) {
            if (!array_key_exists('Description', $Options))
               continue;

            echo '<div class="Info LocationInfo '.$Location.'Info">',
               Gdn_Format::Html($Options['Description']),
               '</div>';
         }
      ?>
   </li>
   <li class="js-repeat">
      <?php
         echo $Form->Label('Repeat', 'RepeatType');
//
         echo '<div>', $Form->Radio('RepeatType', 'Before', array('Value' => Pocket::REPEAT_BEFORE, 'Default' => true)), '</div>';
//
         echo '<div>', $Form->Radio('RepeatType', 'After', array('Value' => Pocket::REPEAT_AFTER)), '</div>';
//
         echo '<div>', $Form->Radio('RepeatType', 'Repeat Every', array('Value' => Pocket::REPEAT_EVERY)), '</div>';

         // Options for repeat every.
         echo '<div class="RepeatOptions RepeatEveryOptions P">',
            '<div class="Info2">', T('Enter numbers starting at 1.'), '</div>',
            $Form->Label('Frequency', 'EveryFrequency', array('Class' => 'SubLabel')),
            $Form->TextBox('EveryFrequency', array('Class' => 'SmallInput')),
            ' <br /> '.$Form->Label('Begin At', 'EveryBegin', array('Class' => 'SubLabel')),
            $Form->TextBox('EveryBegin', array('Class' => 'SmallInput')),
            '</div>';

         echo '<div>', $Form->Radio('RepeatType', 'Given Indexes', array('Value' => Pocket::REPEAT_INDEX)), '</div>';

         // Options for repeat indexes.
         echo '<div class="RepeatOptions RepeatIndexesOptions P">',
            '<div class="Info2">', T('Enter a comma-delimited list of indexes, starting at 1.'), '</div>',
            $Form->Label('Indexes', 'Indexes', array('Class' => 'SubLabel')),
            $Form->TextBox('Indexes'),
            '</div>';
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Conditions', '');
         // echo '<div class="Info2">', T('Limit the pocket to one or more roles or permissions.'), '</div>';
         // $this->ConditionModule->Render();

         echo '<div class="Info2">', T('Limit the display of this pocket to "mobile only".'), '</div>';
         echo $Form->CheckBox("MobileOnly", T("Only display on mobile browsers."));

         echo '<div class="Info2">', T('Limit the display of this pocket for mobile devices.'), '</div>';
         echo $Form->CheckBox("MobileNever", T("Never display on mobile browsers."));

         echo '<div class="Info2">', T('Limit the display of this pocket for embedded comments.'), '</div>';
         echo $Form->CheckBox("EmbeddedNever", T("Don't display for embedded comments."));

         echo '<div class="Info2">', T("Most pockets shouldn't be displayed in the dashboard."), '</div>';
         echo $Form->CheckBox("ShowInDashboard", T("Display in dashboard. (not recommended)"));

         echo '<div class="Info2">', T("Users with the no ads permission will not see this pocket."), '</div>';
         echo $Form->CheckBox("Ad", T("This pocket is an ad."));
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Enable/Disable', 'Disabled');

         echo '<div>', $Form->Radio('Disabled', T('Enabled', 'Enabled: The pocket will be displayed.'), array('Value' => Pocket::ENABLED)), '</div>';

         echo '<div>', $Form->Radio('Disabled', T('Disabled', 'Disabled: The pocket will <b>not</b> be displayed.'), array('Value' => Pocket::DISABLED)), '</div>';

         echo '<div>', $Form->Radio('Disabled', T('Test Mode', 'Test Mode: The pocket will only be displayed for pocket administrators.'), array('Value' => Pocket::TESTING)), '</div>';


//         echo $Form->Label('Enable/Disable', 'Disabled');
//         echo $Form->RadioList('Disabled', array(Pocket::ENABLED => T('Enabled'), Pocket::DISABLED => T('Disabled'), Pocket::TESTING => T('Test Mode')));
      ?>
   </li>
</ul>
<?php
echo $Form->Button('Save'),
   '&nbsp;&nbsp;&nbsp;&nbsp;', Anchor(T('Cancel'), '/settings/pockets', 'Cancel'), ' ',
   $Form->Close();
?>
