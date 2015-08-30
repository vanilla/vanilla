<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div class="User" itemscope itemtype="http://schema.org/Person">
    <h1 class="H"><?php
        echo htmlspecialchars($this->User->Name);

        echo '<span class="Gloss">';
        Gdn_Theme::BulletRow();
        if ($this->User->Title) {
            echo Gdn_Theme::BulletItem('Title');
            echo ' '.Bullet().' '.Wrap(htmlspecialchars($this->User->Title), 'span', array('class' => 'User-Title'));
        }

        $this->fireEvent('UsernameMeta');
        echo '</span>';
        ?></h1>
    <?php
    if ($this->User->Admin == 2) {
        echo '<div class="DismissMessage InfoMessage">', t('This is a system account and does not represent a real person.'), '</div>';
    }

    if ($this->User->About != '') {
        echo '<div id="Status" itemprop="description">'.Wrap(Gdn_Format::Display($this->User->About));
        if ($this->User->About != '' && ($Session->UserID == $this->User->UserID || $Session->checkPermission('Garden.Users.Edit')))
            echo ' - '.anchor(t('clear'), '/profile/clear/'.$this->User->UserID.'/'.$Session->TransientKey(), 'Hijack');

        echo '</div>';
    }

    echo Gdn_Theme::Module('UserBanModule', array('UserID' => $this->User->UserID));

    $this->fireEvent('BeforeUserInfo');
    echo Gdn_Theme::Module('UserInfoModule');
    $this->fireEvent('AfterUserInfo');
    ?>
</div>
