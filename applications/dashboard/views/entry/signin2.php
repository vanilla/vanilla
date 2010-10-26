<?php if (!defined('APPLICATION')) exit();

$Methods = $this->Data('Methods', array());
$SelectedMethod = $this->Data('SelectedMethod', array());

// Testing
$Methods['Facebook'] = array('Label' => 'Facebook', 'Url' => '#', 'ViewLocation' => 'signin');
$Methods['Twitter'] = array('Label' => 'Twitter', 'Url' => '#', 'ViewLocation' => 'signin');


echo '<div class="Border">';
   echo '<div class="Entry">';
      echo Wrap(T('Sign in with any of these methods:'), 'h1');
      
      // Render the buttons to select between the tabs.
      echo '<ul class="Tabs">';
      
      foreach ($Methods as $Key => $Method) {
         $CssClass = 'EntryTabFor_'.$Key.($Key == $SelectedMethod ? ' Active' : '');
         echo Wrap(Anchor($Method['Label'], $Method['Url'], $CssClass), 'li');
      }
      
      echo '</ul>';
      
      // Render the tabs for all of the methods.
      echo '<div class="SignInMethods">';
      foreach ($Methods as $Key => $Method) {
         $Selected = $Key == $SelectedMethod;
         echo '<div class="Method EntryFormFor_'.$Key.($Selected ? '' : ' Hidden').'">';
            echo $this->FetchView($Method['ViewLocation']);
         echo '</div>';
      }
      echo '</div>';
   echo '</div>';
echo '</div>';