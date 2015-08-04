<?php if (!defined('APPLICATION')) exit();

function writeActivity($Activity, &$Sender, &$Session) {
    $Activity = (object)$Activity;
    // If this was a status update or a wall comment, don't bother with activity strings
    $ActivityType = explode(' ', $Activity->ActivityType); // Make sure you strip out any extra css classes munged in here
    $ActivityType = $ActivityType[0];
    $Author = UserBuilder($Activity, 'Activity');
    $PhotoAnchor = '';

    if ($Activity->Photo) {
        $PhotoAnchor = anchor(
            img($Activity->Photo, array('class' => 'ProfilePhoto ProfilePhotoMedium')),
            $Activity->PhotoUrl, 'PhotoWrap');
    }

    $CssClass = 'Item Activity Activity-'.$ActivityType;
    if ($PhotoAnchor != '')
        $CssClass .= ' HasPhoto';

    $Format = val('Format', $Activity);

    $Title = '';
    $Excerpt = $Activity->Story;
    if ($Format) {
        $Excerpt = Gdn_Format::to($Excerpt, $Format);
    }

    if ($Activity->NotifyUserID > 0 || !in_array($ActivityType, array('WallComment', 'WallPost', 'AboutUpdate'))) {
        $Title = '<div class="Title">'.GetValue('Headline', $Activity).'</div>';
    } else if ($ActivityType == 'WallPost') {
        $RegardingUser = UserBuilder($Activity, 'Regarding');
        $PhotoAnchor = userPhoto($RegardingUser);
        $Title = '<div class="Title">'
            .UserAnchor($RegardingUser, 'Name')
            .' <span>&rarr;</span> '
            .UserAnchor($Author, 'Name')
            .'</div>';

        if (!$Format)
            $Excerpt = Gdn_Format::Display($Excerpt);
    } else {
        $Title = userAnchor($Author, 'Name');
        if (!$Format)
            $Excerpt = Gdn_Format::Display($Excerpt);
    }
    $Sender->EventArguments['Activity'] = &$Activity;
    $Sender->EventArguments['CssClass'] = &$CssClass;
    $Sender->fireEvent('BeforeActivity');
    ?>
<li id="Activity_<?php echo $Activity->ActivityID; ?>" class="<?php echo $CssClass; ?>">
   <?php
    if (ActivityModel::canDelete($Activity)) {
        echo '<div class="Options">'.anchor('×', 'dashboard/activity/delete/'.$Activity->ActivityID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'Delete').'</div>';
    }
    if ($PhotoAnchor != '') {
        ?>
        <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
    <?php } ?>
   <div class="ItemContent Activity">
      <?php echo $Title; ?>
    <?php echo WrapIf($Excerpt, 'div', array('class' => 'Excerpt')); ?>
    <?php
    $Sender->EventArguments['Activity'] = $Activity;
    $Sender->FireAs('ActivityController')->fireEvent('AfterActivityBody');

    // Reactions stub
    if (in_array(val('ActivityType', $Activity), array('Status', 'WallPost')))
        WriteReactions($Activity);
    ?>
      <div class="Meta">
         <span class="MItem DateCreated"><?php echo Gdn_Format::date($Activity->DateInserted); ?></span>
         <?php
    $SharedString = FALSE;
    $ID = val('SharedNotifyUserID', $Activity->Data);
    if (!$ID)
        $ID = val('CommentNotifyUserID', $Activity->Data);

    if ($ID)
        $SharedString = formatString(t('Comments are between {UserID,you}.'), array('UserID' => array($Activity->NotifyUserID, $ID)));

    $AllowComments = $Activity->NotifyUserID < 0 || $SharedString;


    if ($AllowComments && $Session->checkPermission('Garden.Profiles.Edit'))
        echo '<span class="MItem AddComment">'
            .anchor(t('Activity.Comment', 'Comment'), '#CommentForm_'.$Activity->ActivityID, 'CommentOption');

    if ($SharedString) {
        echo ' <span class="MItem"><i>'.$SharedString.'</i></span>';
    }

    echo '</span>';

    $Sender->fireEvent('AfterMeta');
    ?>
      </div>
   </div>
   <?php
    $Comments = val('Comments', $Activity, array());
    if (count($Comments) > 0) {
        echo '<ul class="DataList ActivityComments">';
        foreach ($Comments as $Comment) {
            WriteActivityComment($Comment, $Activity);
        }
    } else {
        echo '<ul class="DataList ActivityComments Hidden">';
    }

    if ($Session->checkPermission('Garden.Profiles.Edit')):
        ?>
        <li class="CommentForm">
            <?php
            echo anchor(t('Write a comment'), '/dashboard/activity/comment/'.$Activity->ActivityID, 'CommentLink');
            $CommentForm = Gdn::Factory('Form');
            $CommentForm->setModel($Sender->ActivityModel);
            $CommentForm->addHidden('ActivityID', $Activity->ActivityID);
            $CommentForm->addHidden('Return', Gdn_Url::Request());
            echo $CommentForm->open(array('action' => url('/dashboard/activity/comment'), 'class' => 'Hidden'));
            echo '<div class="TextBoxWrapper">'.$CommentForm->textBox('Body', array('MultiLine' => true, 'value' => '')).'</div>';

            echo '<div class="Buttons">';
            echo $CommentForm->button('Comment', array('class' => 'Button Primary'));
            echo '</div>';

            echo $CommentForm->close();
            ?></li>
    <?php
    endif;

    echo '</ul>';
    ?>
</li>
<?php
}

