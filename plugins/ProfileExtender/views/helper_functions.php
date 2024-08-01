<?php

if (!function_exists('extendedProfileFields')) {
    /**
     * Output markup for extended profile fields.
     *
     * @param array $profileFields Formatted profile fields.
     * @param array $allFields Extended profile field data.
     * @param array $magicLabels "Magic" labels configured on the Profile Extender plug-in class.
     */
    function extendedProfileFields($profileFields, $allFields, $magicLabels = []) {
        foreach ($profileFields as $name => $value) {
            // Skip empty and hidden fields.
            $showOnProfile = false;
            $fieldLabel = null;
            foreach($allFields as $field){
                if(isset($field['Name']) && $name === $field['Name']){
                    if($field['OnProfile']){
                        $showOnProfile = true;
                        $fieldLabel = $field['Label'];
                    }
                }
            }
            if (!$value || !$showOnProfile) {
                continue;
            }

            // Non-magic fields must be plain text, but we'll auto-link
            if (!in_array($name, $magicLabels)) {
                $value = Gdn_Format::links(Gdn_Format::text($value));
            }

            $class = 'Profile'.Gdn_Format::alphaNumeric($name);
            $label = Gdn_Format::text(Gdn::translate($fieldLabel));
            $filteredVal = Gdn_Format::htmlFilter($value);

            echo " <dt class=\"ProfileExtend {$class}\">{$label}</dt> ";
            echo " <dd class=\"ProfileExtend {$class}\">{$filteredVal}</dd> ";
        }
    }
}
