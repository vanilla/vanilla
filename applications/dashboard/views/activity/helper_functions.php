<?php if (!defined('APPLICATION')) exit();

function writeActivity($activity, $sender, $session) {
    $activity = (object)$activity;
    // If this was a status update or a wall comment, don't bother with activity strings
    $activityType = explode(' ', $activity->ActivityType); // Make sure you strip out any extra css classes munged in here
    $activityType = $activityType[0];
    $author = userBuilder($activity, 'Activity');
    $photoAnchor = '';

    if ($activity->Photo) {
        $photoAnchor = anchor(
            img($activity->Photo, ['class' => 'ProfilePhoto ProfilePhotoMedium', 'aria-hidden' => 'true']),
            $activity->PhotoUrl, 'PhotoWrap');
    }

    $cssClass = 'Item Activity Activity-'.$activityType;
    if ($photoAnchor != '')
        $cssClass .= ' HasPhoto';

    $format = val('Format', $activity);
    if (!$format) {
        $format = 'html';
    }

    $title = '';
    $excerpt = Gdn_Format::to($activity->Story, $format);

    if ($activity->NotifyUserID > 0 || !in_array($activityType, ['WallComment', 'WallPost', 'AboutUpdate'])) {
        $title = '<div class="Title" role="heading" aria-level="3">'.Gdn_Format::to(val('Headline', $activity), 'html').'</div>';
    } else if ($activityType == 'WallPost') {
        $regardingUser = userBuilder($activity, 'Regarding');
        $photoAnchor = userPhoto($regardingUser);
        $title = '<div class="Title">'
            .userAnchor($regardingUser, 'Name')
            .' <span>&rarr;</span> '
            .userAnchor($author, 'Name')
            .'</div>';

        if (!$format)
            $excerpt = Gdn_Format::display($excerpt);
    } else {
        $title = userAnchor($author, 'Name');
        if (!$format)
            $excerpt = Gdn_Format::display($excerpt);
    }
    $sender->EventArguments['Activity'] = &$activity;
    $sender->EventArguments['CssClass'] = &$cssClass;
    $sender->fireEvent('BeforeActivity');
    ?>
<li id="Activity_<?php echo $activity->ActivityID; ?>" class="<?php echo $cssClass; ?>">
   <?php
    if (ActivityModel::canDelete($activity)) {
        echo '<div class="Options">'.anchor('&times;', 'dashboard/activity/delete/'.$activity->ActivityID.'/'.$session->transientKey().'?Target='.urlencode($sender->SelfUrl), 'Delete').'</div>';
    }
    if ($photoAnchor != '') {
        ?>
        <div class="Author Photo"><?php echo $photoAnchor; ?></div>
    <?php } ?>
   <div class="ItemContent Activity">
      <?php echo $title; ?>
    <?php echo wrapIf($excerpt, 'div', ['class' => 'Excerpt userContent']); ?>
    <?php
    $sender->EventArguments['Activity'] = $activity;
    $sender->fireAs('ActivityController')->fireEvent('AfterActivityBody');

    // Reactions stub
    if (in_array(val('ActivityType', $activity), ['Status', 'WallPost']))
        writeReactions($activity);
    ?>
      <div class="Meta">
         <span class="MItem DateCreated"><?php echo Gdn_Format::date($activity->DateInserted); ?></span>
         <?php
    $sharedString = FALSE;
    $iD = val('SharedNotifyUserID', $activity->Data);
    if (!$iD)
        $iD = val('CommentNotifyUserID', $activity->Data);

    if ($iD)
        $sharedString = formatString(t('Comments are between {UserID,you}.'), ['UserID' => [$activity->NotifyUserID, $iD]]);

    $allowComments = $activity->NotifyUserID < 0 || $sharedString;


    if ($allowComments && $session->checkPermission('Garden.Profiles.Edit')) {
        echo '<span class="MItem AddComment">'
            .anchor(t('Activity.Comment', 'Comment'), '#CommentForm_'.$activity->ActivityID, 'CommentOption')
            .'</span>';
    }

    if ($sharedString) {
        echo ' <span class="MItem"><i>'.$sharedString.'</i></span>';
    }

    $sender->fireEvent('AfterMeta');
    ?>
      </div>
   </div>
   <?php
    $comments = val('Comments', $activity, []);
    if (count($comments) > 0) {
        echo '<ul class="DataList ActivityComments">';
        foreach ($comments as $comment) {
            writeActivityComment($comment, $activity);
        }
    } else {
        echo '<ul class="DataList ActivityComments Hidden">';
    }

    if ($session->checkPermission('Garden.Profiles.Edit')):
        ?>
        <li class="CommentForm">
            <?php
            echo anchor(t('Write a comment'), '/dashboard/activity/comment/'.$activity->ActivityID, 'CommentLink');
            $commentForm = Gdn::factory('Form');
            $commentForm->setModel($sender->ActivityModel);
            $commentForm->addHidden('ActivityID', $activity->ActivityID);
            $commentForm->addHidden('Return', Gdn_Url::request());
            echo $commentForm->open(['action' => url('/dashboard/activity/comment'), 'class' => 'Hidden']);
            echo '<div class="TextBoxWrapper">'.$commentForm->textBox('Body', ['MultiLine' => true, 'value' => '']).'</div>';

            echo '<div class="Buttons">';
            echo $commentForm->button('Comment', ['class' => 'Button Primary']);
            echo '</div>';

            echo $commentForm->close();
            ?></li>
    <?php
    endif;

    echo '</ul>';
    ?>
</li>
<?php
}

