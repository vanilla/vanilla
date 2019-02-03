<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

/**
 * Class that handles rendering an PHP object or array in a javascript context.
 */
class PhpAsJsVariable {

    /** @var string */
    private $variableName;

    /** @var array|object */
    private $data;

    /**
     * Constructor.
     *
     * @param string $variableName The name of variable to expose the script on the frontend.
     * @param array|object $data The JSON serializable data that should be serialized for a javascript context.
     */
    public function __construct(string $variableName, $data) {
        $this->variableName = $variableName;
        $this->data = $data;
    }

    /**
     * Render the string representation of the script contents.
     *
     * @return string
     */
    public function __toString() {
        return 'window["' . $this->variableName . '"]=' . json_encode($this->data) . ";\n";
    }
}
