<?php if (!defined('APPLICATION')) exit(); ?>

<ul class="DataList CategoryList<?php echo $this->data('DoHeadings') ? ' CategoryListWithHeadings' : ''; ?>">
<?php foreach ($this->data('Categories') as $category) : ?>
    <li id="Category_<?php echo val('CategoryID', $category); ?>" class="<?php echo cssClass($category); ?>'">
        <div class="ItemContent Category">
            <div class="Options"><?php echo getOptions($category); ?></div>
            <?php categoryPhoto($category); ?>
            <div class="TitleWrap">
                <?php echo anchor(Gdn_Format::text(val('Name', $category)), categoryUrl($category), 'Title') ?>
            </div>
            <div class="CategoryDescription"><?php echo val('Description', $category); ?></div>
            <div class="Meta">
                <span class="MItem RSS"><?php echo anchor(
                        img('applications/dashboard/design/images/rss.gif',['alt' => t('RSS Feed')]),
                        '/categories/'.val('UrlCode', $category).'/feed.rss', '', ['title' => t('RSS Feed')]);
                ?></span>
                <span class="MItem DiscussionCount"><?php printf(
                    pluralTranslate(
                        val('CountDiscussions', $category),
                        '%s discussion html',
                        '%s discussions html',
                        t('%s discussion'),
                        t('%s discussions')
                    ),
                    bigPlural(val('CountDiscussions', $category), '%s discussion')
                ); ?></span>
                <span class="MItem CommentCount"><?php printf(
                    pluralTranslate(
                        val('CountComments', $category),
                        '%s comment html',
                        '%s comments html',
                        t('%s comment'),
                        t('%s comments')
                    ),
                    bigPlural(val('CountComments', $category), '%s comment')
                ); ?></span>
                <?php if (val('LastTitle', $category) != ''): ?>
                <span class="MItem LastDiscussionTitle"><?php printf(
                        t('Most recent: %1$s by %2$s'),
                        anchor(Gdn_Format::text(sliceString(val('LastTitle', $category), 40)), val('LastUrl', $category)),
                        userAnchor(userBuilder($category, 'Last'))
                ); ?></span>
                <span class="MItem LastCommentDate"><?php echo Gdn_Format::date(val('LastDateInserted', $category)); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </li>
<?php endforeach; ?>
</ul>

<?php echo wrap(
    anchor(htmlspecialchars(t('View All')), $this->data('ParentCategory.Url')),
    'span',
    ['class' => 'MItem Category']
); ?>

