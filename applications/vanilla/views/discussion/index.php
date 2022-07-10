<?php use Vanilla\Theme\BoxThemeShim;
use Vanilla\Utility\HtmlUtils;

if (!defined('APPLICATION')) exit();
$Session = Gdn::session();
if (!function_exists('WriteComment'))
    include $this->fetchViewLocation('helper_functions', 'discussion');


$writeDiscussionPageHeader = function ($sender) {
    $writeOptionsMenu = function ($withFireEvent = true) use ($sender) {
        echo '<div class="Options">';
        if ($withFireEvent) {
            $sender->fireEvent('BeforeDiscussionOptions');
        }
        writeBookmarkLink();
        echo getDiscussionOptionsDropdown();
        writeAdminCheck();
        echo '</div>';
    };

    // Write the page title.
    echo '<div id="Item_0" class="PageTitle pageHeadingBox isLarge">';
        if (!BoxThemeShim::isActive()) {
            $writeOptionsMenu();
        }
        //this is for data driven themes, to add the resolved status/icon before title
        if (BoxThemeShim::isActive()) {
            $sender->fireEvent('BeforeDiscussionOptions');
        }
        echo '<h1>'.($sender->data('Discussion.displayName') ? $sender->data('Discussion.displayName') : $sender->data('Discussion.Name')).'</h1>';
        if (BoxThemeShim::isActive()) {
            $writeOptionsMenu(false);
        }
    echo "</div>";

    $sender->fireEvent('AfterDiscussionTitle');
    $sender->fireEvent('AfterPageTitle');
};

if (BoxThemeShim::isActive()) {
    // With the shim, the h1 goes outside the top level box.
    $writeDiscussionPageHeader($this);
}

BoxThemeShim::startBox();

    // Wrap the discussion related content in a div.
    echo '<div class="MessageList Discussion">';

        if (!BoxThemeShim::isActive()) {
            // Without the shim the h1 goes inside the box for compatibility reasons.
            $writeDiscussionPageHeader($this);
        }

        $isFirstPage = $this->data('Page') == 1;

        if ($isFirstPage) {
            // First page renders the discussion itself.
            include $this->fetchViewLocation('discussion', 'discussion');
        }
    echo '</div>'; // close discussion wrap

    if ($isFirstPage) {
        //for dataDriven themes we render GuestModule after discussion as well, if there are at least some comments
        if (!Gdn::session()->isValid() && Gdn::themeFeatures()->get("DataDrivenTheme") && $this->data('Comments')->numRows() > 2) {
            /** @var GuestModule  $guestModule */
            $guestModule = Gdn::getContainer()->get(GuestModule::class);
            $guestModule->setWidgetAlignment("center");
            echo $guestModule;
        }
        // First page may have plugins to render after the discussion.
        $this->fireEvent('AfterDiscussion');
    }

    // Comments
    BoxThemeShim::inactiveHtml('<div class="CommentsWrap">');

        // Write the comments.
        $this->Pager->Wrapper = '<span %1$s>%2$s</span>';
        echo '<span class="BeforeCommentHeading">';
            $this->fireEvent('CommentHeading');
            echo $this->Pager->toString('less');
        echo '</span>';

        BoxThemeShim::inactiveHtml('<div class="DataBox DataBox-Comments">');
            $hasComments = $this->data('Comments')->numRows() > 0;
            if ($hasComments) {
                BoxThemeShim::startHeading();
                echo '<h2 class="CommentHeading">' . $this->data('_CommentsHeader', t('Comments')) . '</h2>';
                BoxThemeShim::endHeading();
            }
            $listClasses = HtmlUtils::classNames("MessageList DataList Comments", $hasComments ? 'pageBox' : '');
            ?>
            <ul class="<?php echo $listClasses ?>">
                <?php include $this->fetchViewLocation('comments'); ?>
            </ul>
            <?php
            $this->fireEvent('AfterComments');
            if ($this->Pager->lastPage()) {
                $LastCommentID = $this->addDefinition('LastCommentID');
                if (!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
                    $this->addDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
                $this->addDefinition('Vanilla_Comments_AutoRefresh', Gdn::config('Vanilla.Comments.AutoRefresh', 0));
            }
        BoxThemeShim::inactiveHtml('</div>');

        echo '<div class="P PagerWrap">';
            $this->Pager->Wrapper = '<div %1$s>%2$s</div>';
            echo $this->Pager->toString('more');
        echo '</div>'; // End pager.
    BoxThemeShim::inactiveHtml('</div>');
    writeCommentForm();

BoxThemeShim::endBox();
