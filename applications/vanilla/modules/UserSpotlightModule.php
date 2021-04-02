<?php
/**
 * @author Hovhannes Hambardzumyan <hovhannes.hambardzumyan@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Widget to spotlight a user.
 */
class UserSpotlightModule extends AbstractReactModule {

    /** @var UserModel */
    private $userModel;

    /** @var int $userID */
    private $userID;

    /** @var string|null */
    public $title = null;

    /** @var string|null */
    public $description = null;

    /**
     * UserSpotlightModule Constructor
     *
     * @param UserModel $userModel
     */
    public function __construct(\UserModel $userModel) {
        parent::__construct();
        $this->userModel = $userModel;
    }

    /**
     * Set userID.
     *
     * @param int $userID
     */
    public function setUserID(int $userID) {
        $this->userID = $userID;
    }

    /**
     * Get userID.
     *
     * @return int
     */
    public function getUserID(): int {
        return $this->userID;
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array {
        $data = $this->getData();
        if ($data === null) {
            return null;
        }
        if (count($data) === 0) {
            return null;
        }
        $props = [];
        $props['title'] = $this->getTitle();
        $props['description'] = $this->description;
        $props['userInfo'] = $data;

        $props = $this->getSchema()->validate($props);

        return $props;
    }

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return 'UserSpotlight';
    }

    /**
     * Create a schema of the props for the component.
     *
     * @return Schema
     */
    public static function getSchema(): Schema {
        return Schema::parse([
            'title:s?',
            'description:s?',
            'userInfo?' => Schema::parse([
                'userID:i',
                'url:s?',
                'photoUrl:s?',
                'name:s?',
                'title:s?',
                'label:s?',
                'dateLastActive?'
            ])
        ]);
    }


    /**
     * @return array|null
     */
    protected function getData(): ?array {
        if (!$this->userID) {
            return null;
        }

        $user = $this->userModel->getFragmentByID($this->userID);

        if (count($user) === 0) {
            return null;
        }
        return $user;
    }

    /**
     * Return the module's title
     *
     * @return string|null
     */
    protected function getTitle(): ?string {
        return $this->title;
    }

    /**
     * Return the module's description
     *
     * @return string|null
     */
    protected function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return "User Spotlight";
    }

    /**
     * @return Schema
     */
    public static function getWidgetSchema(): Schema {
        $widgetSchema = Schema::parse([
            'userID?' => [
                'type' => 'integer',
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'User',
                        'Choose a user.',
                        'Search...'
                    ),
                    new ApiFormChoices(
                        '/api/v2/users/by-names?name=%s*',
                        '/api/v2/users/%s',
                        'userID',
                        'name'
                    )
                )
            ],
            'title:s?' => [
                'x-control' => SchemaForm::textBox(new FormOptions('Title', 'Set a custom title.'))
            ],
            'description:s?' => [
                'x-control' => SchemaForm::textBox(
                    new FormOptions('Description', 'Set a custom description.'),
                    'textarea'
                )
            ],
        ]);

        return SchemaUtils::composeSchemas($widgetSchema);
    }
}
