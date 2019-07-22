<?php if (!defined('APPLICATION')) exit();

if (!function_exists('UserVerified')):
    /**
     * Return the verified status of a user with a link to change it.
     * @param array|object $user
     * @return string
     */
    function userVerified($user) {
        $userID = val('UserID', $user);

        if (val('Verified', $user)) {
            $label = t('Verified');
            $title = t('Verified Description', 'Verified users bypass spam and pre-moderation filters.');
            $url = "/user/verify.json?userid=$userID&verified=0";
        } else {
            $label = t('Not Verified');
            $title = t('Not Verified Description', 'Unverified users are passed through spam and pre-moderation filters.');
            $url = "/user/verify.json?userid=$userID&verified=1";
        }

        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            return anchor($label, $url, ['title' => $title, 'class' => 'User-Verified Hijack']);
        } else {
            return wrap($label, 'span', ['title' => $title, 'class' => 'User-Verified']);
        }
    }

endif;
