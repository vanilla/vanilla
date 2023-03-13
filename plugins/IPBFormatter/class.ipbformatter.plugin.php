<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use IPBFormatter\Formats\IPBFormat;
use IPBFormatter\Formatter;
use Psr\Container\ContainerInterface;
use Vanilla\Formatting\FormatService;

class IPBFormatterPlugin extends Gdn_Plugin
{
    /**
     *
     * @var BBCode
     */
    protected $_NBBC;

    /** @var null  */
    protected $_Media = null;

    /**
     *
     *
     * @param $string
     * @return mixed|string
     */
    public function format($string)
    {
        $string = str_replace(["&quot;", "&#39;", "&#58;", "Â"], ['"', "'", ":", ""], $string);
        $string = str_replace("<#EMO_DIR#>", "default", $string);
        $string = str_replace("<{POST_SNAPBACK}>", '<span class="SnapBack">»</span>', $string);

        // There is an issue with using uppercase code blocks, so they're forced to lowercase here
        $string = str_replace(["[CODE]", "[/CODE]"], ["[code]", "[/code]"], $string);

        /**
         * IPB inserts line break markup tags at line breaks.  They need to be removed in code blocks.
         * The original newline/line break should be left intact, so whitespace will be preserved in the pre tag.
         */
        $string = preg_replace_callback(
            "/\[code\].*?\[\/code\]/is",
            function ($codeBlocks) {
                return str_replace(["<br />"], [""], $codeBlocks[0]);
            },
            $string
        );

        /**
         * IPB formats some quotes as HTML.  They're converted here for the sake of uniformity in presentation.
         * Attribute order seems to be standard.  Spacing between the opening of the tag and the first attribute is variable.
         */
        $string = preg_replace_callback(
            '#<blockquote\s+(class="ipsBlockquote" )?data-author="([^"]+)" data-cid="(\d+)" data-time="(\d+)">(.*?)</blockquote>#is',
            function ($blockQuotes) {
                $author = $blockQuotes[2];
                $cid = $blockQuotes[3];
                $time = $blockQuotes[4];
                $quoteContent = $blockQuotes[5];

                // $Time will over as a timestamp. Convert it to a date string.
                $date = date("F j Y, g:i A", $time);

                return "[quote name=\"{$author}\" url=\"{$cid}\" date=\"{$date}\"]{$quoteContent}[/quote]";
            },
            $string
        );

        // If there is a really long string, it could cause a stack overflow in the bbcode parser.
        // Not much we can do except try and chop the data down a touch.

        // 1. Remove html comments.
        $string = preg_replace("/<!--(.*)-->/Uis", "", $string);

        // 2. Split the string up into chunks.
        $strings = (array) $string;
        $result = "";
        foreach ($strings as $string) {
            $result .= $this->nbbc()->parse($string);
        }

        // Linkify URLs in content
        $result = Gdn_Format::links($result);

        // Parsing mentions
        $result = Gdn_Format::mentions($result);

        // Handling emoji
        $result = Emoji::instance()->translateToHtml($result);

        // Make sure to clean filter the html in the end.
        $config = [
            "anti_link_spam" => ["`.`", ""],
            "comment" => 1,
            "cdata" => 1,
            "css_expression" => 1,
            "deny_attribute" => "on*",
            "elements" => "*-applet-form-input-textarea-iframe-script-style",
            // object, embed allowed
            "keep_bad" => 0,
            "schemes" =>
                "classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https",
            // clsid allowed in class
            "valid_xml" => 2,
        ];

        $spec = "object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)";
        $result = Htmlawed::filter($result, $config, $spec);

        return $result;
    }

    /**
     * @return BBCode;
     */
    public function nbbc()
    {
        if ($this->_NBBC === null) {
            $plugin = new BBCode();

            $this->_NBBC = $plugin->nbbc();
            $this->_NBBC->setIgnoreNewlines(true);

            $this->_NBBC->addRule("attachment", [
                "mode" => Nbbc\BBCode::BBCODE_MODE_CALLBACK,
                "method" => [$this, "DoAttachment"],
                "class" => "image",
                "allow_in" => ["listitem", "block", "columns", "inline", "link"],
                "end_tag" => Nbbc\BBCode::BBCODE_PROHIBIT,
                "content" => Nbbc\BBCode::BBCODE_PROHIBIT,
                "plain_start" => "[image]",
                "plain_content" => [],
            ]);
        }

        return $this->_NBBC;
    }

