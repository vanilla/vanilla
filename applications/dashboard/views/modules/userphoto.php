<?php if (!defined('APPLICATION')) exit();
$User = val('User', Gdn::controller());
if (!$User && Gdn::session()->isValid()) {
    $User = Gdn::session()->User;
}

if (!$User)
    return;

$Photo = $User->Photo;
if ($Photo) {
    if (!IsUrl($Photo)) {
        $Photo = Gdn_Upload::url(changeBasename($Photo, 'p%s'));
    }
} else {
    $Photo = UserModel::getDefaultAvatarUrl($User, 'profile');
}

if ($User->Banned) {
    $BannedPhoto = c('Garden.BannedPhoto', 'https://c3409409.ssl.cf0.rackcdn.com/images/banned_large.png');
    if ($BannedPhoto)
        $Photo = Gdn_Upload::url($BannedPhoto);
}

if ($Photo) {
    ?>
    <div class="Photo PhotoWrap PhotoWrapLarge <?php echo val('_CssClass', $User); ?>">
        <?php
        $Img = img($Photo, array('class' => 'ProfilePhotoLarge'));
        $canEditPhotos = Gdn::session()->checkRankedPermission(c('Garden.Profile.EditPhotos', true)) || Gdn::session()->checkPermission('Garden.Users.Edit');
        if (!$User->Banned && $canEditPhotos && (Gdn::session()->UserID == $User->UserID || Gdn::session()->checkPermission('Garden.Users.Edit')))
            echo anchor(Wrap(t('Change Picture')), '/profile/picture?userid='.$User->UserID, 'ChangePicture');

        echo $Img;
        ?>
    </div>
<?php } else if ($User->UserID == Gdn::session()->UserID || Gdn::session()->checkPermission('Garden.Users.Edit')) { ?>
    <div
        class="Photo"><?php echo anchor(t('Add a Profile Picture'), '/profile/picture?userid='.$User->UserID, 'AddPicture BigButton'); ?></div>
<?php
}
