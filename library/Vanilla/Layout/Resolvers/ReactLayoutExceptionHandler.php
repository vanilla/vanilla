<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Resolvers;

use Garden\Http\HttpResponseException;
use Garden\Hydrate\DataHydrator;
use Garden\Web\Exception\HttpException;
use Vanilla\Utility\DebugUtils;
use Vanilla\Web\TwigRenderTrait;

/**
 * Exception handler for the layout service.
 *
 * Propagates errors up to the nearest React node and replaces them with a React error component.
 */
class ReactLayoutExceptionHandler implements \Garden\Hydrate\ExceptionHandlerInterface
{
    use TwigRenderTrait;

    /**
     * @inheritdoc
     */
    public function handleException(\Throwable $ex, array $data, array $params)
    {
        $hydrateKey = $data[DataHydrator::KEY_HYDRATE] ?? "";
        $isReactNode = stringBeginsWith($hydrateKey, "react") || stringBeginsWith($hydrateKey, "section");
        if (!$isReactNode) {
            // Propagate the exception up until we hit a React node.
            throw $ex;
        }

        $props = [
            "layoutDefinition" => $data,
            "componentName" => $hydrateKey ?: null,
            "message" => $ex->getMessage(),
        ];

        if ($ex instanceof HttpException) {
            $props["message"] = implode("\n", [$props["message"], $ex->getDescription()]);
        }

        if (DebugUtils::isDebug()) {
            $props["trace"] = DebugUtils::stackTraceString($ex->getTrace());
        }

        $seoTpl = <<<TWIG
<div class="error">
<h3><strong>Component {{ componentName }} had an error.</strong></p></h3>
<p>{{ message }}</p>
{% if trace %}
<pre class="trace">{{ trace }}</pre>
{% endif %}
</div>
TWIG;
        $seoContent = $this->renderTwigFromString($seoTpl, $props);

        return [
            '$reactComponent' => "LayoutError",
            '$reactProps' => $props,
            '$seoContent' => $seoContent,
        ];
    }
}
