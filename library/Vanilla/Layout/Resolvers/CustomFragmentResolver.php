<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Resolvers;

use Garden\Container\Container;
use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\CustomFragmentWidget;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Extension of {@link ReactResolver} that resolves custom fragments.
 *
 * We need an extension because we don't have a 1:1 mapping between a fragment and PHP classes.
 */
class CustomFragmentResolver extends ReactResolver
{
    /**
     * @param array $fragmentRow
     * @param Container $container
     */
    public function __construct(protected array $fragmentRow, Container $container)
    {
        parent::__construct(CustomFragmentWidget::class, $container);
    }

    /**
     * Use fragment name as widget name.
     *
     * @return string
     */
    protected function getWidgetName(): string
    {
        return $this->fragmentRow["name"];
    }

    /**
     * Create a unique type for this fragment based on the fragmentUUID.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::HYDRATE_GROUP_REACT . ".custom." . $this->fragmentRow["fragmentUUID"];
    }

    /**
     * Override the schema to kludge in fragmentImpl metadata for the layout editor previews.
     * The values from these are never used by the widget (instead the actual DB values are loaded).
     *
     * @inheritdoc
     */
    public function getSchema(): ?Schema
    {
        $schema = parent::getSchema() ?? new Schema(["type" => "object"]);

        $customSchema = $this->fragmentRow["customSchema"] ?? null;
        if (!is_array($customSchema) || empty($customSchema)) {
            $customSchema = ["type" => "object"];
        }
        $ownSchema = new Schema($customSchema);

        $stubFragmentImplSchema = Schema::parse([
            "fragmentImpl" => Schema::parse([
                "fragmentUUID:s" => [
                    "default" => $this->fragmentRow["fragmentUUID"],
                ],
                "fragmentType:s" => [
                    "default" => $this->fragmentRow["fragmentType"],
                ],
            ]),
        ]);

        return SchemaUtils::composeSchemas($schema, $ownSchema, $stubFragmentImplSchema);
    }

    /**
     * @return ReactWidgetInterface
     */
    protected function createWidgetInstance(): ReactWidgetInterface
    {
        $instance = $this->container->getArgs(CustomFragmentWidget::class);
        $instance->setFragmentRow($this->fragmentRow);
        return $instance;
    }
}
