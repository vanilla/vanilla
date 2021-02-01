<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) return;
$userID = Gdn::session()->UserID;
$categoryID = isset($this->Category) ? $this->Category->CategoryID : null;
BoxThemeShim::startHeading();
?>

    <h1 class="H HomepageTitle"><?php echo $this->data('Title').followButton($categoryID); ?></h1>
    <div class="P PageDescription"><?php echo $this->description(); ?></div>
<?php
BoxThemeShim::endHeading();
$this->fireEvent('AfterDescription');
$this->fireEvent('AfterPageTitle');
if ($this->data('EnableFollowingFilter')) {
    echo '<div class="PageControls Top">'.categoryFilters().'</div>';
}
$categories = $this->data('CategoryTree');
BoxThemeShim::startBox();
writeCategoryTable($categories);
BoxThemeShim::endBox();
?>
