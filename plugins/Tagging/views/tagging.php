<?php if (!defined('APPLICATION')) exit(); ?>

<?php

$TagType = $this->data('_TagType');
$TagTypes = $this->data('_TagTypes');
$CanAddTags = $this->data('_CanAddTags');

?>

    <h1><?php echo t($this->Data['Title']); ?></h1>
    <div class="Info">
        <?php echo t('Tags are keywords that users can assign to discussions to help categorize their question with similar questions.'); ?>
    </div>

<?php echo $this->Form->open(); ?>
    <div class="Wrap">
        <?php
        echo $this->Form->errors();

        echo '<p>', t('Search for a tag.', 'Search for all or part of a tag.'), '</p>';

        echo $this->Form->textBox('Search');
        echo ' '.$this->Form->button(t('Go'));
        //printf(t('%s tag(s) found.'), $this->data('RecordCount'));
        ?>
    </div>
    <div class="Wrap">
        <?php
        echo t('Click a tag name to edit. Click x to remove.');
        echo ' ';
        echo t("Red tags are special and can't be removed.");
        ?>
    </div>

    <ul class="tabbed-content tag-tabs">

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
                $CurrentTab = 'current-tab';
            }

            $TabUrl = url('/settings/tagging/?type='.strtolower($TagMeta['key']));

            ?>

            <li>
                <a href="<?php echo $TabUrl; ?>" class="<?php echo $CurrentTab; ?>">
                    <?php echo ucwords(strtolower($TagName)); ?>
                    <?php if ($CurrentTab) echo "({$this->data('RecordCount')})"; ?>
                </a>
            </li>

        <?php endforeach; ?>

    </ul>

    <div class="Tags">
        <?php
        $Session = Gdn::session();
        $TagCount = $this->data('RecordCount');
        if ($TagCount == 0) {
            echo t("There are no tags in the system yet.");
        } else {
            $Tags = $this->data('Tags');
            foreach ($Tags as $Tag) {
                $CssClass = 'TagAdmin';
                $Title = '';
                $Special = FALSE;

                if (val('Type', $Tag)) {
                    $Special = TRUE;
                    $CssClass .= " Tag-Special Tag-{$Tag['Type']}";
                    $Title = t('This is a special tag.');
                }

                ?>
                <div id="<?php echo "Tag_{$Tag['TagID']}"; ?>" class="<?php echo $CssClass; ?>"
                     title="<?php echo $Title; ?>">
                    <?php
                    $DisplayName = TagFullName($Tag);

                    if ($Special) {
                        echo htmlspecialchars($DisplayName).' '.Wrap($Tag['CountDiscussions'], 'span', array('class' => 'Count'));
                    } else {
                        echo anchor(
                            htmlspecialchars($DisplayName).' '.Wrap($Tag['CountDiscussions'], 'span', array('class' => 'Count')),
                            "/settings/tags/edit/{$Tag['TagID']}",
                            'TagName Tag_'.str_replace(' ', '_', $Tag['Name'])
                        );

                        echo ' '.anchor('×', "/settings/tags/delete/{$Tag['TagID']}", 'Delete Popup');
                    }
                    ?>
                </div>
            <?php
            }
        }

        ?>

        <div class="add-new-tag">

            <?php
            if ($CanAddTags) {
                echo ' '.anchor('Add Tag', '/settings/tags/add?type='.$TagType, 'Popup Button');
            }
            ?>

        </div>

    </div>
<?php

PagerModule::write();

echo $this->Form->close();
