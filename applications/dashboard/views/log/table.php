<?php if (!defined('APPLICATION')) exit();
include $this->fetchViewLocation('helper_functions');
?>
    <div class="table-wrap">
        <table id="Log" class="table-data table-data-content js-tj">
            <thead>
            <tr>
                <th class="column-checkbox" data-tj-ignore="true"><input id="SelectAll" type="checkbox"/></th>
                <th class="content-cell column-full content-cell-responsive" data-tj-main="true"><?php echo t('Record Content', 'Content') ?></th>
                <th class="UsernameCell column-lg username-cell-responsive"><?php echo $this->data('_flaggedByTitle', t('Flagged By', 'Flagged By')); ?></th>
                <th class="options column-checkbox"></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($this->data('Log') as $row):
                $recordLabel = valr('Data.Type', $row);
                if (!$recordLabel || $recordLabel === 'Post') {
                    $recordLabel = $row['RecordType'];
                }
                $recordLabel = Gdn_Form::labelCode(mb_convert_case($recordLabel, MB_CASE_TITLE));
                $user = Gdn::userModel()->getID($row['InsertUserID'] ?? 0);
                $viewPersonalInfo = gdn::session()->checkPermission('Garden.PersonalInfo.View');

                $userBlock = new MediaItemModule(val('Name', $user), userUrl($user));
                $userBlock->setView('media-sm')
                    ->setImage(userPhotoUrl($user))
                    ->addMeta(Gdn_Format::dateFull($row['DateInserted'], 'html'));

                $Url = FALSE;
                if (in_array($row['Operation'], ['Edit', 'Moderate'])) {
                    switch (strtolower($row['RecordType'])) {
                        case 'discussion':
                            $Url = "/discussion/{$row['RecordID']}/x/p1";
                            break;
                        case 'comment':
                            $Url = "/discussion/comment/{$row['RecordID']}#Comment_{$row['RecordID']}";
                    }
                } elseif ($row['Operation'] === 'Delete') {
                    switch (strtolower($row['RecordType'])) {
                        case 'comment':
                            $Url = "/log/filter?recordType=comment&recordID={$row['RecordID']}";
                    }
                }

                ?>
                <tr id="<?php echo "LogID_{$row['LogID']}"; ?>">
                    <td class="column-checkbox"><input type="checkbox" name="LogID[]" value="<?php echo $row['LogID']; ?>"/>
                    </td>
                    <td class="content-cell">
                        <?php
                        $recordUser = Gdn::userModel()->getID($row['Data']['InsertUserID'] ?? $row['RecordUserID'], DATASET_TYPE_ARRAY);
                        if ($row['RecordName']) {
                            $authorBlock = new MediaItemModule(val('Name', $recordUser), userUrl($recordUser));
                            $authorBlock->setView('media-sm')
                                ->setImage(userPhotoUrl($recordUser))
                                ->addTitleMetaIf((bool)$recordUser['Banned'], wrap(t('Banned'), 'span', ['class' => 'text-danger']))
                                ->addTitleMeta(plural($recordUser['CountDiscussions'] + $recordUser['CountComments'], '%s post', '%s posts'))

                                ->addMeta(Gdn_Format::dateFull($row['Data']['DateInserted'] ?? $row['RecordDate'], 'html'))
                                ->addMeta('<b>'.htmlspecialchars(t($recordLabel, $recordLabel)).'</b>')
                                ->addMetaIf(($viewPersonalInfo && !empty($row['Data']['InsertIPAddress'])), iPAnchor($row['Data']['InsertIPAddress']));

                            echo $authorBlock;
                        }

                        echo '<div class="post-content js-collapsable" data-className="userContent">', $this->formatContent($row), '</div>';

                        // Write the other record counts.

                        echo otherRecordsMeta($row['Data']);

                        echo '<div class="Meta-Container">';
                        if ($row['CountGroup'] > 1) {
                            echo ' <span class="info">',
                                '<span class="Meta-Label">'.t('Reported').'</span> ',
                            wrap(plural($row['CountGroup'], '%s time', '%s times'), 'span', 'Meta-Value'),
                            '</span> ';
                        }

                        // Write custom meta information.
                        $CustomMeta = valr('Data._Meta', $row, false);
                        if (is_array($CustomMeta)) {
                            foreach ($CustomMeta as $Key => $Value) {
                                echo ' <span class="Meta">',
                                    '<span class="Meta-Label">'.t($Key).'</span> ',
                                wrap(Gdn_Format::html($Value), 'span', ['class' => 'Meta-Value']),
                                '</span>';
                            }
                        }
                        echo '</div>';
                        ?>
                    </td>
                    <td class="UsernameCell">
                        <?php echo $userBlock; ?>
                    </td>
                    <td class="options column-checkbox">
                        <?php
                        if ($Url) {
                            $attr = ['title' => t('View Post'), 'aria-label' => t('View Post'), 'class' => 'btn btn-icon btn-icon-sm'];
                            echo anchor(dashboardSymbol('external-link', 'icon icon-text'), $Url, '', $attr);
                        }
                        ?>
                    </td>
                </tr>
            <?php
            endforeach;
            ?>
            </tbody>
        </table>
    </div>
<?php
