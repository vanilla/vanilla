<?php if (!defined('APPLICATION')) exit();
$this->RenderAsset('Messages');
?>
<div class="Column Column2">
    <h1><?php echo t('Recently Active Users'); ?></h1>
    <table id="RecentUsers" border="0" cellpadding="0" cellspacing="0" class="AltColumns">
        <!--
      <thead>
         <tr>
            <th><?php echo t('User'); ?></th>
            <th class="Alt"><?php echo t('Last Active'); ?></th>
         </tr>
      </thead>
      -->
        <tbody>
        <?php
        $Alt = '';
        foreach ($this->ActiveUserData as $User) {
            ?>
            <tr<?php
            $Alt = $Alt == '' ? ' class="Alt"' : '';
            echo $Alt;
            ?>>
                <th><?php
                    $PhotoUser = UserBuilder($User);
                    echo userPhoto($PhotoUser);
                    echo userAnchor($User);
                    ?></th>
                <td class="Alt"><?php echo Gdn_Format::date($User->DateLastActive, 'html'); ?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
</div>
<div class="Column Column1 ReleasesColumn">
    <h1><?php echo t('Updates'); ?></h1>

    <div class="List"></div>
</div>
<div class="Column Column2 NewsColumn">
    <h1><?php echo t('Recent News'); ?></h1>

    <div class="List"></div>
</div>
