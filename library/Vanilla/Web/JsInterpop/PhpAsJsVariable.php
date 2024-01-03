<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

use Vanilla\Web\TwigRenderTrait;

/**
 * Class that handles rendering an PHP object or array in a javascript context.
 */
class PhpAsJsVariable
{
    use TwigRenderTrait;
    /** @var array<string, mixed> */
    private array $variables;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $variables Mapping of global js variable to json encodable value.
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * Render the string representation of the script contents.
     *
     * @return string
     */
    public function __toString()
    {
        $result = "";
        foreach ($this->variables as $variableName => $variableValue) {
            $encodedData = json_encode($variableValue);

            $result .= "window['{$variableName}'] = {$encodedData};";
        }
        return $result;
    }
}
