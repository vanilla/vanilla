<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<h1><?php echo t('Manage Routes'); ?></h1>
<div class="Info"><?php
    echo t('Routes can be used to redirect users to various parts of your site depending on the url.'),
    ' ',
    anchor(t('Get more information on creating custom routes'), 'http://vanillaforums.org/page/routes');
    ?></div>
<div class="FilterMenu"><?php echo anchor(t('Add Route'), 'dashboard/routes/add', 'AddRoute SmallButton'); ?></div>
<table class="AltColumns" id="RouteTable">
    <thead>
    <tr>
        <th><?php echo t('Route'); ?></th>
        <th class="Alt"><?php echo t('Target'); ?></th>
        <th class="Alt"><?php echo t('Type'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    $Alt = FALSE;
    foreach ($this->MyRoutes as $Route => $RouteData) {
        $Alt = !$Alt;

        $Target = $RouteData['Destination'];
        $RouteType = t(Gdn::router()->RouteTypes[$RouteData['Type']]);
        $Reserved = $RouteData['Reserved'];
        ?>
        <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
            <td class="Info">
                <strong><?php echo $Route; ?></strong>

                <div>
                    <?php
                    echo anchor(t('Edit'), '/dashboard/routes/edit/'.trim($RouteData['Key'], '='), 'EditRoute SmallButton');
                    if (!$Reserved)
                        echo anchor(t('Delete'), '/routes/delete/'.trim($RouteData['Key'].'=').'/'.$Session->TransientKey(), 'DeleteRoute SmallButton');

                    ?>
                </div>
            </td>
            <td class="Alt"><?php echo $Target; ?></td>
            <td class="Alt"><?php echo $RouteType; ?></td>
        </tr>
        <?php
        ++$i;
    }
    ?>
    </tbody>
</table>
