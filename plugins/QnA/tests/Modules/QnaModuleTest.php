<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\QnA\Modules;

use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test rendering of the QnA module.
 */
class QnaModuleTest extends StorybookGenerationTestCase {

    use UsersAndRolesApiTestTrait;
    use QnaApiTestTrait;
    use EventSpyTestTrait;

    public static $addons = ['vanilla', 'QnA'];

    /**
     * Test rendering of the QnA module.
     */
    public function testRender() {
        $this->createCategory();
        $this->createQuestion();
        $this->createQuestion();

        // Make an accepted answer.
        $user = $this->createUser();
        $question = $this->runWithUser(function () {
            return $this->createQuestion();
        }, $user);
        $answer = $this->createAnswer();
        $this->runWithUser(function () use ($question, $answer) {
            $this->acceptAnswer($question, $answer);
        }, $user);

        // Make an answer.
        $question2 = $this->createQuestion();
        $this->createAnswer();
        $this->recalculateDiscussionQnA($question2);
        $this->generateStoryHtml('/categories', 'QnA Module');
    }

    /**
     * Event handler to mount a QnA module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender) {
        /** @var \QnAModule $module */
        $module = self::container()->get(\QnAModule::class);
        $module->setAcceptedAnswer(false);
        $module->setTitle('Unanswered');
        $sender->addModule($module);

        /** @var \QnAModule $module */
        $module = self::container()->get(\QnAModule::class);
        $module->setAcceptedAnswer(true);
        $module->setTitle('Accepted');
        $sender->addModule($module);

        /** @var \QnAModule $module */
        $module = self::container()->get(\QnAModule::class);
        $module->setQuestionFilter(\QnaModel::UNANSWERED);
        $module->setTitle('Filter by Unanswered');
        $sender->addModule($module);

        /** @var \QnAModule $module */
        $module = self::container()->get(\QnAModule::class);
        $module->setQuestionFilter(\QnaModel::ANSWERED);
        $module->setTitle('Filter by Answered');
        $sender->addModule($module);

        /** @var \QnAModule $module */
        $module = self::container()->get(\QnAModule::class);
        $module->setQuestionFilter(\QnaModel::ACCEPTED);
        $module->setTitle('Filter by Accepted Answer');
        $sender->addModule($module);

        /** @var \QnAModule $module */
        $module = self::container()->get(\QnAModule::class);
        $module->setQuestionFilter(\QnAModule::ALL_QUESTIONS);
        $module->setTitle('All questions');
        $sender->addModule($module);
    }
}
