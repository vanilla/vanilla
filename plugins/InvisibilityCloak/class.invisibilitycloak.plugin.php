<?php
/**
 * @copyright 2014-2016 Vanilla Forums, Inc.
 */

/**
 * Class InvisibilityCloakPlugin
 */
class InvisibilityCloakPlugin extends Gdn_Plugin {

    /** @var Gdn_Configuration */
    private $configuration;

    /**
     * Addon constructor.
     *
     * @param Gdn_Configuration $configuration
     */
    public function __construct(Gdn_Configuration $configuration) {
        $this->configuration = $configuration;
    }

    /**
     * Hook into the startup event.
     */
    public function gdn_dispatcher_appStartup_handler() {
        $this->configuration->set("Robots.Invisible", true, true, false);
    }

    /**
     * No bots meta tag.
     *
     * @param object $sender
     */
    public function base_render_before($sender) {
        if ($sender->Head) {
            $sender->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex,noarchive']);
        }
    }
}
