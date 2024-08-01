<?php
/**
 * @author Raphaël Bergina <rbergina@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Models;

use Garden\Web\Data;
use Vanilla\Formatting\FormatService;
use Vanilla\Web\AbstractJsonLDItem;

/**
 * Item to transform an QnA into some JSON-LD data.
 *
 * @see https://developers.google.com/search/docs/data-types/qapage
 */
final class QnAJsonLD extends AbstractJsonLDItem
{
    const TYPE = "QAPage";

    /** @var array */
    private $discussion;

    /** @var array */
    private $acceptedAnswers;

    /** @var FormatService */
    private $formatService;

    /** @var \UserModel */
    private $userModel;

    /**
     * QnAJsonLD constructor.
     *
     * @param array $discussion
     * @param array $acceptedAnswers
     * @param FormatService $formatService
     * @param \UserModel $userModel
     */
    public function __construct(
        array $discussion,
        array $acceptedAnswers,
        FormatService $formatService,
        \UserModel $userModel
    ) {
        $this->discussion = $discussion;
        $this->acceptedAnswers = $acceptedAnswers;
        $this->formatService = $formatService;
        $this->userModel = $userModel;
    }

    /**
     * @inheritdoc
     */
    public function calculateValue(): Data
    {
        $author = $this->userModel->getID($this->discussion["InsertUserID"], DATASET_TYPE_ARRAY);
        $tz = new \DateTimeZone("UTC");
        $dateInserted = new \DateTimeImmutable($this->discussion["DateInserted"], $tz);

        $questionData = [
            "@type" => "Question",
            "name" => $this->discussion["Name"],
            "text" => $this->formatService->renderPlainText($this->discussion["Body"], $this->discussion["Format"]),
            "answerCount" => $this->discussion["CountComments"],
            "dateCreated" => $dateInserted->format("Y-m-d\TH:i:s\Z"),
            "author" => [
                "@type" => "Person",
                "name" => $author["Name"],
            ],
            "acceptedAnswer" => array_map(function ($comment) {
                return $this->getAnswerInfo((array) $comment);
            }, $this->acceptedAnswers),
            // @todo: Define rules for suggestedAnswer
            "suggestedAnswer" => [],
        ];

        return new Data([
            "@type" => self::TYPE,
            "mainEntity" => $questionData,
        ]);
    }

    /**
     * Get answer structured data.
     *
     * @param array $answer
     * @return array
     */
    private function getAnswerInfo(array $answer): array
    {
        $tz = new \DateTimeZone("UTC");
        $dateInserted = new \DateTimeImmutable($answer["DateInserted"], $tz);
        $author = $this->userModel->getID($answer["InsertUserID"], DATASET_TYPE_ARRAY);

        $data = [
            "@type" => "Answer",
            "text" => $this->formatService->renderPlainText($answer["Body"], $answer["Format"]),
            "dateCreated" => $dateInserted->format("Y-m-d\TH:i:s\Z"),
            "url" => \CommentModel::commentUrl($answer),
            "author" => [
                "@type" => "Person",
                "name" => $author["Name"],
            ],
            "upvoteCount" => (int) ($answer["Score"] ?? 0),
        ];

        return $data;
    }
}
