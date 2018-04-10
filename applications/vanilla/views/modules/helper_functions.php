<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteModuleDiscussion')):
    function writeModuleDiscussion($discussion, $px = 'Bookmark', $showPhotos = false) {
        ?>
        <li id="<?php echo "{$px}_{$discussion->DiscussionID}"; ?>" class="<?php echo cssClass($discussion); ?>">
            <?php if ($showPhotos) :
                $firstUser = userBuilder($discussion, 'First');
                echo userPhoto($firstUser, ['LinkClass' => 'IndexPhoto']);
            endif; ?>
   <span class="Options">
      <?php
      //      echo optionsList($Discussion);
      echo bookmarkButton($discussion);
      ?>
   </span>

            <div class="Title"><?php
                echo anchor(Gdn_Format::text($discussion->Name, false), discussionUrl($discussion).($discussion->CountCommentWatch > 0 ? '#Item_'.$discussion->CountCommentWatch : ''), 'DiscussionLink');
                ?></div>
            <div class="Meta DiscussionsModuleMeta">
                <?php
                $last = new stdClass();
                $last->UserID = $discussion->LastUserID;
                $last->Name = $discussion->LastName;

                echo newComments($discussion);

                $translation = pluralTranslate($discussion->CountComments, '%s comment html', '%s comments html', t('%s comment'), t('%s comments'));
                echo '<span class="MItem">'.Gdn_Format::date($discussion->LastDate, 'html').userAnchor($last).'</span>';
                echo '<span class="MItem CountComments Hidden">'.sprintf($translation, $discussion->CountComments).'</span>';
                ?>
            </div>
        </li>
    <?php
    }
endif;

if (!function_exists('WritePromotedContent')):
    /**
     * Generates html output of $content array
     *
     * @param array|object $content
     * @param PromotedContentModule $sender
     */
    function writePromotedContent($content, $sender) {
        static $userPhotoFirst = NULL;
        if ($userPhotoFirst === null)
            $userPhotoFirst = c('Vanilla.Comment.UserPhotoFirst', true);

        $contentType = val('RecordType', $content);
        $contentID = val("{$contentType}ID", $content);
        $author = val('Author', $content);
        $contentURL = val('Url', $content);
        $sender->EventArguments['Content'] = &$content;
        $sender->EventArguments['ContentUrl'] = &$contentURL;
        ?>
        <div id="<?php echo "Promoted_{$contentType}_{$contentID}"; ?>" class="<?php echo cssClass($content); ?>">
            <div class="AuthorWrap">
         <span class="Author">
            <?php
            if ($userPhotoFirst) {
                echo userPhoto($author);
                echo userAnchor($author, 'Username');
            } else {
                echo userAnchor($author, 'Username');
                echo userPhoto($author);
            }
            $sender->fireEvent('AuthorPhoto');
            ?>
         </span>
         <span class="AuthorInfo">
            <?php
            echo ' '.wrapIf(htmlspecialchars(val('Title', $author)), 'span', ['class' => 'MItem AuthorTitle']);
            echo ' '.wrapIf(htmlspecialchars(val('Location', $author)), 'span', ['class' => 'MItem AuthorLocation']);
            $sender->fireEvent('AuthorInfo');
            ?>
         </span>
            </div>
            <div class="Meta CommentMeta CommentInfo">
         <span class="MItem DateCreated">
            <?php echo anchor(Gdn_Format::date($content['DateInserted'], 'html'), $contentURL, 'Permalink', ['rel' => 'nofollow']); ?>
         </span>
                <?php
                // Include source if one was set
                if ($source = val('Source', $content))
                    echo wrap(sprintf(t('via %s'), t($source.' Source', $source)), 'span', ['class' => 'MItem Source']);

                $sender->fireEvent('ContentInfo');
                ?>
            </div>
            <div
                class="Title"><?php echo anchor(Gdn_Format::text(sliceString($content['Name'], $sender->TitleLimit), false), $contentURL, 'DiscussionLink'); ?></div>
            <div class="Body">
                <?php
                $linkContent = Gdn_Format::excerpt($content['Body'], $content['Format']);
                $trimmedLinkContent = sliceString($linkContent, $sender->BodyLimit);

                echo anchor(htmlspecialchars($trimmedLinkContent), $contentURL, 'BodyLink');

                $sender->fireEvent('AfterPromotedBody'); // separate event to account for less space.
                ?>
            </div>
        </div>
    <?php
    }
endif;

