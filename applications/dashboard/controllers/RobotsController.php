<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\EventManager;
use Gdn_Session as SessionInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\Robots;

/**
 * A controller for serving instructions to web crawlers.
 */
class RobotsController extends Gdn_Controller
{
    /** Default robots.txt contents. */
    private const ROBOTS_DEFAULT = [
        "/entry/",
        "/messages/",
        "/profile/comments/",
        "/profile/discussions/",
        "/search/",
        "/sso/",
        "/sso",
    ];

    /** Content of robots.txt when the site is supposed to be "invisible" to crawlers and bots. */
    private const ROBOTS_INVISIBLE = <<<ROBOTS_INVISIBLE
User-agent: *
Disallow: /
ROBOTS_INVISIBLE;
    /**
     * Inject dependencies.
     *
     * @param ConfigurationInterface $configuration
     * @param EventManager $eventManager
     * @param SessionInterface $session
     */
    public function __construct(
        private ConfigurationInterface $configuration,
        private EventManager $eventManager,
        private SessionInterface $session
    ) {
        parent::__construct();
    }

    /**
     * Get initial rules for the body of robots.txt.
     *
     * @return string
     */
    private function robotRules(): string
    {
        // Config lookup is backwards-compatible with Sitemaps addon.
        $rules = $this->configuration->get(
            "Robots.Rules",
            $this->configuration->get("Sitemap.Robots.Rules", $this->generateSiteRobotDefault())
        );

        return $rules;
    }

    private function generateSiteRobotDefault(): string
    {
        $rules = <<<UserAgent
User-agent: *

UserAgent;

        $sectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);

        // Go through the sections to find the correct one.
        $sections = $sectionModel->getAll();
        foreach (self::ROBOTS_DEFAULT as $disallowLine) {
            foreach ($sections as $section) {
                $rules .= <<<DisallowText
Disallow: {$section->getBasePath()}$disallowLine

DisallowText;
            }
        }

        return $rules;
    }

    /**
     * Generate the robots.txt body.
     */
    public function index()
    {
        // Clear the session to mimic a crawler.
        $this->session->UserID = 0;
        $this->session->User = false;

        $this->deliveryMethod(DELIVERY_METHOD_TEXT);
        $this->deliveryType(DELIVERY_TYPE_VIEW);

        $isInvisible = $this->configuration->get("Robots.Invisible");

        $robots = new Robots();
        if ($isInvisible) {
            $robots->addRule(self::ROBOTS_INVISIBLE);
        } else {
            $robots->addRule($this->robotRules());
            $this->eventManager->fire("robots_init", $robots);
        }

        $this->setHeader("Content-Type", "text/plain");
        $this->setData("rules", $robots->getRules());
        $this->setData("sitemaps", $robots->getSitemaps());
        $this->render();
    }
}
