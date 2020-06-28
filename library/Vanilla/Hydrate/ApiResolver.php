<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Hydrate;

use Garden\Hydrate\AbstractDataResolver;
use Garden\Schema\Schema;
use Garden\Web\Dispatcher;
use Garden\Web\Exception\HttpException;
use Vanilla\Web\RequestFactory;

/**
 * A data resolver that makes API calls.
 */
class ApiResolver extends AbstractDataResolver {

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var RequestFactory
     */
    private $requests;

    /**
     * ApiResolver constructor.
     *
     * @param RequestFactory $requests The request factory used for generating requests.
     * @param Dispatcher $dispatcher The dispatcher that will dispatch the requests.
     */
    public function __construct(RequestFactory $requests, Dispatcher $dispatcher) {
        $this->requests = $requests;
        $this->dispatcher = $dispatcher;

        $this->schema = new Schema([
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                ],
                'query' => [
                    'type' => 'object',
                    'default' => [],
                ],
            ],
            'required' => ['path', 'query']
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string {
        return "api";
    }

    /**
     * Resolve the data by making an internal API call.
     *
     * @param array $data
     * @param array $params
     * @return mixed
     */
    protected function resolveInternal(array $data, array $params) {
        ['path' => $path, 'query' => $query] = $data;

        $request = $this->requests->createRequest('GET', $path, $query);

        $result = $this->dispatcher->dispatch($request);
        if ($result->getStatus() < 200 || $result->getStatus() >= 300) {
            $ex = HttpException::createFromStatus($result->getStatus(), $result->getDataItem('message', ''));
            throw $ex;
        } else {
            return $result->getData();
        }
    }
}
