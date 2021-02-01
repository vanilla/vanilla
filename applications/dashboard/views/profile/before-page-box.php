<?php

use Vanilla\Theme\BoxThemeShim;

$Session = Gdn::session();
BoxThemeShim::startHeading();
echo '<h1 class="H">';
    echo htmlspecialchars($this->User->Name);

    echo '<span class="Gloss">';
    Gdn_Theme::bulletRow();
    if ($this->User->Title) {
        echo Gdn_Theme::bulletItem('Title');
        echo ' '.bullet().' '.wrap(htmlspecialchars($this->User->Title), 'span', ['class' => 'User-Title']);
    }

    $this->fireEvent('UsernameMeta');
    echo '</span>';
    echo '</h1>';
    if (BoxThemeShim::isActive()) {
        echo Gdn_Theme::module('ProfileOptionsModule');
    }
BoxThemeShim::endHeading();
if ($this->User->Admin == 2) {
echo '<div class="DismissMessage InfoMessage">', t('This is a system account and does not represent a real person.'), '</div>';
}

if ($this->User->About != '') {
echo '<div id="Status" itemprop="description">'.wrap(Gdn_Format::display($this->User->About));
    if ($this->User->About != '' && ($Session->UserID == $this->User->UserID || $Session->checkPermission('Garden.Users.Edit')))
    echo ' - '.anchor(t('clear'), '/profile/clear/'.$this->User->UserID, 'Hijack');

    echo '</div>';
}

echo Gdn_Theme::module('UserBanModule', ['UserID' => $this->User->UserID]);
