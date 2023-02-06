<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats;
use Vanilla\Web\TwigStaticRenderer;
use Vanilla\Formatting\Quill\Parser;

/**
 * Plugin class for the Rich Editor.
 */
class RichEditorPlugin extends Gdn_Plugin
{
    const FORMAT_NAME = RichFormat::FORMAT_KEY;
    const CONFIG_QUOTE_ENABLE = "RichEditor.Quote.Enable";
    const CONFIG_REINTERPRET_ENABLE = "RichEditor.Reinterpret.Enable";

    /** @var integer */
    private static $editorID = 0;

    /** @var \Vanilla\Formatting\FormatService */
    private $formatService;

    /**
     * Set some properties we always need.
     *
     * @param \Vanilla\Formatting\FormatService $formatService
     */
    public function __construct(\Vanilla\Formatting\FormatService $formatService)
    {
        $this->formatService = $formatService;
        parent::__construct();
        self::$editorID++;
    }

    /**
     * {@inheritDoc}
     */
    public function setup()
    {
        saveToConfig("Garden.InputFormatter", RichFormat::FORMAT_KEY);
        saveToConfig("Garden.MobileInputFormatter", RichFormat::FORMAT_KEY);
        saveToConfig(self::CONFIG_QUOTE_ENABLE, true);
        saveToConfig("EnabledPlugins.Quotes", false);
    }

    public function onDisable()
    {
        Gdn::config()->saveToConfig("Garden.InputFormatter", "Markdown");
        Gdn::config()->saveToConfig("Garden.MobileInputFormatter", "Markdown");
    }

    /**
     * @return int
     */
    public static function getEditorID(): int
    {
        return self::$editorID;
    }

    /**
     * Check to see if we should be using the Rich Editor
     *
     * @param Gdn_Form $form - A form instance.
     *
     * @return bool
     */
    public function isFormRich(Gdn_Form $form): bool
    {
        $data = $form->formData();
        $format = $data["Format"] ?? null;

        if (Gdn::config("Garden.ForceInputFormatter")) {
            return $this->isInputFormatterRich();
        }

        return $this->isFormatRich($format);
    }

    /**
     * TODO: This should be removed after VNLA-2665 merges
     * https://higherlogic.atlassian.net/browse/VNLA-2665
     * Check to see if we should be using the Rich Editor 2
     *
     * @param Gdn_Form $form - A form instance.
     *
     * @return bool
     */
    public function isFormRich2(Gdn_Form $form): bool
    {
        $data = $form->formData();
        $format = $data["Format"] ?? null;

        if (Gdn::config("Garden.ForceInputFormatter")) {
            return $this->isInputFormatterRich();
        }

        return $this->isFormatRich2($format);
    }

    /**
     * Determine if we are forcing the format to be rich.
     *
     * @param Gdn_Form $form
     *
     * @return bool
     */
    private function isForcedRich(Gdn_Form $form): bool
    {
        // Get the format of the post
        $data = $form->formData();
        $formFormat = $data["Format"] ?? null;

        // Ge the format set in config
        $formatConfig = Gdn::getContainer()->get(FormatConfig::class);
        $defaultFormat = $formatConfig->getDefaultFormat();

        // If reinterpret is true, we should compare the two formats
        if (Gdn::config(self::CONFIG_REINTERPRET_ENABLE)) {
            return $formFormat !== $defaultFormat;
        }

        // Otherwise there is no need to force
        return false;
    }

    /**
     * Determine if the format string corresponds to rich format
     *
     * @param string $format - Format string to check
     * @return bool
     */
    private function isFormatRich(string $format): bool
    {
        return strcasecmp($format, RichFormat::FORMAT_KEY) === 0;
    }

    /**
     * TODO: This should be removed after VNLA-2665 merges
     * https://higherlogic.atlassian.net/browse/VNLA-2665
     * Determine if the format string corresponds to rich2 format
     *
     * @param string $format - Format string to check
     * @return bool
     */
    private function isFormatRich2(string $format): bool
    {
        return strcasecmp($format, RichFormat::RICH2_FORMAT_KEY) === 0;
    }

    /**
     * @return bool
     */
    public function isInputFormatterRich(): bool
    {
        return $this->isFormatRich(Gdn_Format::defaultFormat());
    }

    /**
     * Add the rich editor format to the posting page.
     *
     * @param string[] $postFormats Existing post formats.
     *
     * @return string[] Additional post formats.
     */
    public function getPostFormats_handler(array $postFormats): array
    {
        $postFormats[] = "Rich"; // The config values have always been uppercase. (including in default configs).
        return $postFormats;
    }

