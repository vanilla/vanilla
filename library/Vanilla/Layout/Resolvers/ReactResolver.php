<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Resolvers;

use Garden\Container\Container;
use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Middleware\AbstractMiddleware;
use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Schema\Schema;
use Vanilla\Layout\Section\AbstractLayoutSection;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\PageHeadAwareInterface;
use Vanilla\Web\PageHeadAwareTrait;
use Vanilla\Web\PageHeadInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Data resolver for hydrating a react widget.
 */
class ReactResolver extends AbstractDataResolver implements PageHeadAwareInterface {
    use PageHeadAwareTrait;

    public const HYDRATE_GROUP_SECTION = "section";
    public const HYDRATE_GROUP_REACT = "react";

    /**
     * @var string
     * @psalm-var class-string<ReactWidgetInterface>
     */
    private $reactWidgetClass;

    /** @var Container */
    private $container;

    /**
     * Constructor.
     *
     * @param class-string<ReactWidgetInterface> $reactWidgetClass
     * @param Container $container This is a module factory, so we need the container to instantiate modules.
     */
    public function __construct(string $reactWidgetClass, Container $container) {
        $this->reactWidgetClass = $reactWidgetClass;
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getReactWidgetClass(): string {
        return $this->reactWidgetClass;
    }

    /**
     * @inheritdoc
     */
    protected function resolveInternal(array $data, array $params) {
        /** @var ReactWidgetInterface $module */
        $module = $this->container->get($this->reactWidgetClass);

        if ($module instanceof PageHeadAwareInterface && $this->pageHead !== null) {
            $module->setPageHead($this->pageHead);
        }

        // Apply properties
        if ($module instanceof CombinedPropsWidgetInterface) {
            $module->setProps($data);
        } else {
            foreach ($data as $name => $value) {
                // Check for a setter method
                if (method_exists($module, $method = 'set'.ucfirst($name))) {
                    $module->$method($value);
                } else {
                    $module->$name = $value;
                }
            }
        }

        $props = $module->getProps($params);
        if ($props === null) {
            return null;
        }

        // Returns something matching ReactChildrenSchema.
        return [
            '$reactComponent' => $module->getComponentName(),
            '$reactProps' => $props,
        ];
    }

    /**
     * Check if a node is a react node definition.
     *
     * @param mixed $node A hydrated node.
     *
     * @return bool
     */
    public static function isReactNode($node): bool {
        return ArrayUtils::isArray($node) && isset($node['$reactComponent']) && isset($node['$reactProps']);
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): ?Schema {
        /** @var ReactWidgetInterface $class */
        $class = $this->reactWidgetClass;
        $schema = $class::getWidgetSchema();
        if ($schema->getField('description', null) === null) {
            $schema->setField('description', $class::getWidgetName());
        }
        return $schema;
    }

    /**
     * @return string
     */
    private function getHydrateGroup(): string {
        if (is_a($this->reactWidgetClass, AbstractLayoutSection::class, true)) {
            return self::HYDRATE_GROUP_SECTION;
        } else {
            return self::HYDRATE_GROUP_REACT;
        }
    }

    /**
     * @inheritdoc
     */
    public function getType(): string {
        /** @var ReactWidgetInterface $module */
        $module = $this->reactWidgetClass;
        return self::HYDRATE_GROUP_REACT . '.' . $module::getWidgetID();
    }

    /**
     * @inheritdoc
     */
    public function getHydrateGroups(): array {
        return [$this->getHydrateGroup()];
    }
}
