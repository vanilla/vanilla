<?php

/**
 * Class EmailTemplate
 *
 * Compiles the data for an email, applies appropriate content filters and renders the email.
 *
 */
class EmailTemplate extends Gdn_Pluggable {

    /**
     * Delimiter for plaintext email.
     */
    const PLAINTEXT_START = '<!-- //TEXT VERSION FOLLOWS//';

    /**
     * @var string The HTML formatted email title.
     */
    protected $title;
    /**
     * @var string The HTML formatted email lead (sub-title, appears under title).
     */
    protected $lead;
    /**
     * @var string The HTML formatted email message (the body of the email).
     */
    protected $message;
    /**
     * @var string The HTML formatted email footer.
     */
    protected $footer;
    /**
     * @var array An array representing a link with the following keys:
     * 'url' => The href value of the link.
     * 'text' => The link text.
     * 'color' => The hex color code of the link, must include the leading '#'.
     */
    protected $link;
    /**
     * @var array An array representing a button with the following keys:
     * 'url' => The href value of the button.
     * 'text' => The button text.
     * 'color' => The hex color code of the button text, must include the leading '#'.
     * 'backgroundColor' => The hex color code of the button background, must include the leading '#'.
     */
    protected $button;
    /**
     * @var array An array representing an image with the following keys:
     * 'source' => The image source url.
     * 'link' => The href value of the image wrapper.
     * 'alt' => The alt value of the image tag.
     */
    protected $image;
    /**
     * @var string The path to the email view.
     */
    protected $view;
    /**
     * @var bool Whether to render in plaintext.
     */
    protected $plaintext = false;
    // Colors
    /**
     * @var string The hex color code of the background, must include the leading '#'.
     */
    protected $backgroundColor = '#eee';
    /**
     * @var string The default hex color code of links, must include the leading '#'.
     */
    protected $linkColor = '';
    /**
     * @var string The default hex color code of the button background, must include the leading '#'.
     */
    protected $buttonBackgroundColor = '';
    /**
     * @var string The hex color code of accents, must include the leading '#' (default color value for links and button background-color).
     */
    protected $brandPrimary = '#38abe3'; // Vanilla blue


    /**
     * @param string $message HTML formatted email message (the body of the email).
     * @param string $title HTML formatted email title.
     * @param string $lead HTML formatted email lead (sub-title, appears under title).
     * @param string $view
     * @throws Exception
     */
    function __construct($message = '', $title = '', $lead = '', $view = 'email-basic') {
        $this->setMessage($message);
        $this->setTitle($title);
        $this->setLead($lead);

        $this->view = Gdn::controller()->fetchViewLocation($view, 'email', 'dashboard');
    }

    /**
     * Filters an unsafe HTML string and returns it.
     *
     * @param string $html The HTML to filter.
     * @return string The filtered HTML string.
     */
    protected function formatContent($html) {
        $str = Gdn_Format::htmlFilter($html);
//        $str = strip_tags($str, ['b', 'i', 'p', 'strong', 'em', 'br']);
        return $str;
    }

    /**
     * @param string $view The view name.
     * @param $controllerName The controller name for the view.
     * @param $applicationFolder The application forlder for the view.
     * @return EmailTemplate $this The calling object.
     * @throws Exception
     */
    protected function setView($view, $controllerName, $applicationFolder) {
        $this->view = Gdn::controller()->fetchViewLocation($view, $controllerName, $applicationFolder);
        return $this;
    }

    /**
     * @return string The HTML formatted email title.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title The HTML formatted email title.
     * @return EmailTemplate $this The calling object.
     */
    public function setTitle($title) {
        $this->title = $this->formatContent($title);
        return $this;
    }

    /**
     * @return string The HTML formatted email lead (sub-title, appears under title).
     */
    public function getLead() {
        return $this->lead;
    }

    /**
     * @param string $lead The HTML formatted email lead (sub-title, appears under title).
     * @return EmailTemplate $this The calling object.
     */
    public function setLead($lead) {
        $this->lead = $this->formatContent($lead);
        return $this;
    }

    /**
     * @return string The HTML formatted email message (the body of the email).
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @param string $message The HTML formatted email message (the body of the email).
     * @return EmailTemplate $this The calling object.
     */
    public function setMessage($message){
        $this->message = $this->formatContent($message);
        return $this;
    }

    /**
     * @return string The HTML formatted email footer.
     */
    public function getFooter() {
        return $this->footer;
    }

    /**
     * @param string $footer The HTML formatted email footer.
     * @return EmailTemplate $this The calling object.
     */
    public function setFooter($footer) {
        $this->footer = $this->formatContent($footer);
        return $this;
    }

    /**
     * @return string The hex color code of the background.
     */
    public function getBackgroundColor() {
        return $this->backgroundColor;
    }

    /**
     * @param string $backgroundColor The hex color code of the background, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setBackgroundColor($backgroundColor) {
        $this->backgroundColor = htmlspecialchars($backgroundColor);
        return $this;
    }

    /**
     * @return string The default hex color code of links, must include the leading '#'.
     */
    public function getLinkColor() {
        return $this->linkColor;
    }

    /**
     * Sets the default color for links.
     * The color of the EmailTemplate's link property can be overridden by setting $link['color']
     *
     * @param string $linkColor The default hex color code of links, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setLinkColor($linkColor) {
        $this->linkColor = $linkColor;
        return $this;
    }

    /**
     * @return string The default hex color code of the button background, must include the leading '#'.
     */
    public function getButtonBackgroundColor() {
        return $this->buttonBackgroundColor;
    }

