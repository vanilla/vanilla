<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class EmailTemplate
 *
 * Compiles the data for an email, applies appropriate content filters and renders the email.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @package Core
 * @since 2.2
 */
class EmailTemplate extends Gdn_Pluggable implements Gdn_IEmailTemplate {

    /** Delimiter for plaintext email. */
    const PLAINTEXT_START = '<!-- //TEXT VERSION FOLLOWS//';

    /** Default email colors. */
    const DEFAULT_TEXT_COLOR = '#333333';
    const DEFAULT_BACKGROUND_COLOR = '#eeeeee';
    const DEFAULT_CONTAINER_BACKGROUND_COLOR = '#ffffff';
    const DEFAULT_BUTTON_BACKGROUND_COLOR = '#38abe3'; // Vanilla blue
    const DEFAULT_BUTTON_TEXT_COLOR = '#ffffff';


    // Component properties

    /** @var string The HTML formatted email title. */
    protected $title;

    /**  @var string The HTML formatted email lead (sub-title, appears under title). */
    protected $lead;

    /** @var string The HTML formatted email message (the body of the email). */
    protected $message;

    /**
     * @var array An array representing a footer with the following keys:
     *  'text' => The HTML-formatted footer text.
     *  'textColor' => The hex color code of the footer text, must include the leading '#'.
     *  'backgroundColor' => The hex color code of the footer background, must include the leading '#'.
     */
    protected $footer;

    /**
     * @var array An array representing a button with the following keys:
     *  'url' => The href value of the button.
     *  'text' => The button text.
     *  'textColor' => The hex color code of the button text, must include the leading '#'.
     *  'backgroundColor' => The hex color code of the button background, must include the leading '#'.
     */
    protected $button;

    /**
     * @var array An array representing an image with the following keys:
     *  'source' => The image source url.
     *  'link' => The href value of the image wrapper.
     *  'alt' => The alt value of the image tag.
     */
    protected $image;

    /** @var string The path to the email view. */
    protected $view;

    /** @var bool Whether to render in plaintext. */
    protected $plaintext = false;


    // Color properties

    /** @var string The hex color code of the text, must include the leading '#'.*/
    protected $textColor = self::DEFAULT_TEXT_COLOR;

    /** @var string The hex color code of the background, must include the leading '#'. */
    protected $backgroundColor = self::DEFAULT_BACKGROUND_COLOR;

    /** @var string The hex color code of the container background, must include the leading '#'. */
    protected $containerBackgroundColor = self::DEFAULT_CONTAINER_BACKGROUND_COLOR;

    /**@var string The default hex color code of the button text, must include the leading '#'. */
    protected $defaultButtonTextColor = self::DEFAULT_BUTTON_TEXT_COLOR;

    /** @var string The default hex color code of the button background, must include the leading '#'. */
    protected $defaultButtonBackgroundColor = self::DEFAULT_BUTTON_BACKGROUND_COLOR;


    /**
     * Template initial setup.
     *
     * @param string $message HTML formatted email message (the body of the email).
     * @param string $title HTML formatted email title.
     * @param string $lead HTML formatted email lead (sub-title, appears under title).
     * @param string $view
     *
     * @throws Exception
     */
    public function __construct($message = '', $title = '', $lead = '', $view = 'email-basic') {
        parent::__construct();

        $this->setMessage($message);
        $this->setTitle($title);
        $this->setLead($lead);

        // Set templating defaults
        $this->setTextColor(c('Garden.EmailTemplate.TextColor', self::DEFAULT_TEXT_COLOR));
        $this->setBackgroundColor(c('Garden.EmailTemplate.BackgroundColor', self::DEFAULT_BACKGROUND_COLOR));
        $this->setContainerBackgroundColor(c('Garden.EmailTemplate.ContainerBackgroundColor', self::DEFAULT_CONTAINER_BACKGROUND_COLOR));
        $this->setDefaultButtonBackgroundColor(c('Garden.EmailTemplate.ButtonBackgroundColor', self::DEFAULT_BUTTON_BACKGROUND_COLOR));
        $this->setDefaultButtonTextColor(c('Garden.EmailTemplate.ButtonTextColor', self::DEFAULT_BUTTON_TEXT_COLOR));

        $this->setDefaultEmailImage();

        // Set default view
        $this->view = AssetModel::viewLocation($view, 'email', 'dashboard');
    }

    /**
     * Sets the default image for the email template.
     */
    protected function setDefaultEmailImage() {
        if (!$this->getImage()) {
            $image = $this->getDefaultEmailImage();
            $this->setImageArray($image);
        }
    }

