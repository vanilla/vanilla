<?php if (!defined('APPLICATION')) exit();
$Methods = $this->Data('Methods', array());
$SelectedMethod = $this->Data('SelectedMethod', array());
$CssClass = count($Methods) > 0 ? ' MultipleEntryMethods' : ' SingleEntryMethod';

// Testing
//$Methods['Facebook'] = array('Label' => 'Facebook', 'Url' => '#', 'ViewLocation' => 'signin');
//$Methods['Twitter'] = array('Label' => 'Twitter', 'Url' => '#', 'ViewLocation' => 'signin');

echo '<div class="Entry'.$CssClass.'">';
   echo '<h1>'.$this->Data('Title').'</h1>';

   // Render the main signin form.
   echo '<div class="MainForm">';

   include $this->FetchViewLocation('passwordform');

   echo $this->Data('MainForm');

   echo '</div>';

   // Render the buttons to select other methods of signing in.
   if (count($Methods) > 0) {
      echo '<div class="Methods">'
         .Wrap('<b>'.T('Or you can...').'</b>', 'div');

      foreach ($Methods as $Key => $Method) {
         $CssClass = 'Method Method_'.$Key;
         echo '<div class="'.$CssClass.'">',
            $Method['SignInHtml'],
            '</div>';
      }

      echo '</div>';
   }

echo '</div>';