    /**
     * Sets the default color for the button background.
     * The color of the EmailTemplate's link property can be overridden by setting $button['backgroundColor']
     *
     * @param string $buttonBackgroundColor The default hex color code of the button background, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setButtonBackgroundColor($buttonBackgroundColor) {
        $this->buttonBackgroundColor = $buttonBackgroundColor;
        return $this;
    }

    /**
     * @return string The hex color code of accents (default color value for links and button background-color).
     */
    public function getBrandPrimary() {
        return $this->brandPrimary;
    }

    /**
     * Sets the brand primary, which is the default color for links and the button if they are not individually set.
     * Colors of specific elements can be overridden by setting $linkColor, $buttonBackgroundColor, $button['backgroundColor'], or $link['color'].
     *
     * @param string $brandPrimary The hex color code of accents, must include the leading '#' (default color value for links and button background-color).
     * @return EmailTemplate $this The calling object.
     */
    public function setBrandPrimary($brandPrimary) {
        $this->brandPrimary = htmlspecialchars($brandPrimary);
        return $this;
    }

    /**
     * @return bool Whether to render in plaintext.
     */
    public function isPlaintext() {
        return $this->plaintext;
    }

    /**
     * @param bool $plainText Whether to render in plaintext.
     */
    public function setPlaintext($plainText) {
        $this->plaintext = $plainText;
    }

    /**
     * Set the button property.
     *
     * @param string $url The href value of the button.
     * @param string $text The button text.
     * @param string $color The hex color code of the button text, must include the leading '#'.
     * @param string $backgroundColor The hex color code of the button background, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setButton($url, $text, $color = '#fff', $backgroundColor = '') {
        if (!$backgroundColor) {
            $backgroundColor = $this->buttonColor ? $this->buttonBackgroundColor : $this->brandPrimary;
        }
        $this->button = array('url' => htmlspecialchars($url),
                              'text' => htmlspecialchars($this->formatContent($text)),
                              'color' => htmlspecialchars($color),
                              'backgroundColor' => htmlspecialchars($backgroundColor));
        return $this;
    }

    /**
     * Set the link property.
     *
     * @param string $url The href value of the link.
     * @param string $text The link text.
     * @param string $color The hex color code of the link, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setLink($url, $text, $color = '') {
        if (!$color) {
            $color = $this->linkColor ? $this->linkColor : $this->brandPrimary;
        }
        // We need both text and a url to have a valid link.
        if ($url && $text) {
            $this->link = array('url' => htmlspecialchars($url),
                'text' => htmlspecialchars($this->formatContent($text)),
                'color' => htmlspecialchars($color));
        }
        return $this;
    }

    /**
     * @return array An array representing an image with the following keys:
     * 'source' => The image source url.
     * 'link' => The href value of the image wrapper.
     * 'alt' => The alt value of the image tag.
     */
    public function getImage() {
        return $this->image;
    }

    /**
     * Set the image property.
     *
     * @param string $sourceUrl The image source url.
     * @param string $linkUrl  The href value of the image wrapper.
     * @param string $alt The alt value of the img tag.
     * @return EmailTemplate $this The calling object.
     */
    public function setImage($sourceUrl = '', $linkUrl = '', $alt = '') {
        // We need either a source image or an alt to have an img tag.
        if ($sourceUrl || $alt) {
            $this->image = array('source' => htmlspecialchars($sourceUrl),
                'link' => htmlspecialchars($linkUrl),
                'alt' => $alt);
        }
        return $this;
    }

    /**
     * Set the image property using an array with the following keys:
     * 'source' => The image source url.
     * 'link' => The href value of the image wrapper.
     * 'alt' => The alt value of the img tag.
     * @return EmailTemplate $this The calling object.
     */
    public function setImageArray($image) {
        $this->setImage(val('source', $image), val('link', $image), val('alt', $image));
        return $this;
    }

    /**
     * Copies the email object to an array. A simple (array) typecast won't work,
     * since the properties are protected and as such, add unwanted information to the array keys.
     *
     * @param EmailTemplate $email The email object.
     * @return array Copy of email object in an array format for output.
     */
    protected function objectToArray($email) {
        if (is_array($email) || is_object($email)) {
            $result = array();
            foreach ($email as $key => $value) {
                $result[$key] = $this->objectToArray($value);
            }
            return $result;
        }
        return $email;
    }

    /**
     * Renders a plaintext email.
     *
     * @return string A plaintext email.
     */
    protected function plainTextEmail() {
        $email = array(
            val('alt', $this->image).' '.val('link', $this->image),
            $this->getTitle(),
            $this->getLead(),
            val('text', $this->button).' '.val('url', $this->button),
            $this->getMessage(),
            val('text', $this->link).' '.val('url', $this->link),
            $this->getFooter()
        );
        $email = implode('<br><br>', $email);
        $email = Gdn_Format::plainText($email);
        return $email;
    }

    /**
     * Render the email.
     *
     * @return string The rendered email.
     */
    public function toString() {
        if ($this->isPlaintext()) {
            return $this->plainTextEmail();
        }
        if (!$this->getLinkColor()) {
            $this->setLinkColor($this->getBrandPrimary());
        }
        $controller = new Gdn_Controller();
        $controller->setData('email', $this->objectToArray($this));
        $email = $controller->fetchView($this->view);
        // Append plaintext version
        $email .= self::PLAINTEXT_START.$this->plainTextEmail();
        return $email;
    }
}
