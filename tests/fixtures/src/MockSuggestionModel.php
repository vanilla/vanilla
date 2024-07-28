<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

use DiscussionModel;
use Vanilla\Dashboard\Models\AiSuggestionSourceInterface;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormChoicesInterface;

class MockSuggestionModel implements AiSuggestionSourceInterface
{
    /** @var DiscussionModel  */
    private DiscussionModel $discussionModel;

    /**
     * AI Suggestion constructor.
     *
     * @param DiscussionModel $discussionModel
     */
    public function __construct(DiscussionModel $discussionModel)
    {
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritdoc
     */
    public function generateSuggestions(array $discussion, string $keywords): array
    {
        $this->discussionModel->formatField($discussion, "Body", $discussion["Format"]);

        $formattedResult = [
            [
                "format" => "Vanilla",
                "type" => $this->getName(),
                "id" => 0,
                "url" => "someplace.com/here",
                "title" => "answer 1",
                "summary" => "This is how you do this.",
                "hidden" => false,
            ],
            [
                "format" => "Vanilla",
                "type" => $this->getName(),
                "id" => 1,
                "url" => "someplace.com/else",
                "title" => "answer 2",
                "summary" => "This is how you do this a different way.",
                "hidden" => false,
            ],
            [
                "format" => "Vanilla",
                "type" => $this->getName(),
                "id" => 2,
                "url" => "someplace.com/else1",
                "title" => "answer 3",
                "summary" => "This is how you do this, a third way.",
                "hidden" => false,
            ],
        ];

        return $formattedResult;
    }

    public function getName(): string
    {
        return "mockSuggestion";
    }

    public function getExclusionDropdownChoices(): ?FormChoicesInterface
    {
        return new ApiFormChoices(
            "/api/v2/categories/search?query=%s&limit=30",
            "/api/v2/categories/%s",
            "categoryID",
            "name"
        );
    }

    public function getToggleLabel(): string
    {
        return t("Mock Source for testing");
    }

    public function getExclusionLabel(): ?string
    {
        return t("Where to stop mocking about");
    }
}
