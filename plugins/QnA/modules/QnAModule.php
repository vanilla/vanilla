<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license https://vanillaforums.com Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Widgets\AbstractWidgetModule;

/**
 * Question & Answer Module
 */
class QnAModule extends AbstractWidgetModule {

    /** @var int limit */
    private $limit = 10;

    /** @var string */
    private $title = "";

    /**
     * @var bool
     */
    private $acceptedAnswer = true;

    /** @var DiscussionModel $discussionModel */
    private $discussionModel;

    /** @var CategoryModel $categoryModel */
    private $categoryModel;

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void {
        $this->limit = $limit;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        $this->title = $title;
    }

    /**
     * @param bool $acceptedAnswer
     */
    public function setAcceptedAnswer(bool $acceptedAnswer): void {
        $this->acceptedAnswer = $acceptedAnswer;
    }

    /**
     * QnaAnswersModule constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(DiscussionModel $discussionModel, CategoryModel $categoryModel) {
        $this->discussionModel = $discussionModel;
        $this->categoryModel = $categoryModel;
    }

    /**
     * Get the data for the module.
     */
    public function getData() {
        $where = ['Type' => 'Question'];

        $where['QnA'] = $this->acceptedAnswer ? 'Accepted' : ['Answered', 'Unanswered'];

        $visibleCategoriesResult = $this->categoryModel->getVisibleCategoryIDs(['filterHideDiscussions' => true]);
        if ($visibleCategoriesResult !== true) {
            $where['d.CategoryID'] = $visibleCategoriesResult;
        }

        $items = $this->discussionModel->getWhere($where, null, null, $this->limit)->resultObject();

        return $items;
    }

    /**
     * Set target
     *
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * Get widget name.
     * @return string
     */
    public static function getWidgetName(): string {
        return t('Questions and Answers');
    }

    /**
     * Get widget schema.
     *
     * @return Schema
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'title' => [
                'type' => 'string',
                'nullable' => true,
                'minLength' => 0,
                'x-control' => SchemaForm::textBox(new FormOptions('Title', 'Set a custom title.'))
            ],
            'acceptedAnswer' => [
                'type' => 'boolean',
                'default' => true,
                'x-control' => SchemaForm::toggle(
                    new FormOptions(
                        'Answered questions only',
                        'Show only answered questions'
                    )
                )
            ]
        ]);
    }

    /**
     * Render view
     *
     * @return string
     */
    public function toString() {
        $items = $this->getData();
        $this->setData('discussions', $items);
        $this->setData('title', $this->title);

        return parent::toString();
    }
}
