<?php if (!defined('APPLICATION')) exit(); ?>

<?php

$TagType = $this->data('_TagType');
$TagTypes = $this->data('_TagTypes');
$CanAddTags = $this->data('_CanAddTags');

$desc = t('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.');
helpAsset(sprintf(t('About %s'), t('Tagging')), $desc);

if (strtolower($TagType) == 'all' || strtolower($TagType) == 'tags') {
    // Only show add button if filter type supports adding new tags.
    echo heading(t($this->data('Title')), t('Add Tag'), '/settings/tags/add?type=Tag', 'js-modal btn btn-primary');
} else {
    echo heading(t($this->data('Title')));
}
?>
<div class="toolbar flex-wrap">
    <div class="search-wrap input-wrap toolbar-main">
        <?php
        $info = sprintf(t('%s tag(s) found.'), $this->data('RecordCount'));
        $placeholder = t('Search for a tag.', 'Search for all or part of a tag.');
        echo $this->Form->searchForm('search', '/settings/tagging', ['placeholder' => $placeholder], $info);
        ?>
    </div>

    <div class="btn-group">

        <?php foreach ($TagTypes as $TagTypeName => $TagMeta): ?>

            <?php

            $TagName = ($TagMeta['key'] == '' || strtolower($TagMeta['key']) == 'tags')
                ? 'Tags'
                : $TagTypeName;

            $TagName = (!empty($TagMeta['plural']))
                ? $TagMeta['plural']
                : $TagName;

            if ($TagMeta['key'] == '') {
                $TagMeta['key'] = (!empty($TagMeta['plural']))
                    ? $TagMeta['plural']
                    : $TagMeta['key'];
            }

            $CurrentTab = '';
            if (strtolower($TagType) == strtolower($TagMeta['key'])
                || (!empty($TagMeta['plural']) && strtolower($TagType) == strtolower($TagMeta['plural']))
            ) {
                $CurrentTab = 'active';
            }

            $TabUrl = url('/settings/tagging/?type='.strtolower($TagMeta['key']));

            ?>

            <a href="<?php echo $TabUrl; ?>" class="<?php echo $CurrentTab; ?> btn btn-secondary">
                <?php echo ucwords(strtolower($TagName)); ?>
            </a>

        <?php endforeach; ?>

    </div>
    <?php PagerModule::write(array('Sender' => $this, 'View' => 'pager-dashboard')); ?>
</div>
<div class="table-wrap">
    <table class="Tags table-data js-tj">
        <thead>
            <tr>
                <th class="column-md"><?php echo t('Tag') ?></th>
                <th><?php echo t('Type') ?></th>
                <th class="column-md"><?php echo t('Date Added'); ?></th>
                <th class="column-xs"><?php echo t('Count'); ?></th>
                <?php if ($CanAddTags) { ?>
                <th class="column-sm"></th>
                <?php } ?>
            </tr>
        </thead>
        <?php
        $Session = Gdn::session();
        $TagCount = $this->data('RecordCount');
            $Tags = $this->data('Tags'); ?>
            <tbody>
            <?php
            foreach ($Tags as $Tag) {
                $CssClass = 'TagAdmin';
                $Title = '';
                $Special = FALSE;
                $type = val('Type', $Tag);
                if (empty($type)) {
                    $type = t('Tag');
                }
                $userModel = new UserModel();
                $createdBy = $userModel->getID(val('InsertUserID', $Tag));
                $dateInserted = Gdn_Format::date(val('DateInserted', $Tag), '%e %b %Y');
                $count = val('CountDiscussions', $Tag, 0);

                if (val('Type', $Tag)) {
                    $Special = TRUE;
                    $CssClass .= " Tag-Special Tag-{$Tag['Type']}";
                    $Title = t('This is a special tag.');
                }

                ?>
                <tr id="<?php echo "Tag_{$Tag['TagID']}"; ?>" class="<?php echo $CssClass; ?>"
                     title="<?php echo $Title; ?>">
                    <td>
                    <?php
                    $DisplayName = TagFullName($Tag); ?>
                    <div class="media media-sm">
                        <div class="media-body">
                        <?php
                        echo '<div class="media-title"><a href="'.url('/discussions/tagged/'.val('Name', $Tag)).'">'.htmlspecialchars($DisplayName).'</a></div>';
                        echo '<div class="info">'.sprintf(t('Created by %s'), userAnchor($createdBy)).'</div>';
                        ?>
                        </div>
                    </div>
                    </td>
                    <td class="type">
                        <?php echo $type; ?>
                    </td>
                    <td class="date">
                        <?php echo $dateInserted; ?>
                    </td>
                    <td class="count">
                        <?php echo $count; ?>
                    </td>
                    <?php if ($CanAddTags) { ?>
                    <td class="options">
                        <div class="btn-group">
                        <?php
                        if (!$Special) {
                            echo anchor(dashboardSymbol('edit'), "/settings/tags/edit/{$Tag['TagID']}", 'js-modal btn btn-icon', ['aria-label' => t('Edit'), 'title' => t('Edit')]);
                            echo anchor(dashboardSymbol('delete'), "/settings/tags/delete/{$Tag['TagID']}", 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'title' => t('Delete'), 'data-body' => sprintf(t('Are you sure you want to delete this %s?'), t('tag'))]);
                        }
                        ?>
                        </div>
                    </td>
                    <?php } ?>
                </tr>
            <?php
        }

        ?>


            </tbody>
    </table>
</div>
