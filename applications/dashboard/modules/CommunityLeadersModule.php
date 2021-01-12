<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Modules;

use Garden\Schema\Schema;
use Vanilla\Dashboard\UserPointsModel;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\AbstractHomeWidgetModule;

/**
 * Widget with the users having the top points.
 */
class CommunityLeadersModule extends AbstractHomeWidgetModule {

    /** @var string One of the SLOT_TYPE constants */
    private $slotType = UserPointsModel::SLOT_TYPE_WEEK;

    /** @var int|null $categoryID */
    private $categoryID = null;

    /** @var int */
    private $limit = 10;

    /** @var UserPointsModel */
    private $userPointsModel;

    public $contentType = self::CONTENT_TYPE_ICON;
    public $borderType = 'none';
    public $name = null;
    public $maxColumnCount = 5;
    public $iconProps = [
        'border' => [
            'radius' => 100,
        ]
    ];

    /**
     * DI.
     *
     * @param UserPointsModel $userPointsModel
     */
    public function __construct(UserPointsModel $userPointsModel) {
        parent::__construct();
        $this->userPointsModel = $userPointsModel;
    }

    /**
     * @return array
     */
    protected function getItemOptions(): array {
        $options = [
            'contentType' => $this->contentType,
            'display' => $this->display,
            'borderType' => $this->borderType,
            'name' => $this->name,
            'alignment' => 'center',
        ];
        if (!empty($this->iconProps)) {
            $options['iconProps'] = $this->iconProps;
        }
        return $options;
    }

    /**
     * @return string|null
     */
    protected function getTitle(): ?string {
        $category = $this->userPointsModel->getPointsCategory($this->categoryID);
        return $this->userPointsModel->getTitleForSlotType($this->slotType, $category ? $category['Name'] : '');
    }

    /**
     * @return array|null
     */
    protected function getData(): ?array {
        $users = $this->userPointsModel->getLeaders($this->slotType, $this->categoryID, $this->limit);
        if (count($users) === 0) {
            return null;
        }
        return array_map([$this, 'mapUserToWidgetItem'], $users);
    }

    /**
     * Map a user into a widget item.
     *
     * @param array $user A full user from the database + UserPoints data.
     *
     * @return array
     */
    private function mapUserToWidgetItem(array $user): array {
        $points = $user['Points'] ?? 0;
        return [
            'to' => userUrl($user),
            'iconUrl' => $user['Photo'],
            'name' => $user['Name'],
            'description' => $user['Label'] ?? null,
            'counts' => [
                [
                    'labelCode' => 'Points',
                    'count' => $points,
                ]
            ]
        ];
    }

    /**
     * @param string $slotType
     */
    public function setSlotType(string $slotType): void {
        $this->slotType = $slotType;
    }

    /**
     * @param int|null $categoryID
     */
    public function setCategoryID(?int $categoryID): void {
        $this->categoryID = $categoryID;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void {
        $this->limit = $limit;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return "LeaderBoard (Grid)";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        $ownSchema = Schema::parse([
            'slotType?' => [
                'type' => 'string',
                'default' => 'w',
                'enum' => ['d', 'w', 'm', 'a'],
                'x-control' => SchemaForm::radio(
                    new FormOptions(
                        'Timeframe',
                        'Choose what duration to check for leaders in.'
                    ),
                    new StaticFormChoices(
                        [
                            'd' => 'Daily',
                            'w' => 'Weekly',
                            'm' => 'Monthly',
                            'a' => 'All Time',
                        ]
                    )
                )
            ],
            'limit?' => [
                'type' => 'integer',
                'default' => 10,
                'x-control' => SchemaForm::textBox(
                    new FormOptions(
                        'Limit',
                        'Maximum amount of users to display.'
                    ),
                    'number'
                )
            ],
        ]);


        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(),
            self::widgetColumnSchema(),
            $ownSchema
        );
    }
}
