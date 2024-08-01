<?php
/**
 * Guest module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Community\CallToActionModule;

/**
 * Renders the "You should register or sign in" panel box.
 */
class GuestModule extends Gdn_Module
{
    /** @var string  */
    public $MessageCode = "GuestModule.Message";

    /** @var string  */
    public $MessageDefault = "It looks like you're new here. Sign in or register to get started.";

    /**
     * @var bool
     */
    private $asWidget = false;

    /**
     * @var string
     */
    private $registerLinkButtonType = "standard";

    /**
     * @var string
     */
    private $signInLinkButtonType = "primary";

    /**
     * @var string
     */
    private $widgetAlignment = "left";

    /**
     * @var boolean
     */
    private $compactButtonsInWidget = false;

    /**
     * @var boolean
     */
    private $desktopOnlyWidget = false;

    /**
     *
     *
     * @param string $sender
     * @param bool $applicationFolder
     */
    public function __construct($sender = "", $applicationFolder = false)
    {
        if (!$applicationFolder) {
            $applicationFolder = "Dashboard";
        }
        parent::__construct($sender, $applicationFolder);

        $this->Visible = c("Garden.Modules.ShowGuestModule");
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget()
    {
        return "Panel";
    }

    /**
     * @return bool
     */
    public function isAsWidget(): bool
    {
        return $this->asWidget;
    }

    /**
     * @param bool $asWidget
     */
    public function setAsWidget(bool $asWidget): void
    {
        $this->asWidget = $asWidget;
    }

    /**
     * @return string
     */
    public function getRegisterLinkButtonType(): string
    {
        return $this->registerLinkButtonType;
    }

    /**
     * @param string $registerLinkButtonType
     */
    public function setRegisterLinkButtonType(string $registerLinkButtonType): void
    {
        $this->registerLinkButtonType = $registerLinkButtonType;
    }

    /**
     * @return string
     */
    public function getSignInLinkButtonType(): string
    {
        return $this->signInLinkButtonType;
    }

    /**
     * @param string $signInLinkButtonType
     */
    public function setSignInLinkButtonType(string $signInLinkButtonType): void
    {
        $this->signInLinkButtonType = $signInLinkButtonType;
    }

    /**
     * @return string
     */
    public function getWidgetAlignment(): string
    {
        return $this->widgetAlignment;
    }

    /**
     * @param string $widgetAlignment
     */
    public function setWidgetAlignment(string $widgetAlignment): void
    {
        $this->widgetAlignment = $widgetAlignment;
    }

    /**
     * @return bool
     */
    public function getCompactButtonsInWidget(): bool
    {
        return $this->compactButtonsInWidget;
    }

    /**
     * @param bool $compactButtonsInWidget
     */
    public function setCompactButtonsInWidget(bool $compactButtonsInWidget): void
    {
        $this->compactButtonsInWidget = $compactButtonsInWidget;
    }

    /**
     * @return bool
     */
    public function getDesktopOnlyWidget(): bool
    {
        return $this->desktopOnlyWidget;
    }

    /**
     * @param bool $desktopOnlyWidget
     */
    public function setDesktopOnlyWidget(bool $desktopOnlyWidget): void
    {
        $this->desktopOnlyWidget = $desktopOnlyWidget;
    }

    /**
     * Get module data
     */
    public function getData()
    {
        $controller = Gdn::controller();
        $this->setData("signInUrl", signInUrl($controller->SelfUrl));
        $this->setData("registerUrl", registerUrl($controller->SelfUrl));
    }

    /**
     * Get Guest Module as CallToActionModule.
     *
     * @return CallToActionModule
     */
    private function getWidget(): CallToActionModule
    {
        $ctaModule = new CallToActionModule();
        $ctaModule->setAlignment($this->widgetAlignment);
        $ctaModule->setCompactButtons($this->compactButtonsInWidget);
        $ctaModule->setDesktopOnly($this->desktopOnlyWidget);
        $ctaModule->setTitle(t("Welcome!", t("Howdy, Stranger!", "Welcome!")));
        $ctaModule->setDescription(t($this->MessageCode, $this->MessageDefault));
        $ctaModule->setTextCTA(t("Sign In"));
        $ctaModule->setUrl($this->data("signInUrl"));
        $ctaModule->setLinkButtonType($this->getSignInLinkButtonType());
        if ($this->data("registerUrl")) {
            $ctaModule->setOtherCTAs([
                [
                    "textCTA" => t("Register", t("Apply for Membership", "Register")),
                    "to" => $this->data("registerUrl"),
                    "linkButtonType" => $this->getRegisterLinkButtonType(),
                ],
            ]);
        }

        return $ctaModule;
    }

    /**
     * Render.
     *
     * @return string
     */
    public function toString()
    {
        $isGuest = !Gdn::session()->isValid();
        $newGuestModule = Gdn::themeFeatures()->get("NewGuestModule");
        $isDataDrivenTheme = Gdn::themeFeatures()->get("DataDrivenTheme");

        //other plugins (like Private Discussions) might have their own views, so we won't prevent its functionality
        $isDefaultApplicationFolder = $this->_ApplicationFolder === "Dashboard";

        if ($isGuest) {
            $this->getData();
        }
        if ($newGuestModule && $isDefaultApplicationFolder) {
            $this->setAsWidget(true);
        }

        //in legacy themes we render smaller buttons to fit in the panel
        if (!$isDataDrivenTheme) {
            $this->setCompactButtonsInWidget(true);
        }
        if ($isGuest && !$this->isAsWidget()) {
            return parent::toString();
        }
        if ($isGuest && $this->isAsWidget() && $this->data("signInUrl")) {
            return $this->getWidget()->toString();
        }

        return "";
    }
}
