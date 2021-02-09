<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license https://vanillaforums.com Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Widgets\AbstractWidgetModule;

/**
 * Question & Answer Module
 */
class QnAModule extends AbstractWidgetModule {

    const ALL_QUESTIONS = 'all';

    /** @var int limit */
    private $limit = 10;

    /** @var string */
    private $title = "";

    /**
     * @var string
     */
    private $questionFilter;

    /** @var DiscussionModel $discussionModel */
    private $discussionModel;

    /** @var CategoryModel $categoryModel */
    private $categoryModel;

    /**
     * @param int $limit
     */
    public function setLimit(int $limit = 10): void {
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
        $this->questionFilter = $acceptedAnswer ? QnaModel::ACCEPTED : QnaModel::UNANSWERED;
    }

    /**
     * @param string $questionFilter
     */
    public function setQuestionFilter(string $questionFilter = QnaModel::ACCEPTED): void {
        $this->questionFilter = $questionFilter;
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

        if ($this->questionFilter !== self::ALL_QUESTIONS) {
            $where['QnA'] = $this->questionFilter;
        }

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
                'x-control' => SchemaForm::textBox(new FormOptions('Title', 'Set a custom title.')),
            ],
            'questionFilter' => [
                'type' => 'string',
                'default' => 'Accepted',
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'Question filter',
                        'Set the filter'
                    ),
                    new StaticFormChoices(
                        [
                            QnaModel::ACCEPTED => t('Accepted answer only'),
                            QnaModel::ANSWERED => t('Answered questions only'),
                            QnaModel::UNANSWERED => t('Unanswered questions only'),
                            self::ALL_QUESTIONS => t('All'),
                        ]
                    )
                ),
            ],
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
