<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
?>
<div class="header-block">
    <h1><?php echo t('Manage Routes'); ?></h1>
    <div class="btn-group"><?php echo anchor(t('Add Route'), 'dashboard/routes/add', 'AddRoute btn btn-primary'); ?></div>
</div>
<?php
    Gdn_Theme::assetBegin('Help');
    echo '<h2>'.sprintf(t('About %s'), t('Routes')).'</h2>';
    echo t('Routes are used to redirect users.', 'Routes are used to redirect users depending on the URL requested.');
    echo anchor(t('Learn about custom routing.', 'Learn about custom routing.'), 'http://docs.vanillaforums.com/developers/routes');
    Gdn_Theme::assetEnd();
?>

<div class="table-wrap">
    <table class="AltColumns" id="RouteTable">
        <thead>
        <tr>
            <th><?php echo t('Route'); ?></th>
            <th><?php echo t('Target'); ?></th>
            <th><?php echo t('Type'); ?></th>
            <th class="options"><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 0;
        $Alt = false;
        foreach ($this->MyRoutes as $Route => $RouteData) {
            $Alt = !$Alt;

            $Target = $RouteData['Destination'];
            $RouteType = t(Gdn::router()->RouteTypes[$RouteData['Type']]);
            $Reserved = $RouteData['Reserved'];
            ?>
            <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
                <td class="Info">
                    <strong><?php echo $Route; ?></strong>
                </td>
                <td class="Alt"><?php echo $Target; ?></td>
                <td class="Alt"><?php echo $RouteType; ?></td>
                <td>
                    <div class="btn-group">
                    <?php
                    echo anchor(dashboardSymbol('edit'), '/dashboard/routes/edit/'.trim($RouteData['Key'], '='), 'EditRoute btn btn-icon', ['aria-label' => t('Edit')]);
                    if (!$Reserved) {
                        echo anchor(dashboardSymbol('delete'), '/routes/delete/'.trim($RouteData['Key'].'=').'/'.$Session->TransientKey(), 'DeleteRoute btn btn-delete', ['aria-label' => t('Delete')]);
                    }
                    ?>
                    </div>
                </td>
            </tr>
            <?php
            ++$i;
        }
        ?>
        </tbody>
    </table>
</div>