    /**
     * Attach editor anywhere 'BodyBox' is used.
     *
     * It is not being used for editing a posted reply, so find another event to hook into.
     *
     * @param Gdn_Form $sender The Form Object
     * @param array $args Arguments from the event.
     */
    public function gdn_form_beforeBodyBox_handler(Gdn_Form $sender, array $args)
    {
        $isRich = $this->isFormRich($sender);
        $isRich2 = $this->isFormRich2($sender);
        $originalRecord = $sender->formData();
        $body = $originalRecord["Body"] ?? false;
        $originalFormat = $originalRecord["Format"] ?? false;
        $isForcedRich = $this->isForcedRich($sender);

        if ($isRich || $isRich2 || $isForcedRich) {
            $controller = Gdn::controller();
            if ($controller) {
                $controller->CssClass .= " hasRichEditor";
            }

            $editorID = $this->getEditorID();
            $viewData = [
                "editorID" => $editorID,
                "descriptionID" => "richEditor-" . $editorID . "-description",
                "hasUploadPermission" => checkPermission("uploads.add"),
                "uploadEnabled" => $args["Attributes"]["UploadEnabled"] ?? true,
            ];

            if (!Gdn::session()->User->Admin) {
                // If a category is set check for AllowFileUploads. (admins bypass this condition)
                $categoryID = $controller->data("Category.CategoryID", $controller->data("ContextualCategoryID"));
                // Check the category exists.
                $category = CategoryModel::categories($categoryID);
                $viewData["uploadEnabled"] = CategoryModel::checkAllowFileUploads($category);
            }
            if ($this->isFormatRich($originalFormat)) {
                // Filter out empty arrays from JSON. See https://higherlogic.atlassian.net/browse/VNLA-640
                try {
                    $newBodyValue = $this->formatService->filter($body, RichFormat::FORMAT_KEY);
                    $sender->setValue("Body", $newBodyValue);
                } catch (\Exception $e) {
                    // Ignore
                }
            }
            if ($isForcedRich) {
                $viewData["needsHtmlConversion"] = true;

                $newBodyValue = $this->formatService->renderHTML($body, $originalFormat);
                $sender->setValue("Body", $newBodyValue);
                $sender->setValue("Format", RichFormat::FORMAT_KEY);
            }

            $rendered = TwigStaticRenderer::renderTwigStatic("@rich-editor/rich-editor.twig", $viewData);

            // Render the editor view.
            $args["BodyBox"] .= $rendered;
        } elseif (c("Garden.ForceInputFormatter")) {
            /*
                Allow rich content to be rendered and modified if the InputFormat
                is different from the original format in no longer applicable or
                forced to be different by Garden.ForceInputFormatter.
            */
            if ($body && c("Garden.InputFormatter") !== $originalFormat) {
                switch (strtolower(c("Garden.InputFormatter", "unknown"))) {
                    case Formats\TextFormat::FORMAT_KEY:
                    case Formats\TextExFormat::FORMAT_KEY:
                        $newBodyValue = $this->formatService->renderPlainText($body, Formats\RichFormat::FORMAT_KEY);
                        $sender->setValue("Body", $newBodyValue);
                        break;
                    case "unknown":
                        // Do nothing
                        break;
                    default:
                        $newBodyValue = $this->formatService->renderHTML($body, Formats\HtmlFormat::FORMAT_KEY);
                        $sender->setValue("Body", $newBodyValue);
                }
            }
        }
    }

    /**
     * Add 'Quote' option to discussion via the reactions row after each post.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_afterFlag_handler($sender, $args)
    {
        if ($this->isInputFormatterRich() && c(self::CONFIG_QUOTE_ENABLE, true)) {
            $this->addQuoteButton($sender, $args);
        }
    }

    /**
     * Output Quote link.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    protected function addQuoteButton($sender, $args)
    {
        // There are some case were Discussion is not set as an event argument so we use the sender data instead.
        $discussion = $sender->data("Discussion");
        $discussion = is_array($discussion) ? (object) $discussion : $discussion;

        if (!$discussion) {
            return;
        }

        if (!Gdn::session()->UserID) {
            return;
        }

        if (
            !Gdn::session()->checkPermission(
                "Vanilla.Comments.Add",
                false,
                "Category",
                $discussion->PermissionCategoryID
            )
        ) {
            return;
        }

        if (isset($args["Comment"])) {
            $url = commentUrl($args["Comment"]);
        } elseif ($discussion) {
            $url = discussionUrl($discussion);
        } else {
            return;
        }

        $icon = sprite("ReactQuote", "ReactSprite");
        $linkText = $icon . " " . t("Quote");
        $classes = "ReactButton Quote Visible js-quoteButton";

        echo Gdn_Theme::bulletItem("Flags");
        echo "<a href='#' role='button' data-scrape-url='$url' role='button' class='$classes'>$linkText</a>";
        echo " ";
    }

    /**
     * Add additional WYSIWYG specific form item to the dashboard posting page.
     *
     * @param string $additionalFormItemHTML
     * @param Gdn_Form $form The Form instance from the page.
     * @param Gdn_ConfigurationModel $configModel The config model used for the Form.
     *
     * @return string The built up form html
     */
    public function postingSettings_formatSpecificFormItems_handler(
        string $additionalFormItemHTML,
        Gdn_Form $form,
        Gdn_ConfigurationModel $configModel
    ): string {
        $configModel->setField(self::CONFIG_QUOTE_ENABLE);
        $form->setValue(self::CONFIG_QUOTE_ENABLE, c(self::CONFIG_QUOTE_ENABLE));
        $configModel->setField(self::CONFIG_REINTERPRET_ENABLE);
        $form->setValue(self::CONFIG_REINTERPRET_ENABLE, c(self::CONFIG_REINTERPRET_ENABLE));

        $openingLiTag = "<li class='form-group js-richFormGroup Hidden' data-formatter-type='Rich'>";
        $additionalFormItemHTML .=
            $openingLiTag .
            VanillaSettingsController::postFormatReintrerpretToggle($form, self::CONFIG_REINTERPRET_ENABLE, "Rich") .
            "</li>";

        $additionalFormItemHTML .=
            $openingLiTag .
            $form->toggle(
                self::CONFIG_QUOTE_ENABLE,
                t("Enable Rich Quotes"),
                [],
                t(
                    "RichEditor.QuoteEnable.Notes",
                    'Use the following option to enable quotes for the Rich Editor. This will only apply if the default formatter is "Rich".'
                )
            ) .
            "</li>";
        return $additionalFormItemHTML;
    }
}
