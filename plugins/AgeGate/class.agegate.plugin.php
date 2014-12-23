<?php

$PluginInfo['AgeGate'] = array(
    'Name' => 'Age Gate',
    'Description' => 'Add Date of Birth to the registration form, and require a certain age to register.',
    'Version' => '1.0',
    'Author' => "John Ashton",
    'AuthorEmail' => 'john@vanillaforums.com',
    'SettingsUrl' => '/settings/agegate', // Url of the plugin's settings page.

);

class AgeGatePlugin extends Gdn_Pluggable implements Gdn_IPlugin {


    /**
     * @param EntryController $sender Sending Controller.
     * @param array $args Arguments.
     */
    public function EntryController_RegisterBeforeTerms_Handler($sender, $args) {
        $this->EntryController_RegisterFormBeforeTerms_Handler($sender, $args);
    }

    /**
     * @param EntryController $sender Sending Controller.
     * @param array $args Arguments.
     */
    public function EntryController_RegisterFormBeforeTerms_Handler($sender, $args) {

        $days = array_merge(
            array(0 => T('Day')),
            array_combine(range(1, 31), range(1,31))
        );
        $months = array_merge(
            array(0 => T('Month')),
            array_combine(range(1, 12), range(1, 12))
        );
        $years = array_combine(
                range(C('Plugins.AgeGate.StartYear', date('Y')), C('Plugins.AgeGate.StartYear', date('Y')-100)),
                range(C('Plugins.AgeGate.StartYear', date('Y')), C('Plugins.AgeGate.StartYear', date('Y')-100))
            );
        $years = array(0 => T('Year')) + $years;

        echo '<li>';
        echo $sender->Form->Label('Date of Birth', 'DOB');
        echo $sender->Form->DropDown('Day', $days, array('class' => 'AgeGate'));
        echo ' ';
        echo $sender->Form->DropDown('Month', $months, array('class' => 'AgeGate'));
        echo ' ';
        echo $sender->Form->DropDown('Year', $years, array('class' => 'AgeGate'));
        echo '</li>';
    }

    /**
     * @param EntryController $sender Sending Controller.
     * @param array $args Arguments.
     */
    public function EntryController_RegisterValidation_Handler($sender, $args) {

        $day = (int)$sender->Form->GetFormValue('Day', 0);
        $month = (int)$sender->Form->GetFormValue('Month', 0);
        $year = (int)$sender->Form->GetFormValue('Year', 0);

        if ($day == 0 || $year == 0 || $month == 0) {
            $sender->UserModel->Validation->AddValidationResult('', "Please select a valid Date of Birth.");
            return;
        }

        $dob = Gdn_Format::ToDateTime(mktime(0, 0, 0, $month, $day, $year));
        $datetime1 = new DateTime($year . '-' . $month . '-' . $day);
        $datetime2 = new DateTime();

        $interval = $datetime1->diff($datetime2);
        $age =  $interval->format('%y');
        $minimumAge = C('Plugins.AgeGate.MinimumAge', 0);
        if ($age < $minimumAge) {
            $sender->UserModel->Validation->AddValidationResult('', sprintf("You must be at least %d years old to Register.", $minimumAge));
            return;
        }

        // Set the value on the form so that it will be saved to user model
        if ($sender->Form->ErrorCount() == 0 && !$sender->UserModel->Validation->Results()) {
            $sender->Form->_FormValues['DateOfBirth'] = $dob;
        }

    }


    /**
     * @param SettingsController $sender
     */
    public function SettingsController_AgeGate_Create($sender) {

        $sender->Permission('Garden.Settings.Manage');
        $sender->SetData('Title', T('Age Gate Settings'));
        $sender->AddSideMenu();

        if ($sender->Form->IsPostBack() && $sender->Form->AuthenticatedPostBack()) {
            $minimumAge = $sender->Form->GetValue('MinimumAge');

            if (!is_numeric($minimumAge)) {
                $sender->Form->AddError('Please enter a valid number.');
            }
            if ($sender->Form->ErrorCount() == 0) {
                SaveToConfig('Plugins.AgeGate.MinimumAge', $minimumAge);
            }
        } else {
            $sender->Form->SetData(array('MinimumAge' => C('Plugins.AgeGate.MinimumAge')));
        }

        $sender->Render($sender->FetchViewLocation('settings', '', 'plugins/AgeGate'));
    }

    public function Setup() {
        // No setup required
    }

}
