<?php
use Nbbc\BBcode as BBcode;

class Nbbc extends Gdn_Pluggable {
    protected $library;

    protected $media;

    protected $nbbc;

    public function __construct() {
        parent::__construct();
    }

    /**
     * @param object $bbcode The object doing the parsing.
     * @param int $action The current action being performed on the tag.
     * @param string $name The name of the tag.
     * @param string $default The default value passed to the tag in the form: `[tag=default]`.
     * @param array $params All of the parameters passed to the tag.
     * @param string $content The content of the tag. Only available when $action is BBCODE_OUTPUT.
     * @return string
     */
    public function doAttachment($bbcode, $action, $name, $default, $params, $content) {
        $medias = $this->media();
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
     * @param object $bbcode Instance of NBBC parsing
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag
     * @param string $default Value of the _default parameter, from the $params array
     * @param array $params A standard set parameters related to the tag
     * @param string $content Value between the open and close tags, if any
     * @return string Formatted value
     */
    function doImage($bbcode, $action, $name, $default, $params, $content) {
        if ($action == BBCODE_CHECK)
            return true;
        $content = trim($bbcode->UnHTMLEncode(strip_tags($content)));
        if (!$content && $default)
            $content = $default;

        if ($bbcode->IsValidUrl($content, false))
            return "<img src=\"" . htmlspecialchars($content) . "\" alt=\""
            . htmlspecialchars(basename($content)) . "\" class=\"bbcode_img\" />";

        return htmlspecialchars($params['_tag']) . htmlspecialchars($content) . htmlspecialchars($params['_endtag']);
    }

    /**
     * @param object $bbcode Instance of NBBC parsing
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag
     * @param string $default Value of the _default parameter, from the $params array
     * @param array $params A standard set parameters related to the tag
     * @param string $content Value between the open and close tags, if any
     * @return bool|string
     */
    function doQuote($bbcode, $action, $name, $default, $params, $content) {
        if ($action == BBcode::BBCODE_CHECK) {
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

            $User = Gdn::UserModel()->GetByUsername($username);
            if ($User)
                $UserAnchor = UserAnchor($User);
            else
                $UserAnchor = Anchor(htmlspecialchars($username, NULL, 'UTF-8'), '/profile/' . rawurlencode($username));

            $title = ConcatSep(' ', $title, $UserAnchor, T('Quote wrote', 'wrote'));
        }

        if (isset($params['date']))
            $title = ConcatSep(' ', $title, T('Quote on', 'on'), htmlspecialchars(trim($params['date'])));

        if ($title)
            $title = $title . ':';

        if (isset($params['url'])) {
            $url = trim($params['url']);

            if (is_numeric($url))
                $url = "/discussion/comment/$url#Comment_{$url}";
            elseif (!$bbcode->IsValidURL($url))
                $url = '';

            if ($url)
                $title = ConcatSep(' ', $title, Anchor('<span class="ArrowLink">»</span>', $url, ['class' => 'QuoteLink']));
        }

        if ($title)
            $title = "<div class=\"QuoteAuthor\">$title</div>";

        return "\n<blockquote class=\"Quote UserQuote\">\n"
        . $title . "\n<div class=\"QuoteText\">"
        . $content . "</div>\n</blockquote>\n";
    }

    /**
     * Perform formatting against a string for the size tag
     *
     * @param object $bbcode Instance of NBBC parsing
     * @param int $action Value of one of NBBC's defined constants.  Typically, this will be BBCODE_CHECK.
     * @param string $name Name of the tag
     * @param string $default Value of the _default parameter, from the $params array
     * @param array $params A standard set parameters related to the tag
     * @param string $content Value between the open and close tags, if any
     * @return string Formatted value
     */
    public function doSize($bbcode, $action, $name, $default, $params, $content) {
        // px and em are invalid modifiers for this value.  Lose 'em.
        $default = preg_replace('/(px|em)/i', '', $default);

        switch ($default) {
            case '0': $size = '.5em'; break;
            case '1': $size = '.67em'; break;
            case '2': $size = '.83em'; break;
            default:
            case '3': $size = '1.0em'; break;
            case '4': $size = '1.17em'; break;
            case '5': $size = '1.5em'; break;
            case '6': $size = '2.0em'; break;
            case '7': $size = '2.5em'; break;
        }

        return "<span style=\"font-size:$size\">$content</span>";
    }

    /**
     * @param object $bbcode The object doing the parsing.
     * @param int $action The current action being performed on the tag.
     * @param string $name The name of the tag.
     * @param string $default The default value passed to the tag in the form: `[tag=default]`.
     * @param array $params All of the parameters passed to the tag.
     * @param string $content The content of the tag. Only available when $action is BBCODE_OUTPUT.
     * @return string
     */
    function doURL($bbcode, $action, $name, $default, $params, $content) {
        if ($action == BBcode::BBCODE_CHECK) return true;

        $url = is_string($default) ? $default : $bbcode->UnHTMLEncode(strip_tags($content));

        if ($bbcode->IsValidURL($url)) {
            if ($bbcode->debug)
                print "ISVALIDURL<br />";
            if ($bbcode->url_targetable !== false && isset($params['target']))
                $target = " target=\"" . htmlspecialchars($params['target']) . "\"";
            else
                $target = "";

            if ($bbcode->url_target !== false)
                if (!($bbcode->url_targetable == 'override' && isset($params['target'])))
                    $target = " target=\"" . htmlspecialchars($bbcode->url_target) . "\"";
            return '<a href="' . htmlspecialchars($url) . '" rel="nofollow" class="bbcode_url"' . $target . '>' . $content . '</a>';
        } else
            return htmlspecialchars($params['_tag']) . $content . htmlspecialchars($params['_endtag']);
    }

    /**
     * @param Nbbc\BBcode $bbcode The object doing the parsing.
     * @param int $action The current action being performed on the tag.
     * @param string $name The name of the tag.
     * @param string $default The default value passed to the tag in the form: `[tag=default]`.
     * @param array $params All of the parameters passed to the tag.
     * @param string $content The content of the tag. Only available when $action is BBCODE_OUTPUT.
     * @return string
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
     * @param Nbbc\BBcode $bbcode The object doing the parsing.
     * @param int $action The current action being performed on the tag.
     * @param string $name The name of the tag.
     * @param string $default The default value passed to the tag in the form: `[tag=default]`.
     * @param array $params All of the parameters passed to the tag.
     * @param string $content The content of the tag. Only available when $action is BBCODE_OUTPUT.
     * @return bool|string
     */
    function doYoutube($bbcode, $action, $name, $default, $params, $content) {
        if ($action == BBcode::BBCODE_CHECK) {
            return true;
        }

        $videoId = is_string($default) ? $default : $bbcode->unHTMLEncode(strip_tags($content));

        return "<div class=\"Video P\"><iframe width=\"560\" height=\"315\" src=\"http://www.youtube.com/embed/{$videoId}\" frameborder=\"0\" allowfullscreen></iframe></div>";
    }

    /**
     * @param string $bbcode
     * @return string
     */
    public function format($bbcode) {
        $bbcode = str_replace(
            ['[CODE]', '[/CODE]'],
            ['[code]', '[/code]'],
            $bbcode
        );

        return $this->nbbc()->parse($bbcode);
    }

    public function media() {
        if ($this->media === null) {
            try {
                $i = Gdn::pluginManager()->getPluginInstance('FileUploadPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
                $m = $i->mediaCache();
            } catch (Exception $ex) {
                $m = [];
            }

            $media = [];
            foreach ($m as $key => $data) {
                foreach ($data as $row) {
                    $media[$row->MediaID] = $row;
                }
            }
            $this->media = $media;
        }
        return $this->media;
    }

    /**
     *
     * @return BBCode
     */
    public function NBBC() {
        if ($this->nbbc === null) {
            $bbcode = new BBCode;
            $bbcode->setEnableSmileys(false);
            $bbcode->setAllowAmpersand(true);

            $bbcode->addRule('attach', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'         => 'image',
                'content'       => BBcode::BBCODE_REQUIRED,
                'end_tag'       => BBcode::BBCODE_REQUIRED,
                'method'        => [$this, 'doAttachment'],
                'mode'          => BBcode::BBCODE_MODE_CALLBACK,
                'plain_content' => [],
                'plain_start'   => "[image]"
            ]);

            $bbcode->addRule('attachment', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'         => 'image',
                'content'       => BBcode::BBCODE_REQUIRED,
                'end_tag'       => BBcode::BBCODE_REQUIRED,
                'method'        => [$this, 'removeAttachment'],
                'mode'          => BBcode::BBCODE_MODE_CALLBACK,
                'plain_content' => [],
                'plain_start'   => "[image]"
            ]);

            $bbcode->addRule('code', [
                'after_endtag'  => "sns",
                'after_tag'     => "sn",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sn",
                'before_tag'    => "sns",
                'class'         => 'code',
                'content'       => BBcode::BBCODE_VERBATIM,
                'mode' => BBcode::BBCODE_MODE_ENHANCED,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Code:</b>\n",
                'template'      => "\n<pre>{\$_content/v}\n</pre>\n"
            ]);

            $bbcode->addRule('hr', [
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

            $bbcode->addRule('img', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'         => 'image',
                'content'       => BBcode::BBCODE_REQUIRED,
                'end_tag'       => BBcode::BBCODE_REQUIRED,
                'method'        => [$this, 'doImage'],
                'mode'          => BBcode::BBCODE_MODE_CALLBACK,
                'plain_content' => [],
                'plain_start'   => "[image]"
            ]);

            $bbcode->addRule('quote', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'method'        => [$this, 'doQuote'],
                'mode'          => BBcode::BBCODE_MODE_CALLBACK,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Quote:</b>\n"
            ]);

            // The original NBBC rule was copied here and the regex was updated to meet our new criteria.
            $bbcode->addRule('size', [
                'allow'    => ['_default' => '/^[0-9.]+(em|px)?$/D'],
                'allow_in' => ['listitem', 'block', 'columns', 'inline', 'link'],
                'class'    => 'inline',
                'method'   => [$this, 'doSize'],
                'mode'     => BBcode::BBCODE_MODE_CALLBACK
            ]);

            $bbcode->addRule('snapback', [
                'after_endtag'  => "sns",
                'after_tag'     => "sn",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sn",
                'before_tag'    => "sns",
                'class'         => 'code',
                'content'       => BBcode::BBCODE_VERBATIM,
                'mode'          => BBcode::BBCODE_MODE_ENHANCED,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Snapback:</b>\n",
                'template'      => ' <a href="'.Url('/discussion/comment/{$_content/v}#Comment_{$_content/v}', TRUE).'" class="SnapBack">»</a> '
            ]);

            $bbcode->addRule('spoiler', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'plain_end'     => "\n",
                'plain_start'   => "\n",
                'simple_end'    => "</div></div>\n",
                'simple_start'  => "\n" . '<div class="UserSpoiler"><div class="SpoilerTitle">' . t('Spoiler') . ': </div><div class="SpoilerReveal"></div><div class="SpoilerText" style="display: none;">'
            ]);

            $bbcode->addRule('video', [
                'after_endtag'  => "sns",
                'after_tag'     => "sns",
                'allow_in'      => ['listitem', 'block', 'columns'],
                'before_endtag' => "sns",
                'before_tag'    => "sns",
                'method'        => [$this, 'doVideo'],
                'mode'          => BBcode::BBCODE_MODE_CALLBACK,
                'plain_end'     => "\n",
                'plain_start'   => "\n<b>Video:</b>\n",
            ]);

            $bbcode->addRule('youtube', [
                'allow_in'      => ['listitem', 'block', 'columns', 'inline'],
                'class'         => 'link',
                'content'       => BBcode::BBCODE_REQUIRED,
                'method'        => [$this, 'doYouTube'],
                'mode'          => BBcode::BBCODE_MODE_CALLBACK,
                'plain_content' => ['_content', '_default'],
                'plain_end'     => "\n",
                'plain_link'    => ['_default', '_content'],
                'plain_start'   => "\n<b>Video:</b>\n"
            ]);

            // Prevent unsupported tags from displaying
            $bbcode->addRule('table', []);
            $bbcode->addRule('tr', []);
            $bbcode->addRule('td', []);

            // Firing event as NBBCPlugin for compatibility with legacy NBBC plug-in hooks.
            $this->eventArguments['BBCode'] = $bbcode;
            $this->fireAs('NBBCPlugin')->fireEvent('AfterNBBCSetup');
            $this->nbbc = $bbcode;
        }

        return $this->nbbc;
    }

    /**
     * @return string
     */
    public function removeAttachment() {
        // We dont need this since we show attachments.
        return '<!-- phpBB Attachments -->';
    }
}