if (!function_exists('WriteActivityComment')):

    function writeActivityComment($comment, $activity) {
        $session = Gdn::session();
        $author = userBuilder($comment, 'Insert');
        $photoAnchor = userPhoto($author, 'Photo');
        $cssClass = 'Item ActivityComment ActivityComment';
        if ($photoAnchor != '')
            $cssClass .= ' HasPhoto';

        ?>
        <li id="ActivityComment_<?php echo $comment['ActivityCommentID']; ?>" class="<?php echo $cssClass; ?>">
            <?php if ($photoAnchor != '') { ?>
                <div class="Author Photo"><?php echo $photoAnchor; ?></div>
            <?php } ?>
            <div class="ItemContent ActivityComment">
                <?php echo userAnchor($author, 'Title Name'); ?>
                <div class="Excerpt"><?php echo Gdn_Format::to($comment['Body'], $comment['Format']); ?></div>
                <div class="Meta">
                    <span class="DateCreated"><?php echo Gdn_Format::date($comment['DateInserted'], 'html'); ?></span>
                    <?php
                    if (ActivityModel::canDelete($activity)) {
                        echo anchor(t('Delete'), "dashboard/activity/deletecomment?id={$comment['ActivityCommentID']}&tk=".$session->transientKey().'&target='.urlencode(Gdn_Url::request()), 'DeleteComment');
                    }
                    ?>
                </div>
            </div>
        </li>
    <?php
    }

endif;

function writeActivityTabs() {
    $sender = Gdn::controller();
    $modPermission = Gdn::session()->checkPermission('Garden.Moderation.Manage');
    $adminPermission = Gdn::session()->checkPermission('Garden.Settings.Manage');

    if (!$modPermission && !$adminPermission)
        return;
    ?>
    <div class="Tabs ActivityTabs">
        <ul>
            <li <?php if ($sender->data('Filter') == 'public') echo 'class="Active"'; ?>>
                <?php
                echo anchor(t('Public'), '/activity', 'TabLink');
                ?>
            </li>
            <?php
            if ($modPermission):
                ?>
                <li <?php if ($sender->data('Filter') == 'mods') echo 'class="Active"'; ?>>
                    <?php
                    echo anchor(t('Moderator'), '/activity/mods', 'TabLink');
                    ?>
                </li>
            <?php
            endif;

            if ($adminPermission):
                ?>
                <li <?php if ($sender->data('Filter') == 'admins') echo 'class="Active"'; ?>>
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
