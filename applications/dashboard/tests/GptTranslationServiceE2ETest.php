<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard;

use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\OpenAI\OpenAIClient;

/**
 * Run the GPT TTranslationService longrunnertests with the real OpenAI config.
 *
 * This test suites requires to set the env variables in phpunit.xml file:
 * - AZURE_GPT35_DEPLOYMENTURL
 * - AZURE_GPT35_SECRET
 * - AZURE_GPT4_DEPLOYMENTURL
 * - AZURE_GPT4_SECRET
 *
 */
class GptTranslationServiceE2ETest extends GptTranslationServiceTest
{
    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $openAIClient = $this->container()->get(OpenAIClient::class);
        parent::setUp();
        $deploymentURl35 = getenv("AZURE_GPT35_DEPLOYMENTURL");
        $secretURl35 = getenv("AZURE_GPT35_SECRET");
        $deploymentURl4 = getenv("AZURE_GPT4_DEPLOYMENTURL");
        $secretURl4 = getenv("AZURE_GPT4_SECRET");

        if ((!$deploymentURl35 || !$secretURl35) && (!$deploymentURl4 || $secretURl4)) {
            $this->markTestSkipped("Azure GPT is not configured for testing");
        }

        $config = Gdn::getContainer()->get(ConfigurationInterface::class);
        $config->saveToConfig("azure.gpt35.deploymentUrl", $deploymentURl35);
        $config->saveToConfig("azure.gpt35.secret", $secretURl35);
        $config->saveToConfig("azure.gpt4.deploymentUrl", $deploymentURl4);
        $config->saveToConfig("azure.gpt4.secret", $secretURl4);

        $this->container()->setInstance(OpenAIClient::class, $openAIClient);
    }
}