if (!function_exists('WriteActivityComment')):

    function writeActivityComment($Comment, $Activity) {
        $Session = Gdn::session();
        $Author = UserBuilder($Comment, 'Insert');
        $PhotoAnchor = userPhoto($Author, 'Photo');
        $CssClass = 'Item ActivityComment ActivityComment';
        if ($PhotoAnchor != '')
            $CssClass .= ' HasPhoto';

        ?>
        <li id="ActivityComment_<?php echo $Comment['ActivityCommentID']; ?>" class="<?php echo $CssClass; ?>">
            <?php if ($PhotoAnchor != '') { ?>
                <div class="Author Photo"><?php echo $PhotoAnchor; ?></div>
            <?php } ?>
            <div class="ItemContent ActivityComment">
                <?php echo userAnchor($Author, 'Title Name'); ?>
                <div class="Excerpt"><?php echo Gdn_Format::to($Comment['Body'], $Comment['Format']); ?></div>
                <div class="Meta">
                    <span class="DateCreated"><?php echo Gdn_Format::date($Comment['DateInserted'], 'html'); ?></span>
                    <?php
                    if (ActivityModel::canDelete($Activity)) {
                        echo anchor(t('Delete'), "dashboard/activity/deletecomment?id={$Comment['ActivityCommentID']}&tk=".$Session->TransientKey().'&target='.urlencode(Gdn_Url::Request()), 'DeleteComment');
                    }
                    ?>
                </div>
            </div>
        </li>
    <?php
    }

endif;

function writeActivityTabs() {
    $Sender = Gdn::controller();
    $ModPermission = Gdn::session()->checkPermission('Garden.Moderation.Manage');
    $AdminPermission = Gdn::session()->checkPermission('Garden.Settings.Manage');

    if (!$ModPermission && !$AdminPermission)
        return;
    ?>
    <div class="Tabs ActivityTabs">
        <ul>
            <li <?php if ($Sender->data('Filter') == 'public') echo 'class="Active"'; ?>>
                <?php
                echo anchor(t('Public'), '/activity', 'TabLink');
                ?>
            </li>
            <?php
            if ($ModPermission):
                ?>
                <li <?php if ($Sender->data('Filter') == 'mods') echo 'class="Active"'; ?>>
                    <?php
                    echo anchor(t('Moderator'), '/activity/mods', 'TabLink');
                    ?>
                </li>
            <?php
            endif;

            if ($AdminPermission):
                ?>
                <li <?php if ($Sender->data('Filter') == 'admins') echo 'class="Active"'; ?>>
                    <?php
                    echo anchor(t('Admin'), '/activity/admins', 'TabLink');
                    ?>
                </li>
            <?php
            endif;
            ?>
        </ul>
    </div>
<?php
}
