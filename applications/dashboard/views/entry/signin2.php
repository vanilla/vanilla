<?php if (!defined('APPLICATION')) exit();

$Methods = $this->Data('Methods', array());
$SelectedMethod = $this->Data('SelectedMethod', array());
$CssClass = count($Methods) > 0 ? ' MultipleEntryMethods' : ' SingleEntryMethod';

// Testing
//$Methods['Facebook'] = array('Label' => 'Facebook', 'Url' => '#', 'ViewLocation' => 'signin');
//$Methods['Twitter'] = array('Label' => 'Twitter', 'Url' => '#', 'ViewLocation' => 'signin');

echo '<div class="Entry'.$CssClass.'">';

   // Render the main signin form.
   echo '<div class="MainForm">';
//      $CurrentView = $this->Data['CurrentView'];
//      $CurrentViewLocation = call_user_func_array(array($this, 'FetchViewLocation'), (array)$CurrentView);
//      include $CurrentViewLocation;
   echo $this->Data('MainForm');

   echo '</div>';

   // Render the buttons to select other methods of signing in.
   if (count($Methods) > 0) {
      echo '<div class="Methods">'
         .Wrap(T('Or you can...'), 'div');

      foreach ($Methods as $Key => $Method) {
         $CssClass = 'Method Method_'.$Key;
         echo '<div class="'.$CssClass.'">',
            $Method['SignInHtml'],
            '</div>';
      }

      echo '</div>';
   }

echo '</div>';
