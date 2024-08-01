<?php
/**
 * Reaction functions
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

if (!defined("APPLICATION")) {
    exit();
}
use Vanilla\Utility\HtmlUtils;
use Vanilla\Web\TwigStaticRenderer;

if (!function_exists("writeReactions")) {
    /**
     *
     *
     * @param $row
     * @throws Exception
     */
    function writeReactions($row)
    {
        $categoryID = null;
        $controller = \Gdn::controller();
        if ($controller !== null) {
            $categoryID = $controller->data("Category")["CategoryID"] ?? null;
        }
        $dataDrivenColors = Gdn::themeFeatures()->useDataDrivenTheme();
        $attributes = val("Attributes", $row);
        if (is_string($attributes)) {
            $attributes = dbdecode($attributes);
            setValue("Attributes", $row, $attributes);
        }

        static $types = null;
        if ($types === null) {
            $types = ReactionModel::getReactionTypes(["Class" => ["Positive", "Negative"], "Active" => 1]);
        }
        // Since statically cache the types, we do a copy of type for this row so that plugin can modify the value by reference.
        // Not doing so would alter types for every rows and since Discussions and Comments have different behavior that could be a problem.
        $rowReactionTypesReference = $types;
        Gdn::controller()->EventArguments["ReactionTypes"] = &$rowReactionTypesReference;

        if ($iD = val("CommentID", $row)) {
            $recordType = "comment";
        } elseif ($iD = val("ActivityID", $row)) {
            $recordType = "activity";
        } else {
            $recordType = "discussion";
            $iD = val("DiscussionID", $row);
        }
        Gdn::controller()->EventArguments["RecordType"] = $recordType;
        Gdn::controller()->EventArguments["RecordID"] = $iD;

        if (
            checkPermission("Garden.Reactions.View") &&
            Gdn::config("Vanilla.Reactions.ShowUserReactions", ReactionModel::RECORD_REACTIONS_DEFAULT) == "avatars"
        ) {
            writeRecordReactions($row);
        }

        echo '<div class="Reactions">';
        Gdn_Theme::bulletRow();

        // Write the flags.
        static $flags = null;
        if ($flags === null && checkPermission("Reactions.Flag.Add")) {
            $flags = ReactionModel::getReactionTypes(["Class" => "Flag", "Active" => 1]);
            $flagCodes = [];
            foreach ($flags as $flag) {
                $flagCodes[] = $flag["UrlCode"];
            }
            Gdn::controller()->EventArguments["Flags"] = &$flags;
            Gdn::controller()->fireEvent("Flags");
        }

        // Allow addons to work with flags
        Gdn::controller()->EventArguments["Flags"] = &$flags;
        Gdn::controller()->fireEvent("BeforeFlag");

        if (!empty($flags) && is_array($flags)) {
            $discussionOrCommentID = $row->CommentID ?? ($row->DiscussionID ?? null);
            if (
                Gdn::config("Feature.escalations.Enabled", false) &&
                $categoryID !== null &&
                $discussionOrCommentID !== null
            ) {
                $discussionName = $row->DiscussionName ?? $row->Name;

                $twig = <<<TWIG
<a
    class="ReactButton js-legacyDiscussionOrCommentReport"
    href="#"
    tabindex="0"
    title="Flag"
    rel="nofollow"
    role="button"
    data-recordType="{{ recordType }}"
    data-recordID="{{ discussionOrCommentID }}"
    data-discussionName="{{ discussionName }}"
    data-categoryID="{{ categoryID }}"
>
    <span class="ReactSprite ReactFlag" />
    <span class="ReactLabel">{{ t("Flag") }}</span>
</a>
TWIG;

                $flagHtml = TwigStaticRenderer::renderString($twig, [
                    "recordType" => $recordType,
                    "discussionOrCommentID" => $discussionOrCommentID,
                    "discussionName" => $discussionName,
                    "categoryID" => $categoryID,
                ]);
                echo $flagHtml;
            } else {
                echo Gdn_Theme::bulletItem("Flags");

                echo ' <span class="FlagMenu ToggleFlyout">';
                // Write the handle.
                echo reactionButton($row, "Flag", ["LinkClass" => "FlyoutButton", "IsHeading" => true]);
                echo '<ul class="Flyout MenuItems Flags" style="display: none;">';

                foreach ($flags as $flag) {
                    if (is_callable($flag)) {
                        echo "<li>" . call_user_func($flag, $row, $recordType, $iD) . "</li>";
                    } else {
                        echo "<li>" . reactionButton($row, $flag["UrlCode"]) . "</li>";
                    }
                }

                Gdn::controller()->fireEvent("AfterFlagOptions");
                echo "</ul>";
                echo "</span> ";
            }
        }
        Gdn::controller()->fireEvent("AfterFlag");

        $score = formatScore(val("Score", $row));
        echo '<span class="Column-Score Hidden">' . $score . "</span>";

        // Write the reactions.
        $reactionHtml = "";
        foreach ($rowReactionTypesReference as $type) {
            if (isset($type["RecordTypes"]) && !in_array($recordType, (array) $type["RecordTypes"])) {
                continue;
            }
            $reactionHtml .= " " . reactionButton($row, $type["UrlCode"]) . " ";
        }

        if ($reactionHtml !== "") {
            echo Gdn_Theme::bulletItem("Reactions");
        }

        if (!$dataDrivenColors) {
            echo '<span class="ReactMenu">';
            echo '<span class="ReactButtons">';
        }

        echo $reactionHtml;

        if (!$dataDrivenColors) {
            echo "</span>";
            echo "</span>";
        }

        if (checkPermission(["Garden.Moderation.Manage", "Moderation.Reactions.Edit"])) {
            echo Gdn_Theme::bulletItem("ReactionsMod") .
                anchor(t("Log"), "/reactions/logged/{$recordType}/{$iD}", "Popup ReactButton ReactButton-Log", [
                    "rel" => "nofollow",
                ]);
        }

        Gdn::controller()->fireEvent("AfterReactions");

        echo "</div>";
        Gdn::controller()
            ->fireAs("DiscussionController")
            ->fireEvent("Replies");
    }
}

