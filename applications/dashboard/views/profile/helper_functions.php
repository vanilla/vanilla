<?php if (!defined('APPLICATION')) exit();

if (!function_exists('UserVerified')):
    /**
     * Return the verified status of a user with a link to change it.
     * @param array|object $User
     * @return string
     */
    function userVerified($User) {
        $UserID = val('UserID', $User);

        if (val('Verified', $User)) {
            $Label = t('Verified');
            $Title = t('Verified Description', 'Verified users bypass spam and pre-moderation filters.');
            $Url = "/user/verify.json?userid=$UserID&verified=0";
        } else {
            $Label = t('Not Verified');
            $Title = t('Not Verified Description', 'Unverified users are passed through spam and pre-moderation filters.');
            $Url = "/user/verify.json?userid=$UserID&verified=1";
        }

        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            return anchor($Label, $Url, array('title' => $Title, 'class' => 'User-Verified Hijack'));
        } else {
            return wrap($Label, 'span', array('title' => $Title, 'class' => 'User-Verified'));
        }
    }

endif;
