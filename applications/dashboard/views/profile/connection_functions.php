<?php if (!defined('APPLICATION')) exit();

function WriteConnection($Row) {
   $c = Gdn::Controller();
   $Connected = GetValue('Connected', $Row);
?>
   <li id="<?php echo "Provider_{$Row['ProviderKey']}"; ?>" class="Item">
      <div class="Connection-Header">
         <span class="IconWrap">
            <?php
               echo Img(GetValue('Icon', $Row, Asset('/applications/dashboard/design/images/connection-64.png')));
            ?>
         </span>
         <span class="Connection-Name">
            <?php
               echo GetValue('Name', $Row, T('Unknown'));
               
               if ($Connected) {
                  echo ' <span class="Gloss Connected">';
                  
                  if ($Photo = GetValueR('Profile.Photo', $Row)) {
                     echo ' '.Img($Photo, array('class' => 'ProfilePhoto ProfilePhotoSmall'));
                  }
                  
                  echo ' '.htmlspecialchars(GetValueR('Profile.Name', $Row)).'</span>';
               }
            ?>
         </span>
         <span class="Connection-Connect">
            <?php
            echo ConnectButton($Row);
            ?>
         </span>
      </div>
<!--      <div class="Connection-Body">
         <?php
         
//         if (Debug()) {
//            decho(GetValue($Row['ProviderKey'], $c->User->Attributes), 'Attributes');
//         }
         ?>
      </div>-->
   </li>
<?php
}


function ConnectButton($Row) {
   $c = Gdn::Controller();
   
   $Connected = GetValue('Connected', $Row);
   $CssClass = $Connected ? 'Active' : 'InActive';
   $ConnectUrl = GetValue('ConnectUrl', $Row);
   $DisconnectUrl = UserUrl($c->User, '', 'Disconnect', array('provider' => $Row['ProviderKey']));

   $Result = '<span class="ActivateSlider ActivateSlider-'.$CssClass.'">';
   if ($Connected) {
      $Result .= Anchor(T('Connected'), $DisconnectUrl, 'Button Primary Hijack');
   } else {
      $Result .= Anchor(T('Connect'), $ConnectUrl, 'Button');
   }
   $Result .= '</span>';
   
   return $Result;
}