    /**
     * Build an array of attachment records from the Media table, using the MediaID as each record's index.
     *
     * @return array|null
     */
    public function media()
    {
        if ($this->_Media === null) {
            // Set _Media to a non-null value, so we only do this once.
            $this->_Media = [];

            // Fire up a basic model, configured for the Media table
            $mediaModel = new Gdn_Model("Media");

            // Grab a reference to the instance of the current controller
            $controller = Gdn::controller();

            // Grab the current discussion ID from the current controller
            $discussionID = $controller->data("Discussion.DiscussionID");

            // Grab comment data set from the current controller and verify it's populated
            $comments = $controller->data("Comments");
            $commentIDs = [];
            if ($comments instanceof Gdn_DataSet && $comments->numRows()) {
                // Build a collection of comment IDs
                while ($currentComment = $comments->nextRow()) {
                    $commentIDs[] = $currentComment->CommentID;
                }
                unset($currentComment);
            }

            /**
             * Select all media records with a ForeignID matching the discussion ID and a
             * ForeignTable value of "discussion"
             */
            $mediaStatement = $mediaModel->SQL
                ->select("m.*")
                ->from("Media m")
                ->beginWhereGroup()
                ->where("m.ForeignID", $discussionID)
                ->where("m.ForeignTable", "discussion")
                ->endWhereGroup();

            // If we have any comment IDs, find attachments related to them too
            if (!empty($commentIDs)) {
                $mediaStatement
                    ->orOp()
                    ->beginWhereGroup()
                    ->whereIn("m.ForeignID", $commentIDs)
                    ->where("m.ForeignTable", "comment")
                    ->endWhereGroup();
            }

            // Execute the statement and get the results
            $mediaRows = $mediaStatement->get();

            /**
             * Verify $mediaRows is a valid data set and that it is populated.  Next, iterate through the results and
             * insert elements into our _Media array, using each row's MediaID as the index.
             */
            if ($mediaRows instanceof Gdn_DataSet && $mediaRows->numRows()) {
                foreach ($mediaRows->result() as $currentMedia) {
                    $this->_Media[$currentMedia->MediaID] = $currentMedia;
                }
                unset($currentMedia);
            }
        }

        return $this->_Media;
    }

    /**
     *
     *
     * @param $bbcode
     * @param $action
     * @param $name
     * @param $default
     * @param $params
     * @param $content
     * @return string
     */
    public function doAttachment($bbcode, $action, $name, $default, $params, $content)
    {
        $medias = $this->media();
        $parts = explode(":", $default);
        $mediaID = $parts[0];
        if (isset($medias[$mediaID])) {
            $media = $medias[$mediaID];

            $src = htmlspecialchars(Gdn_Upload::url(val("Path", $media)));
            $name = htmlspecialchars(val("Name", $media));
            if (val("ImageWidth", $media)) {
                return <<<EOT
<div class="Attachment Image"><img src="$src" alt="$name" /></div>
EOT;
            } else {
                return anchor($name, $src, "Attachment File");
            }
        }

        return "";
    }

    /**
     * Hooks into the GetFormats event from the Advanced Editor plug-in and adds the IPB format.
     *
     * @param $sender Instance of EditorPlugin firing the event
     */
    public function editorPlugin_getFormats_handler($sender, $args)
    {
        $formats = &$args["formats"];

        $formats[] = "IPB";
    }

    /**
     * Hooks into the GetJSDefinitions event from the Advanced Editor plug-in and adds definitions related to
     * the IPB format.
     *
     * @param $sender Instance of EditorPlugin firing the event
     */
    public function editorPlugin_getJSDefinitions_handler($sender, $args)
    {
        $definitions = &$args["definitions"];

        /**
         * There isn't any currently known help text for the IPB format, so it's an empty string.
         * If that changes, it can be added in the locale or changed here.
         */
        $definitions["ipbHelpText"] = t("editor.ipbHelpText", "");
    }
}