    /**
     * Retrieves default values for the email image.
     *
     * @return array An array representing an image.
     */
    public function getDefaultEmailImage() {
        $image = [];
        if (c('Garden.EmailTemplate.Image', '')) {
            $image['source'] = Gdn_UploadImage::url(c('Garden.EmailTemplate.Image'));
        }
        $image['link'] = url('/', true);
        $image['alt'] = c('Garden.LogoTitle', c('Garden.Title', ''));
        return $image;
    }

    /**
     * Filters an unsafe HTML string and returns it.
     *
     * @param string $html The HTML to filter.
     * @param bool $convertNewlines Whether to convert new lines to html br tags.
     * @param bool $filter Whether to escape HTML or not.
     *
     * @return string The filtered HTML string.
     */
    protected function formatContent($html, $convertNewlines = false, $filter = false) {
        $str = $html;
        if ($filter) {
            $str = Gdn_Format::htmlFilter($str);
        }
        if ($convertNewlines) {
            $str = preg_replace('/(\015\012)|(\015)|(\012)/', '<br>', $str);
        }
        // $str = strip_tags($str, ['b', 'i', 'p', 'strong', 'em', 'br']);
        return $str;
    }

    /**
     * Set which application view to use.
     *
     * @param string $view The view name.
     * @param string $controllerName The controller name for the view.
     * @param string $applicationFolder The application folder for the view.
     *
     * @return EmailTemplate $this The calling object.
     * @throws Exception
     */
    public function setView($view, $controllerName = 'email', $applicationFolder = 'dashboard') {
        $this->view = AssetModel::viewLocation($view, $controllerName, $applicationFolder);
        return $this;
    }

    /**
     * Get the current email title.
     *
     * @return string The HTML formatted email title.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set the current email title.
     *
     * @param string $title The HTML formatted email title.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setTitle($title) {
        $this->title = $this->formatContent($title);
        return $this;
    }

    /**
     * Get the current email sub-title.
     *
     * @return string The HTML formatted email lead (sub-title, appears under title).
     */
    public function getLead() {
        return $this->lead;
    }

    /**
     * Set the current email sub-title.
     *
     * @param string $lead The HTML formatted email lead (sub-title, appears under title).
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setLead($lead) {
        $this->lead = $this->formatContent($lead);
        return $this;
    }

    /**
     * Get the main body of the email.
     *
     * @return string The HTML formatted email message (the body of the email).
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Set the main body of the email.
     *
     * @param string $message The HTML formatted email message (the body of the email).
     * @param bool $convertNewlines Whether to convert new lines to html br tags.
     * @param bool $filter Whether to filter HTML or not.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setMessage($message, $convertNewlines = false, $filter = false) {
        $this->message = $this->formatContent($message, $convertNewlines, $filter);
        return $this;
    }

    /**
     * Get the HTML footer.
     *
     * @return string The HTML formatted email footer.
     */
    public function getFooter() {
        return $this->footer;
    }

    /**
     * Sets the HTML footer.
     *
     * The footer background and text colors default to the button background and text colors.
     *
     * @param string $text The HTML formatted email footer text.
     * @param string $textColor The hex color code of the footer text, must include the leading '#'.
     * @param string $backgroundColor The hex color code of the footer background, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setFooter($text, $textColor = '', $backgroundColor = '') {
        if (!$textColor) {
            $textColor = $this->defaultButtonTextColor;
        }
        if (!$backgroundColor) {
            $backgroundColor = $this->defaultButtonBackgroundColor;
        }
        $this->footer = ['text' => htmlspecialchars($this->formatContent($text)),
            'textColor' => htmlspecialchars($textColor),
            'backgroundColor' => htmlspecialchars($backgroundColor)];
        return $this;
    }

    /**
     * Get the main text color.
     *
     * @return string The hex color code of the text.
     */
    public function getTextColor() {
        return $this->textColor;
    }

    /**
     * Set the main text color. Chainable.
     *
     * @param string $color The hex color code of the text, must include the leading '#'.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setTextColor($color) {
        $this->textColor = htmlspecialchars($color);
        return $this;
    }

    /**
     *
     *
     * @return string The hex color code of the background.
     */
    public function getBackgroundColor() {
        return $this->backgroundColor;
    }

    /**
     *
     *
     * @param string $color The hex color code of the background, must include the leading '#'.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setBackgroundColor($color) {
        $this->backgroundColor = htmlspecialchars($color);
        return $this;
    }

    /**
     *
     *
     * @return string The hex color code of the container background.
     */
    public function getContainerBackgroundColor() {
        return $this->containerBackgroundColor;
    }

