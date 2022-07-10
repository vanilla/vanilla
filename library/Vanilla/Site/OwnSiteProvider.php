<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Garden\Http\HttpClient;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Contracts\Site\AbstractSiteProvider;
use Vanilla\Contracts\Site\Site;

/**
 * Default site provider for when no other one is registered.
 *
 * Only knows about it's own site and the IDs don't mean anything.
 */
class OwnSiteProvider extends AbstractSiteProvider {

    /** @var Site */
    protected $ownSite;

    /** @var Site */
    protected $unknownSite;

    /**
     * DI.
     *
     * @param OwnSite $ownSite
     * @param LocaleInterface $locale
     */
    public function __construct(
        OwnSite $ownSite,
        LocaleInterface $locale
    ) {
        $this->ownSite = $ownSite;

        $unknownSiteHttp = new HttpClient();
        $unknownSiteHttp->addMiddleware(function () {
            throw new NotFoundException('Site');
        });
        $this->unknownSite = new Site(
            $locale->translate('Unknown Site'),
            "/",
            -1,
            -1,
            $unknownSiteHttp
        );
    }

    /**
     * @return Site[]
     */
    public function getAllSites(): array {
        return [$this->ownSite];
    }

    /**
     * @inheritdoc
     */
    public function getOwnSite(): Site {
        return $this->ownSite;
    }

    /**
     * @inheritdoc
     */
    public function getUnknownSite(): Site {
        return $this->unknownSite;
    }
}