if (!function_exists("FormatScore")) {
    /**
     * Formats the score as an integer.
     *
     * @param string|int $score
     * @return int
     */
    function formatScore($score)
    {
        return (int) $score;
    }
}
/**
 * Filter buttons by column.
 *
 * @param string $column
 * @param bool $label
 * @param string $defaultOrder
 * @param string $cssClass
 * @return string
 */
function orderByButton($column, $label = false, $defaultOrder = "", $cssClass = "")
{
    $qSParams = $_GET;
    $qSParams["orderby"] = urlencode($column);
    $url = Gdn::controller()->SelfUrl . "?" . http_build_query($qSParams);
    if (!$label) {
        $label = t("by " . $column);
    }

    $cssClass = " " . $cssClass;
    $currentColumn = Gdn::controller()->data("CommentOrder.Column");
    if ($column == $currentColumn) {
        $cssClass .= " OrderBy-" . ucfirst(Gdn::controller()->data("CommentOrder.Direction")) . " Selected";
    }

    return anchor($label, $url, "FilterButton OrderByButton OrderBy-" . $column . $cssClass, ["rel" => "nofollow"]);
}

/**
 * Count reactions.
 *
 * @param object $row
 * @param array $urlCodes
 * @return mixed reaction count
 */
function reactionCount($row, $urlCodes)
{
    if ($iD = getValue("CommentID", $row)) {
        $recordType = "comment";
    } elseif ($iD = getValue("ActivityID", $row)) {
        $recordType = "activity";
    } else {
        $recordType = "discussion";
        $iD = getValue("DiscussionID", $row);
    }

    if ($recordType == "activity") {
        $data = getValueR("Data.React", $row, []);
    } else {
        $data = getValueR("Attributes.React", $row, []);
    }

    if (!is_array($data)) {
        return 0;
    }

    $urlCodes = (array) $urlCodes;

    $count = 0;
    foreach ($urlCodes as $urlCode) {
        if (is_array($urlCode)) {
            $count += getValue($urlCode["UrlCode"], $data, 0);
        } else {
            $count += getValue($urlCode, $data, 0);
        }
    }
    return $count;
}

