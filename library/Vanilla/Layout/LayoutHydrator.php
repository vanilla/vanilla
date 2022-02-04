<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Garden\Container\Container;
use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Middleware\AbstractMiddleware;
use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Hydrate\Resolvers\ParamResolver;
use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Layout\Middleware\LayoutRoleFilterMiddleware;
use Vanilla\Layout\Middleware\VisibilityMiddleware;
use Vanilla\Layout\Resolvers\ReactLayoutExceptionHandler;
use Vanilla\Layout\Resolvers\ApiResolver;
use Vanilla\Layout\Resolvers\ReactResolver;
use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Layout\Section\SectionOneColumn;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Layout\View\AbstractCustomLayoutView;
use Vanilla\Layout\View\CommonLayoutView;
use Vanilla\Layout\View\HomeLayoutView;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Web\PageHead;
use Vanilla\Web\PageHeadInterface;
use Vanilla\Widgets\React\BannerReactWidget;
use Vanilla\Widgets\React\HtmlReactWidget;
use Vanilla\Widgets\React\LeaderboardWidget;
use Vanilla\Widgets\React\QuickLinksWidget;
use Vanilla\Widgets\React\WidgetContainerReactWidget;
use Vanilla\Widgets\Schema\ReactSingleChildSchema;

/**
 * Class for hydrating layouts with the vanilla/garden-hydrate library.
 *
 * It collects data resolvers and layout view definitions so that it can take a layout specification
 * and resolve the data inside them into a form that can be rendered by the frontend.
 */
final class LayoutHydrator {

    /** @var string */
    public const PARAM_PAGE_HEAD = '_pageHead';

    /** @var Container */
    private $container;

    /** @var CommonLayoutView */
    private $commonLayout;

    /** @var array{string, AbstractCustomLayoutView} */
    private $layoutViews = [];

    /** @var DataHydrator */
    private $dataHydrator;

    /** @var PageHead */
    private $pageHead;

    /**
     * DI.
     *
     * @param Container $container
     * @param CommonLayoutView $commonLayout
     * @param ReactLayoutExceptionHandler $reactExceptionHandler
     */
    public function __construct(
        Container $container,
        CommonLayoutView $commonLayout,
        ReactLayoutExceptionHandler $reactExceptionHandler
    ) {
        $this->container = $container;
        $this->commonLayout = $commonLayout;
        $this->dataHydrator = new DataHydrator();
        $this->dataHydrator->setExceptionHandler($reactExceptionHandler);
        $this->pageHead = $container->get(PageHead::class);

        // Register core resolvers.
        $this->addResolver($container->get(ApiResolver::class));

        // Register core react widget resolvers.
        $this
            ->addReactResolver(HtmlReactWidget::class)
            ->addReactResolver(SectionThreeColumns::class)
            ->addReactResolver(SectionTwoColumns::class)
            ->addReactResolver(SectionOneColumn::class)
            ->addReactResolver(SectionFullWidth::class)
            ->addReactResolver(BannerReactWidget::class)
            ->addReactResolver(WidgetContainerReactWidget::class)
            ->addReactResolver(QuickLinksWidget::class)
            ->addReactResolver(LeaderboardWidget::class)
        ;

        $this->addLayoutView($container->get(HomeLayoutView::class));
        $this->addMiddleware($container->get(LayoutRoleFilterMiddleware::class));
        $this->addMiddleware($container->get(VisibilityMiddleware::class));
    }

    /**
     * Add a layout view type.
     *
     * @param AbstractCustomLayoutView $layoutView
     *
     * @return LayoutHydrator
     */
    public function addLayoutView(AbstractCustomLayoutView $layoutView): LayoutHydrator {
        $this->layoutViews[$layoutView->getType()] = $layoutView;
        return $this;
    }

    /**
     * Get all the registered layout view types.
     *
     * @return string[]
     */
    public function getLayoutViewTypes(): array {
        return array_keys($this->layoutViews);
    }


    /**
     * Add a data middleware to the service.
     *
     * @param AbstractMiddleware $middleware
     *
     * @return $this
     */
    public function addMiddleware(AbstractMiddleware $middleware): LayoutHydrator {
        $this->dataHydrator->addMiddleware($middleware);
        return $this;
    }


    /**
     * Add a data resolver to the service.
     *
     * @param AbstractDataResolver $dataResolver
     *
     * @return $this
     */
    public function addResolver(AbstractDataResolver $dataResolver): LayoutHydrator {
        $this->dataHydrator->addResolver($dataResolver);
        return $this;
    }

    /**
     * Add a resolver for a react widget.
     *
     * @param class-string<AbstractReactModule> $reactModuleClass
     */
    public function addReactResolver(string $reactModuleClass): LayoutHydrator {
        $this->dataHydrator->addResolver(new ReactResolver($reactModuleClass, $this->container));
        return $this;
    }

    /**
     * Get the schema for the layout view parameters.
     *
     * @param AbstractCustomLayoutView|null $layoutView
     * @param bool $includeResolvedSchema Include the resolved parameter values in the schema.
     *
     * @return Schema
     */
    private function getViewParamSchema(?AbstractCustomLayoutView $layoutView, bool $includeResolvedSchema = false): Schema {
        $schema = $this->commonLayout->getParamInputSchema();

        if ($includeResolvedSchema) {
            $schema = $schema->merge($this->commonLayout->getParamResolvedSchema());
        }

        if ($layoutView !== null) {
            $schema = $schema->merge($layoutView->getParamInputSchema());
            if ($includeResolvedSchema) {
                $schema = $schema->merge($layoutView->getParamResolvedSchema());
            }
        }
        return $schema;
    }

