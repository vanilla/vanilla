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
    if ($Current == $Url) {
        $CssClass .= ' Current';
    }
    $CssClass .= ' Choice';

    echo wrap(
        '<div class="image-wrap">'
        .sprite($SpriteClass)
        .'<div class="overlay">'
        .'<div class="buttons">'
        .anchor(t('Select'), $Url, 'btn btn-overlay', ['title' => $Description, 'rel' => $Url])
        .'</div>'
        .'<div class="selected">'
        .dashboardSymbol('checkmark')
        .'</div>'
        .'</div></div>'
        .'<div class="title">'
        .t($Title)
        .'</div>',
        'div',
        array('class' => $CssClass.' label-selector-item')
    );
}

?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {

            $('.HomeOptions a').click(function() {
                $('.HomeOptions .Choice').removeClass('Current');
                $(this).parents('.Choice').addClass('Current');
                var page = $(this).attr('rel');
                $('#Form_Target').val(page);
                return false;
            });

            $('.LayoutOptions a').click(function() {
                var parent = $(this).parents('.LayoutOptions');
                var layoutContainer = $(parent).hasClass('DiscussionsLayout') ? 'DiscussionsLayout' : 'CategoriesLayout';
                $(parent).find('.Choice').removeClass('Current');
                $(this).parents('.Choice').addClass('Current');
                var layout = $(this).attr('rel');
                $('#Form_' + layoutContainer).val(layout);
                return false;
            });

        });
    </script>
    <?php Gdn_Theme::assetBegin('Help'); ?>
    <div class="Help Aside">
        <?php
        echo '<h2>', t('Need More Help?'), '</h2>';
        echo '<ul>';
        echo wrap(Anchor(t("Configuring Vanilla's Homepage"), 'http://docs.vanillaforums.com/developers/configuration/homepage/'), 'li');
        echo wrap(Anchor(t("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
        echo '</ul>';
        ?>
    </div>
    <?php Gdn_Theme::assetEnd(); ?>
    <h1><?php echo t('Homepage'); ?></h1>
    <div>
        <?php printf(t('Use the content at this url as your homepage.', 'Choose the page people should see when they visit: <strong style="white-space: nowrap;">%s</strong>'), url('/', true)) ?>
    </div>

    <div class="Homepage">
        <div class="HomeOptions">
            <?php
            // Only show the vanilla pages if Vanilla is enabled
            $CurrentTarget = $this->data('CurrentTarget');

            if (Gdn::addonManager()->isEnabled('Vanilla', \Vanilla\Addon::TYPE_ADDON)) {
                echo WriteHomepageOption('Discussions', 'discussions', 'SpDiscussions', $CurrentTarget);
                echo WriteHomepageOption('Categories', 'categories', 'SpCategories', $CurrentTarget);
                // echo WriteHomepageOption('Categories &amp; Discussions', 'categories/discussions', 'categoriesdiscussions', $CurrentTarget);
            }
            //echo WriteHomepageOption('Activity', 'activity', 'SpActivity', $CurrentTarget);

            if (Gdn::addonManager()->isEnabled('Reactions', \Vanilla\Addon::TYPE_ADDON)) {
                echo WriteHomepageOption('Best Of', 'bestof', 'SpBestOf', $CurrentTarget);
            }
            ?>
        </div>
        <?php if (Gdn::addonManager()->isEnabled('Vanilla', \Vanilla\Addon::TYPE_ADDON)): ?>
            <p>
                <?php echo wrap(t('Discussions Layout'), 'strong'); ?>
                <br/><?php echo t('Choose the preferred layout for the discussions page.'); ?>
            </p>
            <div class="LayoutOptions DiscussionsLayout">
                <?php
                echo WriteHomepageOption('Modern Layout', 'modern', 'SpDiscussions', $CurrentDiscussionLayout, t('Modern non-table-based layout'));
                echo WriteHomepageOption('Table Layout', 'table', 'SpDiscussionsTable', $CurrentDiscussionLayout, t('Classic table layout used by traditional forums'));
                ?>
            </div>
            <p>
                <?php echo wrap(t('Categories Layout'), 'strong'); ?>
                (<?php echo anchor(t("adjust layout"), '/vanilla/settings/managecategories', array('class' => 'AdjustCategories')); ?>
                )
                <br/><?php echo t('Choose the preferred layout for the categories page.'); ?>
            </p>
            <div class="LayoutOptions CategoriesLayout">
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
