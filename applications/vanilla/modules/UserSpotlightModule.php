<?php
/**
 * @author Hovhannes Hambardzumyan <hovhannes.hambardzumyan@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forum\Widgets\UserSpotlightWidgetTrait;
use Vanilla\InjectableInterface;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Widget to spotlight a user.
 */
class UserSpotlightModule extends AbstractReactModule implements InjectableInterface
{
    use UserSpotlightWidgetTrait;

    /** @var string|null */
    private $title = null;

    /** @var string|null */
    private $description = null;

    /** @var int */
    private $userID = -1;

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array
    {
        $data = $this->getUserFragment($this->getUserID());

        if (is_null($data) || count($data) === 0) {
            return null;
        }

        $props = [];
        $props["title"] = $this->getTitle();
        $props["description"] = $this->getDescription();
        $props["userInfo"] = $data;
        $props = $this->getSchema()->validate($props);

        // kludge containerOptions for the module
        $props["containerOptions"] = ["borderType" => "shadow"];

        return $props;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "UserSpotlightWidget";
    }

    /**
     * Create a schema of the props for the component.
     *
     * @return Schema
     */
    public static function getSchema(): Schema
    {
        return Schema::parse(["title:s?", "description:s?", "userInfo?" => new UserFragmentSchema()]);
    }

    /**
     * Return the module's title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * Return the module's description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "User Spotlight";
    }

    /**
     * @return int
     */
    public function getUserID(): int
    {
        return $this->userID;
    }

    /**
     * @param int $userID
     */
    public function setUserID(int $userID): void
    {
        $this->userID = $userID;
    }

    /**
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::getApiSchema(),
            Schema::parse([
                "title:s?" => [
                    "x-control" => SchemaForm::textBox(new FormOptions("Title", "Set a custom title.")),
                ],
                "description:s?" => [
                    "x-control" => SchemaForm::textBox(
                        new FormOptions("Description", "Set a custom description."),
                        "textarea"
                    ),
                ],
            ])
        );
    }
}
