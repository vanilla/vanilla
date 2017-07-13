<?php if (!defined('APPLICATION')) exit(); ?>
<?php
$tagType = $this->data('_TagType'); // The tag page we're on.
$tagTypes = $this->data('_TagTypes');

/** @var Gdn_Form $form */
$form = $this->Form;

$desc = t('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.');
helpAsset(sprintf(t('About %s'), t('Tagging')), $desc);

if (strtolower($tagType) == 'all' || strtolower($tagType) == 'tags') {
    // Only show add button if filter type supports adding new tags.
    echo heading(t($this->data('Title')), t('Add Tag'), '/settings/tags/add?type=Tag', 'js-modal btn btn-primary');
} else {
    echo heading(t($this->data('Title')));
}

$enabled = c('Tagging.Discussions.Enabled');
?>
<div class="form-group">
    <div class="label-wrap-wide">
        <?php echo '<div class="label">'.t('Enable Tagging').'</div>'; ?>
        <div class="info"><?php echo t('Tagging allows users to add a tag to discussions they start in order to make them more discoverable. '); ?></div>
    </div>
    <div class="input-wrap-right">
        <span id="enable-tagging-toggle">
            <?php
            if ($enabled) {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/enabletagging/false', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
            } else {
                echo wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/dashboard/settings/enabletagging/true', 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
            }
            ?>
        </span>
    </div>
</div>
<div class="tagging-settings js-foggy" <?php echo $enabled ? 'data-is-foggy="false"' : 'data-is-foggy="true"'; ?>>
    <?php
    $tagTypesDropdown = new DropdownModule('', '', 'dropdown-filter');
    $tagTypesDropdown->setView('dropdown-twbs');

    foreach ($tagTypes as $tagTypeName => $tagMeta) {
        $tagName = ($tagMeta['key'] == '' || strtolower($tagMeta['key']) == 'tags') ? 'Tags' : $tagTypeName;
        $tagName = (!empty($tagMeta['plural'])) ? $tagMeta['plural'] : $tagName;

        if ($tagMeta['key'] == '') {
            $tagMeta['key'] = (!empty($tagMeta['plural'])) ? $tagMeta['plural'] : $tagMeta['key'];
        }

        if (strtolower($tagType) == strtolower($tagMeta['key'])
            || (!empty($tagMeta['plural']) && strtolower($tagType) == strtolower($tagMeta['plural']))
        ) {
            $tagTypesDropdown->setTrigger($tagName, 'button', 'btn btn-secondary');
        }

        $url = '/settings/tagging/?type='.strtolower($tagMeta['key']);
        $tagTypesDropdown->addLink($tagName, $url, $tagMeta['key']);
    }

    ?>
    <div class="toolbar flex-wrap">
        <div class="search-wrap input-wrap toolbar-main">
            <?php
            $info = sprintf(t('%s tag(s) found.'), $this->data('RecordCount'));
            $placeholder = t('Search for a tag.', 'Search for all or part of a tag.');
            $form->Method = 'get';
            echo $form->searchForm('search', '/settings/tagging', ['placeholder' => $placeholder], $info);
            ?>
        </div>
        <?php echo $tagTypesDropdown; ?>
        <?php PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']); ?>
    </div>

    <?php $tags = $this->data('Tags'); ?>

    <div class="plank-container plank-container-grid">
        <?php foreach ($tags as $tag) :
            $count = val('CountDiscussions', $tag, 0);
            $displayName = tagFullName($tag);
            $dropdown = '';

            if ((val('Type', $tag, '') == '')) {
                // add dropdown
                $dropdown = new DropdownModule('dropdown', '', '', 'dropdown-menu-right');
                $dropdown->setView('dropdown-twbs')
                    ->addLink(t('Edit'), "/settings/tags/edit/{$tag['TagID']}", 'edit', 'js-modal')
                    ->addDivider()
                    ->addLink(t('Delete'), "/settings/tags/delete/{$tag['TagID']}", 'delete', 'js-modal-confirm');
            }
            ?>
            <div id="Tag_<?php echo val('TagID', $tag) ?>" class="plank-wrapper">
                <div class="plank">
                    <div class="plank-title">
                        <?php echo anchor(htmlspecialchars($displayName), '/discussions/tagged/'.val('Name', $tag), 'reverse-link'); ?>
                        <span class="badge badge-outline">
                            <?php echo $count; ?>
                        </span>
                    </div>
                    <div class="plank-options">
                        <?php echo $dropdown; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
