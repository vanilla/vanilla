<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();

BoxThemeShim::inactiveHtml('<div class="DataListWrap">');
BoxThemeShim::startHeading();
echo '<h2 class="H">'.t('Activity').'</h2>';
BoxThemeShim::endHeading();

BoxThemeShim::startBox();
$Session = Gdn::session();
if ($Session->isValid() && checkPermission('Garden.Profiles.Edit')) {
    $this->fireEvent('BeforeStatusForm');
    $ButtonText = $Session->UserID == $this->User->UserID ? 'Share' : 'Add Comment';


    echo '<div class="FormWrapper FormWrapper-Condensed">';
    echo $this->Form->open(['action' => url("/activity/post/{$this->User->UserID}?Target=".urlencode(userUrl($this->User))), 'class' => 'Activity']);
    echo $this->Form->errors();
    echo $this->Form->bodyBox('Comment', ['Wrap' => true, 'ImageUpload' => true]);
    echo '<div class="Buttons">';
    echo $this->Form->button($ButtonText, ['class' => 'Button Primary']);
    echo '</div>';
    echo $this->Form->close();
    echo '</div>';
}


// We already have our own box. We don't want activity applying another one.
$this->setData('activityBoxIsSet', true);

// Include the activities
include($this->fetchViewLocation('index', 'activity', 'dashboard'));
BoxThemeShim::endBox();
BoxThemeShim::inactiveHtml('</div>');
