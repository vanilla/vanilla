<?php
namespace Vanilla\Utility;

/**
 * Trait that serializes an object to json by getting it's public properties.
 */
trait SerializedPublicPropertiesToJsonTrait
{
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $fn = function ($fragment) {
            // We're doing this in a closure so we don't have access to the private/protected properties.
            $vars = get_object_vars($fragment);
            return $vars;
        };
        return $fn($this);
    }
}