if (!function_exists('writePromotedContentList')):
    /**
     * Generate a modern view of array $data.
     *
     * @param array $data The data used to generate the view
     */
    function writePromotedContentList($data) {
        ?>
        <ul class="PromotedContentList DataList">
            <?php foreach ($data as $row) {
                writePromotedContentRow($row, 'modern');
            } ?>
        </ul>
    <?php
    }
endif;

if (!function_exists('writePromotedContentTable')):
    /**
     * Generate a table view of array $data.
     *
     * @param array $data The $data used to generate the view
     */
    function writePromotedContentTable($data) {
        ?>
        <div class="DataTableContainer">
            <div class="DataTableWrap">
                <table class="DataTable">
                    <thead>
                    <tr>
                        <td class="DiscussionName" role="columnheader">
                            <div class="Wrap"><?php echo t('Subject'); ?></div>
                        </td>
                        <td class="BlockColumn BlockColumn-User LastUser" role="columnheader">
                            <div class="Wrap"><?php echo t('Author'); ?></div>
                        </td>
                    </tr>
                    </thead>
                    <?php foreach ($data as $row) {
                        writePromotedContentRow($row, 'table');
                    } ?>
                </table>
            </div>
        </div>
    <?php
    }
endif;

if (!function_exists('writePromotedContentRow')):
    /**
     * Write a promoted content item in a table or modern view.
     *
     * @param array $row The row to output.
     * @param string $view The view to use.
     */
    function writePromotedContentRow($row, $view) {
        $title = htmlspecialchars(val('Name', $row));
        $url = val('Url', $row);
        $body = Gdn_Format::plainText(val('Body', $row), val('Format', $row));
        $categoryUrl = val('CategoryUrl', $row);
        $categoryName = val('CategoryName', $row);
        $date = val('DateUpdated', $row) ?: val('DateInserted', $row);
        $date = Gdn_Format::date($date, 'html');
        $type = val('RecordType', $row, 'post');
        $id = val('CommentID', $row, val('DiscussionID', $row, ''));
        $author = val('Author', $row);
        $username = val('Name', $author);
        $userUrl = val('Url', $author);
        $userPhoto = val('PhotoUrl', $author);
        $cssClass = val('CssClass', $author);

        if ($view == 'table') {
            ?>
            <tr id="Promoted_<?php echo $type.'_'.$id; ?>" class="Item PromotedContent-Item <?php echo $cssClass; ?>">
                <td class="Name">
                    <div class="Wrap">
                        <a class="Title" href="<?php echo $url; ?>">
                            <?php echo $title; ?>
                        </a>
                        <span class="MItem Category"><?php echo t('in'); ?> <a href="<?php echo $categoryUrl; ?>"
                                                                               class="MItem-CategoryName"><?php echo $categoryName; ?></a></span>

                        <div class="Description"><?php echo $body; ?></div>
                    </div>
                </td>
                <td class="BlockColumn BlockColumn-User User">
                    <div class="Block Wrap">
                        <a class="PhotoWrap PhotoWrapSmall" href="<?php echo $userUrl; ?>">
                            <img class="ProfilePhoto ProfilePhotoSmall" src="<?php echo $userPhoto; ?>">
                        </a>
                        <a class="UserLink BlockTitle" href="<?php echo $userUrl; ?>"><?php echo $username; ?></a>

                        <div class="Meta">
                            <a class="CommentDate MItem" href="<?php echo $url; ?>"><?php echo $date; ?></a>
                        </div>
                    </div>
                </td>
            </tr>

        <?php } else { ?>

            <li id="Promoted_<?php echo $type.'_'.$id; ?>" class="Item PromotedContent-Item <?php echo $cssClass; ?>">
                <?php if (c('EnabledPlugins.IndexPhotos')) { ?>
                    <a title="<?php echo $username; ?>" href="<?php echo $userUrl; ?>" class="IndexPhoto PhotoWrap">
                        <img src="<?php echo $userPhoto; ?>" alt="<?php echo $username; ?>"
                             class="ProfilePhoto ProfilePhotoMedium">
                    </a>
                <?php } ?>
                <div class="ItemContent Discussion">
                    <div class="Title">
                        <a href="<?php echo $url; ?>">
                            <?php echo $title; ?>
                        </a>
                    </div>
                    <div class="Excerpt"><?php echo $body; ?></div>
                    <div class="Meta">
                        <span class="MItem DiscussionAuthor"><ahref="<?php echo $userUrl; ?>
                            "><?php echo $username; ?></a></span>
                        <span class="MItem Category"><?php echo t('in'); ?> <a href="<?php echo $categoryUrl; ?>"
                                                                               class="MItem-CategoryName"><?php echo $categoryName; ?></a></span>
                    </div>
                </div>
            </li>

        <?php }
    }
endif;
