<h1 class="H"><?php
    echo htmlspecialchars($this->User->Name);

    echo '<span class="Gloss">';
    Gdn_Theme::bulletRow();
    if ($this->User->Title)
        echo Gdn_Theme::bulletItem('Title');
    echo ' '.bullet().' '.wrap(htmlspecialchars($this->User->Title), 'span', ['class' => 'User-Title']);
    $this->fireEvent('UsernameMeta');
    echo '</span>';
    ?></h1>
