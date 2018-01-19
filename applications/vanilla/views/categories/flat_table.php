<?php if (!defined('APPLICATION')) exit(); ?>
    <h1 class="H HomepageTitle"><?php echo $this->data('Title').followButton(true); ?></h1>
    <div class="P PageDescription"><?php echo $this->description(); ?></div>
<?php
$this->fireEvent('AfterDescription');
$this->fireEvent('AfterPageTitle');

if (c('Vanilla.EnableCategoryFollowing')) {
    echo '<div class="PageControls Top">';
    echo categoryFilters([['url' => 'http://google.ca', 'active' => true, 'name' => 'All'], ['url' => 'http://google.ca', 'name' => 'Following']]);
    echo '</div>';
}

$categories = $this->data('CategoryTree');

writeCategoryTable($categories, 1);

?>
<div class="PageControls Bottom">
    <?php PagerModule::write(); ?>
</div>
