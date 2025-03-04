<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Web\Middleware;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn;
use LocaleModel;
use MachineTranslation\Services\GptTranslationService;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;

/**
 * A middleware that sets a locale based on slug
 */
class LocaleMiddleware
{
    /**
     * Invoke the middleware that sets the ban.
     *
     * @param RequestInterface $request The current request.
     * @param callable $next The next middleware
     * @return mixed
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function __invoke(RequestInterface $request, callable $next): mixed
    {
        $response = Data::box($next($request));
        if (GptTranslationService::isEnabled()) {
            $data = $response->getData();
            $resource = $response->getMeta("resource");
            if (is_array($data) && !empty($resource)) {
                $data = $this->translate($resource, $data);
                $response->setData($data);
            }
        }
        return $response;
    }

    /**
     * Get translation for the resource
     *
     * @param string $resourceType
     * @param array $data
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws \Exception
     */
    public function translate(string $resourceType, array $data): array
    {
        if (!empty(\Gdn::locale()->current()) && !in_array($resourceType, ["layout", "layouts"])) {
            $communityMachineTranslationModel = Gdn::getContainer()->get(CommunityMachineTranslationModel::class);
            $data = $communityMachineTranslationModel->replaceTranslatableRecord(
                $resourceType,
                $data,
                \Gdn::locale()->current()
            );
        }
        return $data;
    }
}