    /**
     *
     *
     * @param string $color The hex color code of the container background, must include the leading '#'.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setContainerBackgroundColor($color) {
        $this->containerBackgroundColor = htmlspecialchars($color);
        return $this;
    }

    /**
     *
     *
     * @return string The default hex color code of the button text, must include the leading '#'.
     */
    public function getDefaultButtonTextColor() {
        return $this->defaultButtonTextColor;
    }

    /**
     * Sets the default color for the button text.
     *
     * The text color of the EmailTemplate's button property can be overridden by setting $button['textColor']
     *
     * @param string $color The default hex color code of the button text, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setDefaultButtonTextColor($color) {
        $this->defaultButtonTextColor = $color;
        return $this;
    }

    /**
     *
     *
     * @return string The default hex color code of the button background, must include the leading '#'.
     */
    public function getDefaultButtonBackgroundColor() {
        return $this->defaultButtonBackgroundColor;
    }

    /**
     * Sets the default color for the button background.
     *
     * The background color of the EmailTemplate's button property can be overridden by setting $button['backgroundColor']
     *
     * @param string $color The default hex color code of the button background, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setDefaultButtonBackgroundColor($color) {
        $this->defaultButtonBackgroundColor = $color;
        return $this;
    }

    /**
     * Get the `plaintext` property.
     *
     * @return bool Whether to render in plaintext.
     */
    public function isPlaintext() {
        return $this->plaintext;
    }

    /**
     * Set the `plaintext` property.
     *
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
     * @param string $textColor The hex color code of the button text, must include the leading '#'.
     * @param string $backgroundColor The hex color code of the button background, must include the leading '#'.
     * @return EmailTemplate $this The calling object.
     */
    public function setButton($url, $text, $textColor = '', $backgroundColor = '') {
        if (!$textColor) {
            $textColor = $this->defaultButtonTextColor;
        }
        if (!$backgroundColor) {
            $backgroundColor = $this->defaultButtonBackgroundColor;
        }
        $this->button = ['url' => htmlspecialchars($url),
                              'text' => htmlspecialchars($this->formatContent($text)),
                              'textColor' => htmlspecialchars($textColor),
                              'backgroundColor' => htmlspecialchars($backgroundColor)];
        return $this;
    }

    /**
     * Remove the button.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function removeButton() {
        $this->button = [];
        return $this;
    }

    /**
     *
     *
     * @return array An array representing an image with the following keys:
     *  'source' => The image source url.
     *  'link' => The href value of the image wrapper.
     *  'alt' => The alt value of the image tag.
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
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setImage($sourceUrl = '', $linkUrl = '', $alt = '') {
        // We need either a source image or an alt to have an img tag.
        if ($sourceUrl || $alt) {
            $this->image = ['source' => htmlspecialchars($sourceUrl),
                'link' => htmlspecialchars($linkUrl),
                'alt' => $alt];
        }
        return $this;
    }

    /**
     * Set the image properties.
     *
     * @param array $image Uses the following keys:
     *  'source' => The image source url.
     *  'link' => The href value of the image wrapper.
     *  'alt' => The alt value of the img tag.
     *
     * @return EmailTemplate $this The calling object.
     */
    public function setImageArray($image) {
        $this->setImage(val('source', $image), val('link', $image), val('alt', $image));
        return $this;
    }

    /**
     * Copies the email object to an array.
     *
     * A simple (array) typecast won't work since the properties are protected
     * and, as such, add unwanted information to the array keys.
     *
     * @param EmailTemplate $email The email object.
     *
     * @return array Copy of email object in an array format for output.
     */
    protected function objectToArray($email) {
        if (is_array($email) || is_object($email)) {
            $result = [];
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
        $email = [
            'banner' => val('alt', $this->image).' '.val('link', $this->image),
            'title' => $this->getTitle(),
            'lead' => $this->getLead(),
            'message' => $this->getMessage(),
            'button' => sprintf(t('%s: %s'), val('text', $this->button), val('url', $this->button)),
            'footer' => $this->getFooter()
        ];

        foreach ($email as $key => $val) {
            if (!$val) {
                unset($email[$key]);
            } else {
                if ($key == 'message') {
                    $email[$key] = "<br>$val<br>";
                }
            }
        }

        return Gdn_Format::plainText(Gdn_Format::text(implode('<br>', $email), false));
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
        $controller = new Gdn_Controller();
        $controller->setData('email', $this->objectToArray($this));
        $email = $controller->fetchView($this->view);

        // Append plaintext version
        $email .= self::PLAINTEXT_START.$this->plainTextEmail();
        return $email;
    }
}
