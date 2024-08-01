<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

/**
 * Trait implementing CombinedPropsWidgetInterface.
 */
trait CombinedPropsWidgetTrait
{
    /** @var array */
    protected $props = [];

    /**
     * @inheritdoc
     */
    public function setProps(array $props, bool $merge = false)
    {
        if ($merge) {
            $this->props = array_replace_recursive($this->props, $props);
        } else {
            $this->props = $props;
        }
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        return $this->props;
    }
}
