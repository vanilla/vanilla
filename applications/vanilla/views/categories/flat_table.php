<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();
$userID = Gdn::session()->UserID;
$categoryID = $this->Category->CategoryID;
BoxThemeShim::startHeading();
?>
    <h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>
    <div class="P PageDescription"><?php echo $this->description(); ?></div>
<?php
$this->fireEvent('AfterDescription');
$this->fireEvent('AfterPageTitle');
BoxThemeShim::endHeading();

$categories = $this->data('CategoryTree');

writeCategoryTable($categories, 1);

?>
<div class="PageControls Bottom">
    <?php PagerModule::write(); ?>
</div>