    /**
     * Get all parameter names for a view type.
     *
     * @param AbstractCustomLayoutView|null $layoutView
     * @param ParamResolver $paramResolver
     *
     * @return ParamResolver
     */
    private function applyParamsNamesToResolver(?AbstractCustomLayoutView $layoutView, ParamResolver $paramResolver): ParamResolver {
        $paramSchema = $this->getViewParamSchema($layoutView, true);
        $enumValues = [];
        $enumDescriptions = [];

        $extractRecursive = function (array $properties, string $rootKey = '') use (&$enumValues, &$extractRecursive, &$enumDescriptions) {
            foreach ($properties as $propertyKey => $propertyValue) {
                $enumValues[] = $rootKey . $propertyKey;
                $enumDescriptions[] = $propertyValue['description'] ?? '';
                if (isset($propertyValue['properties'])) {
                    $extractRecursive($propertyValue['properties'], $rootKey . $propertyKey . '/');
                }
            }
        };

        $extractRecursive($paramSchema->getField("properties", []));

        $paramSchema = $paramResolver->getSchema();
        $paramSchema->setField('properties.ref.enum', $enumValues);
        $paramSchema->setField('properties.ref.enumDescriptions', $enumDescriptions);

        return $paramResolver;
    }

    /**
     * Validate and hydrate provided layout.
     *
     * @param string $layoutViewType Layout Type.
     * @param array $params parameters to hydrate the layout.
     * @param array $layout layout data
     * @param bool $includeMeta should metadata be included?
     *
     * @return array returns hydrated content.
     */
    public function hydrateLayout(string $layoutViewType, array $params, array $layout, ?bool $includeMeta = true): array {
        $cleanPageHead = clone $this->pageHead;

        // Validate the params.
        $params = $this->resolveParams($layoutViewType, $params, $cleanPageHead);

        $hydrator = $this->getHydrator($layoutViewType);

        $result = $hydrator->resolve($layout, $params);
        if ($includeMeta) {
            // Apply pageHead meta
            $result['seo'] = [
                'title' => $cleanPageHead->getSeoTitle(),
                'description' => $cleanPageHead->getSeoDescription(),
                'meta' => $cleanPageHead->getMetaTags(),
                'links' => $cleanPageHead->getLinkTags()];
        }
        return $result;
    }
    /**
     * Get the full set of resolved params based on the input params.
     *
     * @param string|null $layoutViewType
     * @param array $inputParams
     * @param PageHeadInterface|null $pageHead page header for metadata.
     *
     * @return array
     *
     * @throws ValidationException If the params are invalid.
     */
    public function resolveParams(?string $layoutViewType, array $inputParams, ?PageHeadInterface $pageHead = null): array {
        $layoutView = $this->getLayoutViewType($layoutViewType);
        $inputSchema = $this->getViewParamSchema($layoutView);
        $inputParams = $inputSchema->validate($inputParams);

        $resolvedParamSchema = $this->getViewParamSchema($layoutView, true);
        $resolvers = array_filter([$this->commonLayout, $layoutView]);
        $resolvedParams = $inputParams;
        foreach ($resolvers as $resolver) {
            $resolvedParams = $resolver->resolveParams($resolvedParams, $pageHead);
        }
        $resolvedParams = $resolvedParamSchema->validate($resolvedParams);

        return $resolvedParams;
    }

    /**
     * Get a data hydrator instance for a layout view type.
     *
     * @param string|null $layoutViewType
     *
     * @return DataHydrator
     */
    public function getHydrator(?string $layoutViewType): DataHydrator {
        $dataHydrator = $this->dataHydrator;
        $layoutView = $this->getLayoutViewType($layoutViewType);

        if ($layoutView) {
            $dataHydrator = $layoutView->createHydrator($dataHydrator, $this->container);
        }

        // Configure our param resolver.
        $paramResolver = clone $dataHydrator->getParamResolver();
        $paramResolver = $this->applyParamsNamesToResolver($layoutView, $paramResolver);
        $dataHydrator->addResolver($paramResolver);
        return $dataHydrator;
    }

    /**
     * Get the full hydration schema.
     *
     * @param string|null $layoutViewType The layout view type to apply into the schema.
     *        If one isn't provided then the base schema with no view specific assets or params will be applied.
     *
     * @return Schema
     */
    public function getSchema(?string $layoutViewType): Schema {
        $dataHydrator = $this->getHydrator($layoutViewType);

        $schemaGenerator = $dataHydrator->getSchemaGenerator();
        $schema = Schema::parse([
            'layoutID:s?',
            'name:s?',
            'layoutViewType:s' => [
                'enum' => $this->getLayoutViewTypes(),
            ],
            'layout' => [
                'type' => 'array',
                'items' => new HydrateableSchema(
                    // Allow only sections in the top level.
                    (new ReactSingleChildSchema(null, ReactResolver::HYDRATE_GROUP_SECTION))->getSchemaArray(),
                    null
                ),
            ],
            'dateInserted:dt?',
        ]);
        $schema = $schemaGenerator->decorateSchema($schema);

        return $schema;
    }

    /**
     * Get a layout view type.
     *
     * @param string|null $layoutViewType
     *
     * @return AbstractCustomLayoutView|null
     */
    public function getLayoutViewType(?string $layoutViewType): ?AbstractCustomLayoutView {
        $layoutView = $this->layoutViews[$layoutViewType] ?? null;
        if ($layoutViewType !== null && $layoutView === null) {
            throw new NotFoundException('LayoutViewType', ['layoutViewType' => $layoutViewType]);
        }
        return $layoutView;
    }
}
