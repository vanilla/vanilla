<?php
use Nbbc\BBcode as Nbbc;

class BBCode extends Gdn_Pluggable {

    /**
     * @var array A list of records from the Media table, indexed by MediaID.
     */
    protected $media;

    /**
     * @var Nbbc An instance of Nbbc\BBcode.
     */
    protected $nbbc;

    /**
     * BBCode constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Perform formatting against a string for the attach tag.
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return string Formatted value.
     */
    public function doAttachment($bbcode, $action, $name, $default, $params, $content) {
        $medias  = $this->media();
        $mediaID = $content;

        if (isset($medias[$mediaID])) {
            $media = $medias[$mediaID];

            $src = htmlspecialchars(Gdn_Upload::Url(val('Path', $media)));
            $name = htmlspecialchars(val('Name', $media));

            if (val('ImageWidth', $media)) {
                return "<div class=\"Attachment Image\"><img src=\"{$src}\" alt=\"{$name}\" /></div>";
            } else {
                return Anchor($name, $src, 'Attachment File');
            }
        }

        return anchor(t('Attachment not found.'), '#', 'Attachment NotFound');
    }

    /**
     * Perform formatting against a string for the img tag.
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return bool|string Formatted value.
     */
    function doImage($bbcode, $action, $name, $default, $params, $content) {
        if ($action == Nbbc::BBCODE_CHECK) {
            return true;
        }

        $content = trim($bbcode->unHTMLEncode(strip_tags($content)));

        if (!$content && $default) {
            $content = $default;
        }

        if ($bbcode->IsValidUrl($content, false)) {
            return "<img src=\"" . htmlspecialchars($content) . "\" alt=\""
            . htmlspecialchars(basename($content)) . "\" class=\"bbcode_img\" />";
        }

        return htmlspecialchars($params['_tag']) . htmlspecialchars($content) . htmlspecialchars($params['_endtag']);
    }

    /**
     * Perform formatting against a string for the quote tag.
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return bool|string Formatted value.
     */
    function doQuote($bbcode, $action, $name, $default, $params, $content) {
        if ($action == Nbbc::BBCODE_CHECK) {
            return true;
        }

        if (is_string($default)) {
            $defaultParts = explode(';', $default); // support vbulletin style quoting.
            $Url = array_pop($defaultParts);
            if (count($defaultParts) == 0) {
                $params['name'] = $Url;
            } else {
                $params['name'] = implode(';', $defaultParts);
                $params['url'] = $Url;
            }
        }

        $title = '';

        if (isset($params['name'])) {
            $username = trim($params['name']);
            $username = html_entity_decode($username, ENT_QUOTES, 'UTF-8');

            $User = Gdn::userModel()->getByUsername($username);
            if ($User) {
                $userAnchor = userAnchor($User);
            } else {
                $userAnchor = anchor(htmlspecialchars($username, null, 'UTF-8'), '/profile/' . rawurlencode($username));
            }

            $title = concatSep(' ', $title, $userAnchor, T('Quote wrote', 'wrote'));
        }

        if (isset($params['date'])) {
            $title = concatSep(' ', $title, T('Quote on', 'on'), htmlspecialchars(trim($params['date'])));
        }

        if ($title) {
            $title = $title . ':';
        }

        if (isset($params['url'])) {
            $url = trim($params['url']);

            if (is_numeric($url)) {
                $url = "/discussion/comment/$url#Comment_{$url}";
            } elseif (!$bbcode->isValidURL($url)) {
                $url = '';
            }

            if ($url) {
                $title = concatSep(' ', $title, anchor('<span class="ArrowLink">»</span>', $url, ['class' => 'QuoteLink']));
            }
        }

        if ($title) {
            $title = "<div class=\"QuoteAuthor\">$title</div>";
        }

        return "\n<blockquote class=\"Quote UserQuote\">\n{$title}\n<div class=\"QuoteText\">{$content}</div>\n</blockquote>\n";
    }

    /**
     * Perform formatting against a string for the size tag.
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return string Formatted value.
     */
    public function doSize($bbcode, $action, $name, $default, $params, $content) {
        // px and em are invalid modifiers for this value.  Lose 'em.
        $default = preg_replace('/(px|em)/i', '', $default);

        switch ($default) {
            case '0':
                $size = '.5em';
                break;
            case '1':
                $size = '.67em';
                break;
            case '2':
                $size = '.83em';
                break;
            default:
            case '3':
                $size = '1.0em';
                break;
            case '4':
                $size = '1.17em';
                break;
            case '5':
                $size = '1.5em';
                break;
            case '6':
                $size = '2.0em';
                break;
            case '7':
                $size = '2.5em';
                break;
        }

        return "<span style=\"font-size:{$size}\">{$content}</span>";
    }

