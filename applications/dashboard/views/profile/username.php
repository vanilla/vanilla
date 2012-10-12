<h1 class="H"><?php 
   echo htmlspecialchars($this->User->Name);

   echo '<span class="Gloss">';
   if ($this->User->Title)
      echo ' '.Bullet().' '.Wrap(htmlspecialchars($this->User->Title), 'span', array('class' => 'User-Title'));

      $this->FireEvent('UsernameMeta');
   echo '</span>';
?></h1>