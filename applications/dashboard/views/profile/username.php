<h1 class="H"><?php
    echo htmlspecialchars($this->User->Name);

    echo '<span class="Gloss">';
    Gdn_Theme::BulletRow();
    if ($this->User->Title)
        echo Gdn_Theme::BulletItem('Title');
    echo ' '.Bullet().' '.Wrap(htmlspecialchars($this->User->Title), 'span', array('class' => 'User-Title'));
    $this->fireEvent('UsernameMeta');
    echo '</span>';
    ?></h1>