    /**
     * Perform formatting against a string for the url tag.
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return bool|string Formatted value.
     */
    function doURL($bbcode, $action, $name, $default, $params, $content) {
        if ($action == Nbbc::BBCODE_CHECK) {
            return true;
        }

        $url = is_string($default) ? $default : $bbcode->unHTMLEncode(strip_tags($content));

        if ($bbcode->isValidURL($url)) {
            if ($bbcode->getDebug()) {
                print "ISVALIDURL<br />";
            }

            if ($bbcode->getURLTargetable() !== false && isset($params['target'])) {
                $target = " target=\"" . htmlspecialchars($params['target']) . "\"";
            }
            else {
                $target = "";
            }

            if ($bbcode->getURLTarget() !== false) {
                if (!($bbcode->getURLTargetable() == 'override' && isset($params['target']))) {
                    $target = " target=\"" . htmlspecialchars($bbcode->getURLTarget()) . "\"";
                }
            }

            $encodedUrl = htmlspecialchars($url);
            return "<a href=\"{$encodedUrl}\" rel=\"nofollow\" class=\"bbcode_url\"{$target}\">{$content}</a>";
        } else {
            return htmlspecialchars($params['_tag']) . $content . htmlspecialchars($params['_endtag']);
        }
    }

    /**
     * Perform formatting against a string for the video tag.
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return string Formatted value.
     */
    function doVideo($bbcode, $action, $name, $default, $params, $content) {
        list($width, $height) = Gdn_Format::getEmbedSize();
        list($type, $code) = explode(';', $default);
        switch ($type) {
            case 'youtube':
                return "<div class=\"Video P\"><iframe width=\"{$width}\" height=\"{$height}\" src=\"http://www.youtube.com/embed/{$code}\" frameborder=\"0\" allowfullscreen></iframe></div>";
            default:
                return $content;
        }
    }

    /**
     * Perform formatting against a string for the youtube tag
     *
     * @param Nbbc $bbcode Instance of Nbbc doing the parsing.
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag.
     * @param string $default Value of the _default parameter, from the $params array.
     * @param array $params A standard set parameters related to the tag.
     * @param string $content Value between the open and close tags, if any.
     * @return bool|string Formatted value.
     */
    function doYoutube($bbcode, $action, $name, $default, $params, $content) {
        if ($action == Nbbc::BBCODE_CHECK) {
            return true;
        }

        $videoId = is_string($default) ? $default : $bbcode->unHTMLEncode(strip_tags($content));

        return "<div class=\"Video P\"><iframe width=\"560\" height=\"315\" src=\"http://www.youtube.com/embed/{$videoId}\" frameborder=\"0\" allowfullscreen></iframe></div>";
    }

    /**
     * Parse the provided BBCode into an HTML string.
     *
     * @param string $bbcode Raw BBCode.
     * @return string HTML code, generated from the provided BBCode.
     */
    public function format($bbcode) {
        $bbcode = str_replace(
            ['[CODE]', '[/CODE]'],
            ['[code]', '[/code]'],
            $bbcode
        );

        return $this->nbbc()->parse($bbcode);
    }

    /**
     * Build and return a list of attachments for the current page.
     *
     * @return array
     */
    public function media() {
        if ($this->media === null) {
            $controller    = Gdn::controller();
            $commentIDList = [];
            $comments      = $controller->data('Comments');
            $discussionID  = $controller->data('Discussion.DiscussionID');
            $mediaArray = [];

            // If we have comments, iterate through them and build an array of their IDs.
            if ($comments instanceof Gdn_DataSet && $comments->numRows()) {
                $comments->dataSeek(-1);
                while ($comment = $comments->nextRow()) {
                    $commentIDList[] = $comment->CommentID;
                }
            } elseif (isset($controller->Discussion) && $controller->Discussion) {
                $commentIDList[] = $controller->DiscussionID = $controller->Discussion->DiscussionID;
            }

            if (isset($controller->Comment) && isset($controller->Comment->CommentID)) {
                $commentIDList[] = $controller->Comment->CommentID;
            }

            $this->fireEvent('BeforePreloadDiscussionMedia');

            $mediaQuery = Gdn::sql()
                ->select('m.*')
                ->from('Media m')
                ->beginWhereGroup()
                ->where('m.ForeignID', $discussionID)
                ->where('m.ForeignTable', 'discussion')
                ->endWhereGroup();

            if (count($commentIDList)) {
                $mediaQuery->orOp()
                    ->beginWhereGroup()
                    ->whereIn('m.ForeignID', $commentIDList)
                    ->where('m.ForeignTable', 'comment')
                    ->endWhereGroup();
            }

            $mediaResult  = $mediaQuery->get()->result();

            if ($mediaResult) {
                foreach ($mediaResult as $media) {
                    $mediaArray[$media->MediaID] = $media;
                }
            }

            $this->media = $mediaArray;
        }

        return $this->media;
    }

