<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::session();

$CurrentDiscussionLayout = c('Vanilla.Discussions.Layout', '');
if ($CurrentDiscussionLayout == '')
    $CurrentDiscussionLayout = 'modern';

$CurrentCategoriesLayout = c('Vanilla.Categories.Layout', 'modern');
if ($CurrentCategoriesLayout == '')
    $CurrentCategoriesLayout = 'modern';

function writeHomepageOption($Title, $Url, $CssClass, $Current, $Description = '') {
    $SpriteClass = $CssClass;
    if ($Current == $Url)
        $CssClass .= ' Current';
    $CssClass .= ' Choice';
    echo anchor(t($Title).Wrap(sprite($SpriteClass), 'span', array('class' => 'Wrap')), $Url, array('class' => $CssClass, 'title' => $Description, 'rel' => $Url));
}

?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {

            $('.HomeOptions a.Choice').click(function() {
                $('.HomeOptions a.Choice').removeClass('Current');
                $(this).addClass('Current');
                var page = $(this).attr('rel');
                $('#Form_Target').val(page);
                return false;
            });

            $('.LayoutOptions a.Choice').click(function() {
                var parent = $(this).parents('.LayoutOptions');
                var layoutContainer = $(parent).hasClass('DiscussionsLayout') ? 'DiscussionsLayout' : 'CategoriesLayout';
                $(parent).find('a').removeClass('Current');
                $(this).addClass('Current');
                var layout = $(this).attr('rel');
                $('#Form_' + layoutContainer).val(layout);
                return false;
            });

        });
    </script>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Configuring Vanilla's Homepage"), 'http://docs.vanillaforums.com/developers/configuration/homepage/'), 'li');
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo '</ul>';
        ?>
    </div>

    <h1><?php echo t('Homepage'); ?></h1>
    <div class="Info">
        <?php printf(t('Use the content at this url as your homepage.', 'Choose the page people should see when they visit: <strong style="white-space: nowrap;">%s</strong>'), url('/', true)) ?>
    </div>

    <div class="Homepage">
        <div class="HomeOptions">
            <?php
            // Only show the vanilla pages if Vanilla is enabled
            $CurrentTarget = $this->data('CurrentTarget');

            if (Gdn::ApplicationManager()->CheckApplication('Vanilla')) {
                echo WriteHomepageOption('Discussions', 'discussions', 'SpDiscussions', $CurrentTarget);
                echo WriteHomepageOption('Categories', 'categories', 'SpCategories', $CurrentTarget);
                // echo WriteHomepageOption('Categories &amp; Discussions', 'categories/discussions', 'categoriesdiscussions', $CurrentTarget);
            }
            //echo WriteHomepageOption('Activity', 'activity', 'SpActivity', $CurrentTarget);

            if (Gdn::pluginManager()->CheckPlugin('Reactions')) {
                echo WriteHomepageOption('Best Of', 'bestof', 'SpBestOf', $CurrentTarget);
            }
            ?>
        </div>
        <?php if (Gdn::ApplicationManager()->CheckApplication('Vanilla')): ?>
            <div class="LayoutOptions DiscussionsLayout">
                <p>
                    <?php echo wrap(t('Discussions Layout'), 'strong'); ?>
                    <br/><?php echo t('Choose the preferred layout for the discussions page.'); ?>
                </p>
                <?php
                echo WriteHomepageOption('Modern Layout', 'modern', 'SpDiscussions', $CurrentDiscussionLayout, t('Modern non-table-based layout'));
                echo WriteHomepageOption('Table Layout', 'table', 'SpDiscussionsTable', $CurrentDiscussionLayout, t('Classic table layout used by traditional forums'));
                ?>
            </div>
            <div class="LayoutOptions CategoriesLayout">
                <p>
                    <?php echo wrap(t('Categories Layout'), 'strong'); ?>
                    (<?php echo anchor(t("adjust layout"), '/vanilla/settings/managecategories', array('class' => 'AdjustCategories')); ?>
                    )
                    <br/><?php echo t('Choose the preferred layout for the categories page.'); ?>
                </p>
                <?php
                echo WriteHomepageOption('Modern Layout', 'modern', 'SpCategories', $CurrentCategoriesLayout, t('Modern non-table-based layout'));
                echo WriteHomepageOption('Table Layout', 'table', 'SpCategoriesTable', $CurrentCategoriesLayout, t('Classic table layout used by traditional forums'));
                echo WriteHomepageOption('Mixed Layout', 'mixed', 'SpCategoriesMixed', $CurrentCategoriesLayout, t('All categories listed with a selection of 5 recent discussions under each'));
                ?>
            </div>
        <?php endif; ?>
    </div>

<?php
echo $this->Form->open();
echo $this->Form->errors();
echo $this->Form->Hidden('Target');
echo $this->Form->Hidden('DiscussionsLayout', array('value' => $CurrentDiscussionLayout));
echo $this->Form->Hidden('CategoriesLayout', array('value' => $CurrentCategoriesLayout));
echo $this->Form->close('Save');
