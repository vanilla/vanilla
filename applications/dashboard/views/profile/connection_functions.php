<?php if (!defined('APPLICATION')) exit();

function writeConnection($row) {
    $c = Gdn::controller();
    $connected = val('Connected', $row);
    ?>
    <li id="<?php echo "Provider_{$row['ProviderKey']}"; ?>" class="Item">
        <div class="Connection-Header">
         <span class="IconWrap">
            <?php
            echo img(val('Icon', $row, asset('/applications/dashboard/design/images/connection-64.png')));
            ?>
         </span>
         <span class="Connection-Name">
            <?php
            echo val('Name', $row, t('Unknown'));

            if ($connected) {
                echo ' <span class="Gloss Connected">';

                if ($photo = valr('Profile.Photo', $row)) {
                    echo ' '.img($photo, ['class' => 'ProfilePhoto ProfilePhotoSmall']);
                }

                echo ' '.htmlspecialchars(getValueR('Profile.Name', $row)).'</span>';
            }
            ?>
         </span>
         <span class="Connection-Connect">
            <?php
            echo connectButton($row);
            ?>
         </span>
        </div>
        <!--      <div class="Connection-Body">
         <?php

        //         if (debug()) {
        //            decho(val($Row['ProviderKey'], $c->User->Attributes), 'Attributes');
        //         }
        ?>
      </div>-->
    </li>
<?php
}


function connectButton($row) {
    $c = Gdn::controller();

    $connected = val('Connected', $row);
    $cssClass = $connected ? 'Active' : 'InActive';
    $connectUrl = val('ConnectUrl', $row);
    $disconnectUrl = userUrl($c->User, '', 'Disconnect', ['provider' => $row['ProviderKey']]);

    $result = '<span class="ActivateSlider ActivateSlider-'.$cssClass.'">';
    if ($connected) {
        $result .= anchor(t('Connected'), $disconnectUrl, 'Button Primary Hijack ActivateSlider-Button');
    } else {
        $result .= anchor(t('Connect'), $connectUrl, 'Button ActivateSlider-Button', ['target' => '_top']);
    }
    $result .= '</span>';

    return $result;
}
