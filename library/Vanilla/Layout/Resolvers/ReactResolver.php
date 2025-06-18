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
use Vanilla\Dashboard\Models\FragmentModel;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Layout\LayoutAssetAwareInterface;
use Vanilla\Layout\LayoutAssetAwareTrait;
use Vanilla\Layout\Section\AbstractLayoutSection;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Utility\Timers;
use Vanilla\Web\PageHeadAwareInterface;
use Vanilla\Web\PageHeadAwareTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\FragmentMeta;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Data resolver for hydrating a react widget.
 */
class ReactResolver extends AbstractDataResolver implements
    LayoutAssetAwareInterface,
    PageHeadAwareInterface,
    HydrateAwareInterface
{
    use PageHeadAwareTrait;
    use LayoutAssetAwareTrait;
    use HydrateAwareTrait;

    public const HYDRATE_GROUP_SECTION = "section";
    public const HYDRATE_GROUP_REACT = "react";

    /**
     * @var string
     * @psalm-var class-string<ReactWidgetInterface>
     */
    private $reactWidgetClass;

    /** @var Container */
    protected $container;

    /**
     * Constructor.
     *
     * @param class-string<ReactWidgetInterface> $reactWidgetClass
     * @param Container $container This is a module factory, so we need the container to instantiate modules.
     */
    public function __construct(string $reactWidgetClass, Container $container)
    {
        $this->reactWidgetClass = $reactWidgetClass;
        $this->container = $container;
    }

    /**
     * @return class-string<ReactWidgetInterface>
     */
    public function getReactWidgetClass(): string
    {
        return $this->reactWidgetClass;
    }

    /**
     * @return ReactWidgetInterface
     */
    protected function createWidgetInstance(): ReactWidgetInterface
    {
        return $this->container->get($this->reactWidgetClass);
    }

    /**
     * @inheritdoc
     */
    protected function resolveInternal(array $data, array $params)
    {
        $span = Timers::instance()->startGeneric("hydrateReact", [
            "widgetClass" => $this->reactWidgetClass,
        ]);
        try {
            $testID = $data['$reactTestID'] ?? null;
            if (isset($data['$reactTestID'])) {
                unset($data['$reactTestID']);
            }
            $module = $this->createWidgetInstance();

            $this->addWidgetName($module->getComponentName());

            if ($module instanceof PageHeadAwareInterface && $this->pageHead !== null) {
                $module->setPageHead($this->pageHead);
            }

            if ($module instanceof HydrateAwareInterface && $this->getHydrateParams() !== null) {
                $module->setHydrateParams($this->getHydrateParams());

                foreach ($module->getChildComponentNames() as $childComponentName) {
                    $this->addWidgetName($childComponentName);
                }
            }

            if ($this->getAsset) {
                return [];
            }
            // Apply properties
            if ($module instanceof CombinedPropsWidgetInterface) {
                $module->setProps($data);
            }

            foreach ($data as $name => $value) {
                // Check for a setter method
                if (method_exists($module, $method = "set" . ucfirst($name))) {
                    $module->$method($value);
                } else {
                    $module->$name = $value;
                }
            }

            $props = $module->getProps($params);
            if ($props === null) {
                return null;
            }

            // Returns something matching ReactChildrenSchema.
            $result = [
                '$seoContent' => $module->renderSeoHtml($props),
                '$reactComponent' => $module->getComponentName(),
                '$reactProps' => $props,
            ];

            if ($testID !== null) {
                $result['$reactTestID'] = $testID;
            }
            return $result;
        } catch (\Throwable $t) {
            throw $t;
        } finally {
            $span->finish();
        }
    }

    /**
     * Check if a node is a react node definition.
     *
     * @param mixed $node A hydrated node.
     *
     * @return bool
     */
    public static function isReactNode($node): bool
    {
        return ArrayUtils::isArray($node) && isset($node['$reactComponent']) && isset($node['$reactProps']);
    }

    /**
     * Get allowed sections for a widget.
     *
     * @return array
     */
    public function getAllowedSectionIDs(): array
    {
        /** @var class-string<ReactWidgetInterface> $widgetClass */
        $widgetClass = $this->getReactWidgetClass();
        return $widgetClass::getAllowedSectionIDs();
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): ?Schema
    {
        /** @var ReactWidgetInterface $class */
        $class = $this->reactWidgetClass;
        $schema = $class::getWidgetSchema();

        $availableFragmentTypes = $this->container->get(FragmentModel::class)->getUsedFragmentTypes();

        $implProperties = [];
        foreach ($class::getFragmentClasses() as $fragmentClass) {
            $fragmentType = $fragmentClass::getFragmentType();
            if (!in_array($fragmentType, $availableFragmentTypes)) {
                continue;
            }
            $implProperties[$fragmentType] = $this->fragmentTypeSchema($fragmentClass);
        }

        if (!empty($implProperties)) {
            $schema = Schema::parse([
                "\$fragmentImpls?" => new Schema([
                    "type" => "object",
                    "properties" => $implProperties,
                    "x-control" => SchemaForm::section(new FormOptions("Customization")),
                ]),
            ])->merge($schema);
        }

        if ($schema->getField("description", null) === null) {
            $schema->setField("description", $class::getWidgetName());
        }

        $schema->setField("properties.\$reactTestID", [
            "type" => "string",
        ]);

        return $schema;
    }

    /**
     * @param class-string<FragmentMeta> $fragmentTypeClass
     * @return Schema
     */
    private function fragmentTypeSchema(string $fragmentTypeClass): Schema
    {
        $fragmentType = $fragmentTypeClass::getFragmentType();
        $label = $fragmentTypeClass::getName();
        $inStyleguide = $fragmentTypeClass::isAvailableInStyleguide();

        $staticOptions = [];
        if ($inStyleguide) {
            $staticOptions[] = [
                "label" => t("System"),
                "value" => "system",
            ];
        }

        return Schema::parse([
            "fragmentUUID?" => new Schema([
                "type" => "string",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(label: $label, placeholder: $inStyleguide ? t("Style Guide Default") : t("System")),
                    choices: new ApiFormChoices(
                        indexUrl: "/api/v2/fragments?fragmentType={$fragmentType}&status=active",
                        singleUrl: "/api/v2/fragments/%s",
                        valueKey: "fragmentUUID",
                        labelKey: "name",
                        staticOptions: $staticOptions
                    )
                ),
            ]),
        ]);
    }

    /**
     * @return string
     */
    private function getHydrateGroup(): string
    {
        if (is_a($this->reactWidgetClass, AbstractLayoutSection::class, true)) {
            return self::HYDRATE_GROUP_SECTION;
        } else {
            return self::HYDRATE_GROUP_REACT;
        }
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        /** @var ReactWidgetInterface $module */
        $module = $this->reactWidgetClass;
        return self::HYDRATE_GROUP_REACT . "." . $module::getWidgetID();
    }

    /**
     * @inheritdoc
     */
    public function getHydrateGroups(): array
    {
        return [$this->getHydrateGroup()];
    }

    /**
     * @return string
     */
    protected function getWidgetName(): string
    {
        /** @var ReactWidgetInterface $widgetClass */
        $widgetClass = $this->getReactWidgetClass();
        return $widgetClass::getWidgetName();
    }

    /**
     * Get a catalog item for the resolver.
     *
     * @param DataHydrator $dataHydrator
     *
     * @return array
     */
    public function asCatalogItem(DataHydrator $dataHydrator): array
    {
        /** @var ReactWidgetInterface $widgetClass */
        $widgetClass = $this->getReactWidgetClass();

        $componentName = $widgetClass::getComponentName();
        $widgetIconUrl = asset($widgetClass::getWidgetIconPath(), true);
        $widgetName = $this->getWidgetName();
        $schema = $this->getSchema();

        SchemaUtils::fixObjectDefaultSerialization($schema);

        $fragmentClasses = $widgetClass::getFragmentClasses();
        $fragmentTypes = [];
        /** @var class-string<FragmentMeta> $fragmentClass */
        foreach ($fragmentClasses as $fragmentClass) {
            $fragmentTypes[] = $fragmentClass::getFragmentType();
        }

        $result = [
            '$reactComponent' => $componentName,
            "schema" => $schema,
            "iconUrl" => $widgetIconUrl,
            "name" => $widgetName,
            "fragmentTypes" => $fragmentTypes,
            "widgetGroup" => $widgetClass::getWidgetGroup(),
        ];

        if (is_a($widgetClass, AbstractLayoutAsset::class, true)) {
            $result += [
                "isRequired" => $widgetClass::isRequired(),
            ];
        } elseif (is_a($widgetClass, AbstractLayoutSection::class, true)) {
            $allowedWidgetIDs = [];
            foreach ($dataHydrator->getResolvers() as $resolver) {
                if (
                    $resolver instanceof ReactResolver &&
                    in_array($widgetClass::getWidgetID(), $resolver->getAllowedSectionIDs())
                ) {
                    $allowedWidgetIDs[] = $resolver->getType();
                }
            }

            $result += [
                "allowedWidgetIDs" => $allowedWidgetIDs,
            ];
        }

        return $result;
    }
}