    /**
     * Create, configure and return an instance of Nbbc\BBcode.
     *
     * @return Nbbc
     */
    public function nbbc() {
        if ($this->nbbc === null) {
            $nbbc = new Nbbc();
            $nbbc->setEnableSmileys(false);
            $nbbc->setAllowAmpersand(true);

            $nbbc->addRule('attach', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'         => "image",
                'content'       => Nbbc::BBCODE_REQUIRED,
                'end_tag'       => Nbbc::BBCODE_REQUIRED,
                'method'        => [$this, 'doAttachment'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_content' => [],
                'plain_start'   => "[image]"
            ]);

            $nbbc->addRule('attachment', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'         => "image",
                'content'       => Nbbc::BBCODE_REQUIRED,
                'end_tag'       => Nbbc::BBCODE_REQUIRED,
                'method'        => [$this, 'removeAttachment'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_content' => [],
                'plain_start'   => "[image]"
            ]);

            $nbbc->addRule('code', [
                'after_endtag'  => "sns",
                'after_tag'     => "sn",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sn",
                'before_tag'    => "sns",
                'class'         => 'code',
                'content'       => Nbbc::BBCODE_VERBATIM,
                'mode'          => Nbbc::BBCODE_MODE_ENHANCED,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Code:</b>\n",
                'template'      => "\n<pre>{\$_content/v}\n</pre>\n"
            ]);

            $nbbc->addRule('hr', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'plain_end'     => "\n",
                'plain_start'   => "\n",
                'simple_end'    => "",
                'simple_start'  => ""
            ]);

            $nbbc->addRule('img', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'         => "image",
                'content'       => Nbbc::BBCODE_REQUIRED,
                'end_tag'       => Nbbc::BBCODE_REQUIRED,
                'method'        => [$this, 'doImage'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_content' => [],
                'plain_start'   => "[image]"
            ]);

            $nbbc->addRule('quote', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'method'        => [$this, 'doQuote'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Quote:</b>\n"
            ]);

            // The original NBBC rule was copied here and the regex was updated to meet our new criteria.
            $nbbc->addRule('size', [
                'allow'    => ['_default' => '/^[0-9.]+(em|px)?$/D'],
                'allow_in' => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'    => "inline",
                'method'   => [$this, 'doSize'],
                'mode'     => Nbbc::BBCODE_MODE_CALLBACK
            ]);

            $nbbc->addRule('snapback', [
                'after_endtag'  => "sns",
                'after_tag'     => "sn",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sn",
                'before_tag'    => "sns",
                'class'         => 'code',
                'content'       => Nbbc::BBCODE_VERBATIM,
                'mode'          => Nbbc::BBCODE_MODE_ENHANCED,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Snapback:</b>\n",
                'template'      => ' <a href="'.Url('/discussion/comment/{$_content/v}#Comment_{$_content/v}', true).'" class="SnapBack">»</a> '
            ]);

            $nbbc->addRule('spoiler', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'plain_end'     => "\n",
                'plain_start'   => "\n",
                'simple_end'    => "</div>\n",
                'simple_start'  => "\n<div class=\"Spoiler\">"
            ]);

            $nbbc->addRule('url', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline'],
                'class'         => "link",
                'content'       => Nbbc::BBCODE_REQUIRED,
                'method'        => [$this, 'doURL'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_content' => ['_content', '_default'],
                'plain_end'     => "</a>",
                'plain_link'    => ['_default', '_content'],
                'plain_start'   => "<a rel=\"nofollow\" href=\"{\$link}\">"
            ]);

            $nbbc->addRule('video', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'method'        => [$this, 'doVideo'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Video:</b>\n",
            ]);

            $nbbc->addRule('youtube', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline'],
                'class'         => "link",
                'content'       => Nbbc::BBCODE_REQUIRED,
                'method'        => [$this, 'doYouTube'],
                'mode'          => Nbbc::BBCODE_MODE_CALLBACK,
                'plain_content' => ['_content', '_default'],
                'plain_end'     => "\n",
                'plain_link'    => ['_default', '_content'],
                'plain_start'   => "\n<b>Video:</b>\n"
            ]);

            // Prevent unsupported tags from displaying
            $nbbc->addRule('table', []);
            $nbbc->addRule('tr', []);
            $nbbc->addRule('td', []);

            $this->eventArguments['BBCode'] = $nbbc;
            $this->fireEvent('AfterBBCodeSetup');
            $this->nbbc = $nbbc;
        }

        return $this->nbbc;
    }

    /**
     * Custom handler for the attachment tag.
     *
     * @return string
     */
    public function removeAttachment() {
        // We dont need this since we show attachments.
        return '<!-- phpBB Attachments -->';
    }
}
