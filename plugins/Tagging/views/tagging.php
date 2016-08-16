<?php if (!defined('APPLICATION')) exit(); ?>

<?php

$TagType = $this->data('_TagType');
$TagTypes = $this->data('_TagTypes');
$CanAddTags = $this->data('_CanAddTags');

Gdn_Theme::assetBegin('Help');
echo '<h2>'.sprintf(t('About %s'), t('Tagging')).'</h2>';
echo t('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.');
Gdn_Theme::assetEnd();
?>
<div class="header-block">
    <h1><?php echo t($this->Data['Title']); ?></h1>
    <div class="buttons">
        <?php echo ' '.anchor('Add Tag', '/settings/tags/add?type='.$TagType, 'js-modal btn btn-primary'); ?>
    </div>
</div>
<div class="toolbar">
    <div class="search-wrap input-wrap toolbar-main">
        <?php
        echo $this->Form->open();
        echo $this->Form->errors();
        echo '<div class="icon-wrap icon-search-wrap">'.dashboardSymbol('search').'</div>';
        echo $this->Form->textBox('Search', ['placeholder' => t('Search for a tag.', 'Search for all or part of a tag.')]);
        echo ' '.$this->Form->close(t('Go'), '', ['class' => 'search-submit']);
        echo '<a class="icon-wrap icon-clear-wrap" href="'.url('/settings/tagging').'">'.dashboardSymbol('close').'</a>';
        echo '<div class="info search-info">'.sprintf(t('%s tag(s) found.'), $this->data('RecordCount')).'</div>';
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
    <table class="Tags">
        <thead>
            <tr>
                <th><?php echo t('Tag') ?></th>
                <th><?php echo t('Type') ?></th>
                <th><?php echo t('Date Added'); ?></th>
                <th><?php echo t('Count'); ?></th>
                <?php if ($CanAddTags) { ?>
                <th><?php echo t('Options'); ?></th>
                <?php } ?>
            </tr>
        </thead>
        <?php
        $Session = Gdn::session();
        $TagCount = $this->data('RecordCount');
        if ($TagCount == 0) {
            echo t("There are no tags in the system yet.");
        } else {
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
                    <div class="media-sm">
                        <div class="media-sm-content">
                        <?php
                        echo '<div class="media-sm-title"><a href="'.url('/discussions/tagged/'.val('Name', $Tag)).'">'.htmlspecialchars($DisplayName).'</a></div>';
                        echo '<div class="media-sm-info">'.sprintf(t('Created by %s'), userAnchor($createdBy)).'</div>';
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
                    <?php
                    if (!$Special) {
                        echo anchor(dashboardSymbol('edit'), "/settings/tags/edit/{$Tag['TagID']}", 'Popup btn btn-icon', ['aria-label' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), "/settings/tags/delete/{$Tag['TagID']}", 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete'), 'data-content' => ['body' => sprintf(t('Are you sure you want to delete this %s?'), t('tag'))]]);
                    }
                    ?>
                    </td>
                    <?php } ?>
                </tr>
            <?php
            }
        }

        ?>


            </tbody>
    </table>
</div>
