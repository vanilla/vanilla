<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Resolvers;

use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Schema\Schema;
use Vanilla\Http\InternalClient;

/**
 * Resolver for hydrating API responses.
 */
class ApiResolver extends AbstractDataResolver
{
    public const TYPE = "api";

    /** @var InternalClient */
    private $internalClient;

    /**
     * DI.
     *
     * @param InternalClient $internalClient
     */
    public function __construct(InternalClient $internalClient)
    {
        $this->internalClient = $internalClient;
    }

    /**
     * @inheritdoc
     */
    protected function resolveInternal(array $data, array $params)
    {
        $result = $this->internalClient->get($data["url"], $data["query"] ?? [], [], ["throw" => true]);
        $body = $result->getBody();

        $jsont = $data["jsont"] ?? null;
        if ($jsont !== null) {
            $transformer = new \Garden\JSON\Transformer($jsont);
            $body = $transformer->transform($body);
        }

        return $body;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): ?Schema
    {
        $schema = Schema::parse([
            "url:s" => [
                "description" => "The URL for an API call on the current site.",
            ],
            "query?" => new Schema([
                "type" => "object",
                "additionalProperties" => true,
                "description" => "Query parameters to apply to the request.",
            ]),
            "jsont?" => new Schema([
                "type" => ["object", "string"],
                "additionalProperties" => true,
                "description" =>
                    'A jsont specification for transforming the API response data. You may want to escape this with $hydrate: \'literal\'.' .
                    "See https://github.com/vanilla/garden-jsont",
            ]),
        ]);
        $schema->setField(
            "description",
            "Hydrate data from an API endpoint on the current site. Only GET requests are supported."
        );
        return $schema;
    }
}
