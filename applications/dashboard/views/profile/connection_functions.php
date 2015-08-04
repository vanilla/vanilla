<?php if (!defined('APPLICATION')) exit();

function writeConnection($Row) {
    $c = Gdn::controller();
    $Connected = val('Connected', $Row);
    ?>
    <li id="<?php echo "Provider_{$Row['ProviderKey']}"; ?>" class="Item">
        <div class="Connection-Header">
         <span class="IconWrap">
            <?php
            echo img(val('Icon', $Row, Asset('/applications/dashboard/design/images/connection-64.png')));
            ?>
         </span>
         <span class="Connection-Name">
            <?php
            echo val('Name', $Row, t('Unknown'));

            if ($Connected) {
                echo ' <span class="Gloss Connected">';

                if ($Photo = valr('Profile.Photo', $Row)) {
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
        //            decho(val($Row['ProviderKey'], $c->User->Attributes), 'Attributes');
        //         }
        ?>
      </div>-->
    </li>
<?php
}


function connectButton($Row) {
    $c = Gdn::controller();

    $Connected = val('Connected', $Row);
    $CssClass = $Connected ? 'Active' : 'InActive';
    $ConnectUrl = val('ConnectUrl', $Row);
    $DisconnectUrl = userUrl($c->User, '', 'Disconnect', array('provider' => $Row['ProviderKey']));

    $Result = '<span class="ActivateSlider ActivateSlider-'.$CssClass.'">';
    if ($Connected) {
        $Result .= anchor(t('Connected'), $DisconnectUrl, 'Button Primary Hijack');
    } else {
        $Result .= anchor(t('Connect'), $ConnectUrl, 'Button', array('target' => '_top'));
    }
    $Result .= '</span>';

    return $Result;
}