if (!function_exists("ReactionButton")) {
    /**
     * Builds and returns the formated reaction button.
     *
     * @param object $row
     * @param string $urlCode
     * @param array $options
     * @return string
     */
    function reactionButton($row, $urlCode, $options = [])
    {
        $reactionType = ReactionModel::reactionTypes($urlCode);

        $isHeading = val("IsHeading", $options, false);
        if (!$reactionType) {
            $reactionType = ["UrlCode" => $urlCode, "Name" => $urlCode];
            $isHeading = true;
        }

        if (val("Hidden", $reactionType)) {
            return "";
        }

        // Check reaction's permissions
        if ($permissionClass = getValue("Class", $reactionType)) {
            if (!Gdn::session()->checkPermission("Reactions." . $permissionClass . ".Add")) {
                return "";
            }
        }
        if ($permission = getValue("Permission", $reactionType)) {
            if (!Gdn::session()->checkPermission($permission)) {
                return "";
            }
        }

        $name = $reactionType["Name"];
        $label = t($name);
        $spriteClass = getValue("SpriteClass", $reactionType, "React$urlCode");

        if ($iD = getValue("CommentID", $row)) {
            $recordType = "comment";
        } elseif ($iD = getValue("ActivityID", $row)) {
            $recordType = "activity";
        } else {
            $recordType = "discussion";
            $iD = getValue("DiscussionID", $row);
        }

        $count = 0;
        $isFlag = $permissionClass === "Flag" || $urlCode === "Flag";
        $countDisplay =
            !$isFlag ||
            Gdn::config("Reactions.FlagCount.DisplayToUsers", true) ||
            checkPermission("Garden.Moderation.Manage");
        // Don't display counts for Spam or Abuse if you are not a moderator!
        if ($countDisplay) {
            if ($isHeading) {
                static $types = [];
                if (!isset($types[$urlCode])) {
                    $types[$urlCode] = ReactionModel::getReactionTypes(["Class" => $urlCode, "Active" => 1]);
                }

                $count = reactionCount($row, $types[$urlCode]);
            } else {
                if ($recordType == "activity") {
                    $count = getValueR("Data.React.$urlCode", $row, 0);
                } else {
                    $count = getValueR("Attributes.React.$urlCode", $row, 0);
                }
            }
        }
        $countHtml = "";
        $linkClass = "ReactButton-$urlCode";
        if ($count) {
            $countHtml = ' <span class="Count">' . $count . "</span>";
            $linkClass .= " HasCount";
        }
        $linkClass = concatSep(" ", $linkClass, getValue("LinkClass", $options));

        $urlCode2 = strtolower($urlCode);
        if ($isHeading) {
            $url = "#";
            $dataAttr = "";
        } else {
            $url = url("/react/$recordType/$urlCode2?id=$iD");
            $dataAttr = "data-reaction=\"$urlCode2\"";
        }

        $accessibleLabel = htmlspecialchars("{$label} {$recordType}");

        if ($permissionClass && $permissionClass !== "Positive" && !checkPermission("Garden.Moderation.Manage")) {
            $result = <<<EOT
<a class="Hijack ReactButton $linkClass" href="$url" tabindex="0" aria-label="$accessibleLabel" title="$label" rel="nofollow" role="button"><span class="ReactSprite $spriteClass"></span> $countHtml<span class="ReactLabel">$label</span></a>
EOT;
        } else {
            $result = <<<EOT
<a class="Hijack ReactButton $linkClass" href="$url" tabindex="0" aria-label="$accessibleLabel" title="$label" $dataAttr rel="nofollow" role="button"><span class="ReactSprite $spriteClass"></span> $countHtml<span class="ReactLabel">$label</span></a>

EOT;
        }

        return $result;
    }
}

