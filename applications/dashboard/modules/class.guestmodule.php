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
class GuestModule extends Gdn_Module {

    /** @var string  */
    public $MessageCode = 'GuestModule.Message';

    /** @var string  */
    public $MessageDefault = "It looks like you're new here. If you want to get involved, click one of these buttons!";

    /**
     * @var bool
     */
    private $asWidget = false;

    private $registerLinkButtonType = 'standard';
    private $signInLinkButtonType = 'primary';

    /**
     *
     *
     * @param string $sender
     * @param bool $applicationFolder
     */
    public function __construct($sender = '', $applicationFolder = false) {
        if (!$applicationFolder) {
            $applicationFolder = 'Dashboard';
        }
        parent::__construct($sender, $applicationFolder);

        $this->Visible = c('Garden.Modules.ShowGuestModule');
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * @return bool
     */
    public function isAsWidget(): bool {
        return $this->asWidget;
    }

    /**
     * @param bool $asWidget
     */
    public function setAsWidget(bool $asWidget): void {
        $this->asWidget = $asWidget;
    }

    /**
     * @return string
     */
    public function getRegisterLinkButtonType(): string {
        return $this->registerLinkButtonType;
    }

    /**
     * @param string $registerLinkButtonType
     */
    public function setRegisterLinkButtonType(string $registerLinkButtonType): void {
        $this->registerLinkButtonType = $registerLinkButtonType;
    }

    /**
     * @return string
     */
    public function getSignInLinkButtonType(): string {
        return $this->signInLinkButtonType;
    }

    /**
     * @param string $signInLinkButtonType
     */
    public function setSignInLinkButtonType(string $signInLinkButtonType): void {
        $this->signInLinkButtonType = $signInLinkButtonType;
    }

    /**
     * Get module data
     */
    public function getData() {
        $controller = Gdn::controller();
        $this->setData('signInUrl', signInUrl($controller->SelfUrl));
        $this->setData('registerUrl', registerUrl($controller->SelfUrl));
    }

    /**
     * Get Guest Module as CallToActionModule.
     *
     * @return CallToActionModule
     */
    private function getWidget(): CallToActionModule {
        $ctaModule = new CallToActionModule();
        $ctaModule->setTitle(t('Howdy, Stranger!'));
        $ctaModule->setDescription(t($this->MessageCode, $this->MessageDefault));
        $ctaModule->setTextCTA(t("Sign In Now"));
        $ctaModule->setUrl($this->data('signInUrl'));
        $ctaModule->setLinkButtonType($this->getSignInLinkButtonType());
        if ($this->data('registerUrl')) {
            $ctaModule->setOtherCTAs([
                [
                    'textCTA' => t('Register', t('Apply for Membership', 'Register')),
                    'to' => $this->data('registerUrl'),
                    'linkButtonType' => $this->getRegisterLinkButtonType(),
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
    public function toString() {
        $isGuest = !Gdn::session()->isValid();
        if ($isGuest) {
            $this->getData();
        }
        if ($isGuest && !$this->isAsWidget()) {
            return parent::toString();
        }
        if ($isGuest && $this->isAsWidget() && $this->data('signInUrl')) {
            return $this->getWidget()->toString();
        }

        return '';
    }
}
