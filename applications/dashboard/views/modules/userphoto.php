<?php use Vanilla\Theme\BoxThemeShim;if (!defined('APPLICATION')) exit();
$dataDriven = \Gdn::themeFeatures()->useDataDrivenTheme();
$User = val('User', Gdn::controller());
if (!$User && Gdn::session()->isValid()) {
    $User = Gdn::session()->User;
}

if (!$User) {
    return;
}

$Photo = $User->Photo;
if ($Photo) {
    $Photo = (isUrl($Photo)) ? $Photo : Gdn_Upload::url(changeBasename($Photo, 'p%s'));
    $PhotoAlt = t('Avatar');
} else {
    $Photo = UserModel::getDefaultAvatarUrl($User, 'profile');
    $PhotoAlt = t('Default Avatar');
}

if ($User->Banned) {
    $BannedPhoto = c('Garden.BannedPhoto', UserModel::PATH_BANNED_AVATAR);
    if ($BannedPhoto) {
        $Photo = asset($BannedPhoto, true);
    }
}

if ($Photo) : ?>
    <div class="Photo PhotoWrap PhotoWrapLarge widget-dontUseCssOnMe <?php echo val('_CssClass', $User); ?>">
        <?php

        $canEditPhotos = true;
        // If any of the following are false, the user isn't allowed to edit the photo.
        switch (false) {
            // Is the "Allow users to change their own avatars" feature is enabled?
            case Gdn::session()->getPermissions()->hasRanked('Garden.Profile.EditPhotos'):
            // Has the user permissions to edit his/her picture?
            case checkPermission('Garden.ProfilePicture.Edit'):
            // Is the profile we are consulting not-banned (sorry for the double negation)?
            case !$User->Banned:
            // If the profile we are consulting the one of the user in session?
            case Gdn::session()->UserID == $User->UserID:
                $canEditPhotos = false;
                break;
        }

        // If by all criteria, the user CAN EDIT the photo OR has the superseding Garden.Users.Edit permission.
        if ($canEditPhotos || checkPermission('Garden.Users.Edit')) {
            $contents = ($dataDriven ? '<span class="icon icon-camera"></span>' : '').t('Change Icon');
            echo anchor(wrap($contents, "span", ["class" => "ChangePicture-Text"]), '/profile/picture?userid='.$User->UserID, 'ChangePicture Popup', ["aria-label" => t("Change Picture")]);
        }

        echo img($Photo, ['class' => 'ProfilePhotoLarge', 'alt' => $PhotoAlt]);
        ?>
    </div>
<?php elseif ($User->UserID == Gdn::session()->UserID || Gdn::session()->checkPermission('Garden.Users.Edit')) : ?>
    <div class="Photo">
        <?php echo anchor(t('Add a Profile Picture'), '/profile/picture?userid='.$User->UserID, 'AddPicture BigButton'); ?>
    </div>
<?php
endif;