if (!function_exists("ScoreCssClass")) {
    /**
     * Build the score css class.
     *
     * @param object $row
     * @param bool $all
     * @return array|string
     */
    function scoreCssClass($row, $all = false)
    {
        $score = getValue("Score", $row);
        $restored = !is_null(valr("Attributes.DateRestored", $row));
        if (!$score) {
            $score = 0;
        }

        $bury = Gdn::config()->get("Vanilla.Reactions.BuryValue", -5);
        $promote = Gdn::config()->get("Vanilla.Reactions.PromoteValue", 5);

        if ($score <= $bury && !$restored) {
            $result = $all ? "Un-Buried" : "Buried";
        } elseif ($score >= $promote) {
            $result = "Promoted";
        } else {
            $result = "";
        }

        if ($all) {
            return [$result, "Promoted Buried Un-Buried"];
        } else {
            return $result;
        }
    }
}

if (!function_exists("WriteImageItem")) {
    /**
     * Write Image tile items.
     *
     * @param array $record
     * @param string $cssClass
     */
    function writeImageItem($record, $cssClass = "Tile ImageWrap")
    {
        if (val("CategoryCssClass", $record)) {
            $cssClass .= " " . val("CategoryCssClass", $record);
        }
        $attributes = getValue("Attributes", $record);
        if (!is_array($attributes)) {
            $attributes = dbdecode($attributes);
        }

        $image = false;
        if (getValue("Image", $attributes)) {
            $image = [
                "Image" => getValue("Image", $attributes),
                "Thumbnail" => getValue("Thumbnail", $attributes, ""),
                "Caption" => getValue("Caption", $attributes, ""),
                "Size" => getValue("Size", $attributes, ""),
            ];
        }
        $type = false;
        $title = false;
        $body = getValue("Body", $record, "");

        $recordID = getValue("RecordID", $record); // Explicitly defined?
        if ($recordID) {
            $type = $record["RecordType"];
            $name = getValue("Name", $record);
            $url = getValue("Url", $record);
            if ($name && $url) {
                $title = wrap(anchor(Gdn_Format::text($name), $url), "h3", ["class" => "Title"]);
            }
        } else {
            $recordID = getValue("CommentID", $record); // Is it a comment?
            if ($recordID) {
                $type = "Comment";
            }
        }
        if (!$recordID) {
            $recordID = getValue("DiscussionID", $record); // Is it a discussion?
            if ($recordID) {
                $type = "Discussion";
            }
        }

        $wide = false;
        $formattedBody = Gdn_Format::to($body, $record["Format"]);
        if (stripos($formattedBody, '<div class="Video') !== false) {
            $wide = true; // Video?
        } elseif (inArrayI($record["Format"], ["Html", "Text", "Display"]) && strlen($body) > 800) {
            $wide = true; // Lots of text?
        }
        if ($wide) {
            $cssClass .= " Wide";
        }
        ?>
        <div id="<?php echo "{$type}_{$recordID}"; ?>" class="<?php echo $cssClass; ?>">
            <?php
            if ($type == "Discussion" && function_exists("WriteDiscussionOptions")) {
                writeDiscussionOptions();
            } elseif ($type == "Comment" && function_exists("WriteCommentOptions")) {
                $comment = (object) $record;
                writeCommentOptions($comment);
            }

            if ($title) {
                echo $title;
            }

            if ($image) {
                echo '<div class="Image">';
                echo anchor(
                    img($image["Thumbnail"], ["alt" => $image["Caption"], "title" => $image["Caption"]]),
                    $image["Image"],
                    ["target" => "_blank"]
                );
                echo "</div>";
                echo '<div class="Caption">';
                echo Gdn_Format::plainText($image["Caption"]);
                echo "</div>";
            } else {
                echo '<div class="Body Message">';
                echo $formattedBody;
                echo "</div>";
            }
            ?>
            <div class="AuthorWrap">
            <span class="Author">
               <?php
               echo userPhoto($record, ["Px" => "Insert"]);
               echo userAnchor($record, ["Px" => "Insert"]);
               ?>
            </span>
                <?php writeReactions($record); ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists("WriteProfileCounts")) {
    /**
     * Writes the counts under profiles.
     */
    function writeProfileCounts()
    {
        $currentUrl = url("", true);

        echo '<div class="DataCounts">';

        foreach (Gdn::controller()->data("Counts", []) as $key => $row) {
            $itemClass = "CountItem";
            if (stringBeginsWith($currentUrl, $row["Url"])) {
                $itemClass .= " Selected";
            }

            echo ' <span class="CountItemWrap CountItemWrap-' . $key . '"><span class="' . $itemClass . '">';

            if ($row["Url"]) {
                echo '<a href="' . htmlspecialchars($row["Url"]) . '" class="TextColor" rel="nofollow">';
            }

            echo ' <span class="CountTotal">' . Gdn_Format::bigNumber($row["Total"], "html") . "</span> ";
            echo ' <span class="CountLabel">' . t($row["Name"]) . "</span>";

            if ($row["Url"]) {
                echo "</a>";
            }

            echo "</span></span> ";
        }

        echo "</div>";
    }
}

if (!function_exists("WriteRecordReactions")) {
    /**
     * Writes reactions on a post.
     *
     * @param object $row
     */
    function writeRecordReactions($row)
    {
        $userTags = getValue("UserTags", $row, []);
        if (empty($userTags)) {
            return;
        }

        $recordReactions = "";
        foreach ($userTags as $tag) {
            $user = Gdn::userModel()->getID($tag["UserID"], DATASET_TYPE_ARRAY);
            if (!$user) {
                continue;
            }

            $reactionType = ReactionModel::fromTagID($tag["TagID"]);
            $skipReaction = $reactionType["Class"] !== "Positive" && !checkPermission("Garden.Moderation.Manage");
            if (!$reactionType || $reactionType["Hidden"] || $skipReaction) {
                continue;
            }
            $urlCode = $reactionType["UrlCode"];
            $spriteClass = getValue("SpriteClass", $reactionType, "React$urlCode");
            $title = sprintf(
                "%s - %s on %s",
                $user["Name"],
                t($reactionType["Name"]),
                Gdn_Format::dateFull($tag["DateInserted"])
            );

            $userPhoto = userPhoto($user, ["Size" => "Small", "Title" => $title]);
            if ($userPhoto == "") {
                continue;
            }

            $recordReactions .=
                '<span class="UserReactionWrap" title="' .
                htmlspecialchars($title) .
                '" data-userid="' .
                getValue("UserID", $user) .
                '">' .
                $userPhoto .
                "<span class=\"ReactSprite $spriteClass\"></span>" .
                "</span>";
        }

        if ($recordReactions != "") {
            echo '<div class="RecordReactions">' . $recordReactions . "</div>";
        }
    }
}

if (!function_exists("reactionFilterButton")) {
    function reactionFilterButton($name, $code, $currentReactionType)
    {
        $lCode = strtolower($code);
        $url = url("/bestof/$lCode");
        $imgSrc = "https://badges.v-cdn.net/reactions/50/$lCode.png";
        $cssClass = "";
        if ($currentReactionType == $lCode) {
            $cssClass .= " Selected";
        }

        $result = <<<EOT
<div class="CountItemWrap">
<div class="CountItem$cssClass">
   <a href="$url">
      <span class="CountTotal"><img src="$imgSrc" loading="lazy" /></span>
      <span class="CountLabel">$name</span>
   </a>
</div>
</div>
EOT;

        return $result;
    }
}
