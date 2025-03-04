<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Exception;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Gdn;
use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use UserMetaModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\AiSuggestionModel;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Models\TranslationModel;
use Vanilla\Forum\Models\TranslationPropertyModel;
use Vanilla\Logger;
use Vanilla\Models\AddonModel;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Web\SystemCallableInterface;

/**
 * Auto translation service.
 *
 * Used Open IA configuration to translate provided content.
 */
class JITTTranslationService implements LoggerAwareInterface, SystemCallableInterface
{
    use LoggerAwareTrait;

    public const CONFIG_KEY = "JustInTimeTranslated";
    private const MAX_SUGGESTIONS = 3;

    protected string $domain;

    private array $reporterData = [];

    /**
     * JITT Translation constructor.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @param UserMetaModel $userMetaModel
     * @param LongRunner $longRunner
     * @param \ActivityModel $activityModel
     * @param AiSuggestionModel $aiSuggestionModel
     * @param FormatService $formatService
     */
    public function __construct(
        private ConfigurationInterface $config,
        private OpenAIClient $openAIClient,
        private UserMetaModel $userMetaModel,
        private LongRunner $longRunner,
        private \ActivityModel $activityModel,
        private AiSuggestionModel $aiSuggestionModel,
        private FormatService $formatService,
        private TranslationModel $translationModel,
        private TranslationPropertyModel $translationPropertyModel
    ) {
        $this->logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
    }

    /**
     * Get number of languages from the config.
     *
     * @return int
     */
    public static function languageCount(): int
    {
        return Gdn::config(self::CONFIG_KEY . ".languageCount", 0);
    }

    /**
     * Get the languages selected for translation.
     *
     * @return array
     */
    public static function languageSelected(): array
    {
        return Gdn::config(self::CONFIG_KEY . ".languageSelected", []);
    }

    /**
     * Get Words Not To Translate from the config.
     *
     * @return string
     */
    public static function wordsNotToTranslate(): string
    {
        return Gdn::config(self::CONFIG_KEY . ".wordsNotToTranslate", "");
    }

    /**
     * Check if AI translations are enabled.
     *
     * @return bool
     */
    public function translationEnabled(): bool
    {
        $subcommunityAddon = Gdn::addonManager()->lookupAddon("subcommunities");
        $addonModel = \Gdn::getContainer()->get(AddonModel::class);
        return $addonModel->isEnabledConfig($subcommunityAddon) &&
            $this->languageCount() > 0 &&
            is_array($this->languageSelected()) &&
            count($this->languageSelected()) > 0;
    }

    /**
     * Start the process to generate translations by calling longRunner.
     *
     * @param array $objectToTranslate Object contains what to translate { [0] => ["$textToTranslate" - Key => Value,  object of elements to translate, int $recordID,string $recordType ]}
     *
     * @return TrackingSlipInterface|false
     */
    public function createTranslations(array $objectToTranslate): TrackingSlipInterface|false
    {
        if (!$this->translationEnabled()) {
            return false;
        }

        $action = new LongRunnerAction(self::class, "generateTranslations", [$objectToTranslate]);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * Generate translations for a records LongRunner.
     *
     * @param array $objectToTranslate Object contains what to translate { [0] => ["$textToTranslate" - Key => Value,  object of elements to translate, int $recordID,string $recordType ]}
     *
     * @return Generator|LongRunnerNextArgs
     */
    public function generateTranslations(array $objectToTranslate): Generator|LongRunnerNextArgs
    {
        try {
            $continueTranslating = $objectToTranslate;
            foreach ($objectToTranslate as $index => $translateObject) {
                $recordID = $translateObject["recordID"];
                $recordType = $translateObject["recordType"];
                $toTranslate = $translateObject["textToTranslate"];
                unset($continueTranslating[$index]);
                $continueTranslating = array_values($continueTranslating);
                $translations = $this->translateRequest($toTranslate);

                $translationKeys = [];
                foreach ($translations["translation"] as $locale => $translation) {
                    // If OpenAI returns a single translation for a single key, we need to make it array.
                    if (count($toTranslate) === 1 && !is_array($translation)) {
                        $temp = $translation;
                        $translation = [];
                        foreach ($toTranslate as $key => $value) {
                            $translation[$key] = $temp;
                        }
                    }
                    foreach ($translation as $key => $text) {
                        if (!array_key_exists($key, $translationKeys)) {
                            $translationProperty = $this->translationPropertyModel->getTranslationProperty([
                                "recordID" => $recordID,
                                "recordType" => $recordType,
                                "propertyName" => $key,
                            ]);
                            if (!$translationProperty) {
                                $translationProperty = $this->translationPropertyModel->createTranslationProperty(
                                    $locale,
                                    [
                                        "recordID" => $recordID,
                                        "recordType" => $recordType,
                                        "propertyName" => $key,
                                        "resource" => $recordType,
                                    ]
                                );

                                $translationKey = $translationProperty["translationPropertyKey"];
                            }
                            $translationKeys[$key] = $translationKey;
                        }
                        $this->translationModel->createTranslation($locale, $locale, $translationKeys[$key], $text);
                    }
                }
                yield new LongRunnerSuccessID($recordID);
            }
        } catch (LongRunnerTimeoutException $e) {
            return new LongRunnerNextArgs([$continueTranslating]);
        } catch (Exception $e) {
            yield new LongRunnerFailedID($recordID);
            $this->logger->warning("Error Throws saving translations", [
                "exception" => $e,
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["JITTTranslation"],
            ]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Generate keywords from a discussion.
     *
     * @param array $discussion
     * @return string
     */
    public function generateOriginalLanguage(array $request): string
    {
        foreach ($request as $key => $text) {
            $requestBody = $text;
            break;
        }
        try {
            $prompt = OpenAIPrompt::create()->instruct(
                <<<LANGUAGECHECK
Identify and specify the language of the input you receive, then provide the name of the language as the output.

# Output Format

The response should only include the name of the language in a 2 letter format (e.g., en, sp, en )

# Example

**Input:** Bonjour, comment allez-vous ?
**Output:** fr

**Input:** ¿Cómo estás hoy?
**Output:** sp

**Input:** Hello, how are you?
**Output:** en
LANGUAGECHECK
            );
            $schema = Schema::parse(["language:a" => ""]);
            $prompt->addUserMessage($requestBody);
            $method =
                $this->config->get(OpenAIClient::CONF_GPT4_ENDPOINT) &&
                $this->config->get(OpenAIClient::CONF_GPT4_SECRET)
                    ? OpenAIClient::MODEL_GPT4
                    : OpenAIClient::MODEL_GPT35;
            $return = $this->openAIClient->prompt($method, $prompt, $schema);
            $language = $return["language"][0];
        } catch (\Exception $e) {
            $this->logger->warning("Error getting language of the string", [
                "exception" => $e->getMessage(),
            ]);
            $language = "en";
        }
        return $language;
    }

    /**
     * Turn returned responses into answers to the main question
     *
     * @param array $textToTranslate
     *
     * @return array
     */
    public function translateRequest(array $textToTranslate): array
    {
        $originLanguage = $this->generateOriginalLanguage($textToTranslate);
        $translationSchema = JITTTranslationService::getTranslationSchema();
        $prompt = JITTTranslationService::getBasePrompt($originLanguage);
        $prompt->addUserMessage($textToTranslate);
        $method =
            $this->config->get(OpenAIClient::CONF_GPT4_ENDPOINT) && $this->config->get(OpenAIClient::CONF_GPT4_SECRET)
                ? OpenAIClient::MODEL_GPT4
                : OpenAIClient::MODEL_GPT35;

        $translated = $this->openAIClient->prompt($method, $prompt, $translationSchema);

        return $translated;
    }

    /**
     * Get the base prompt for querying OpenAI for translating requested content.
     *
     * @param string $originLanguage
     *
     * @return OpenAIPrompt
     * @throws ClientException
     */
    public static function getBasePrompt(string $originLanguage): OpenAIPrompt
    {
        $languages = JITTTranslationService::languageSelected();
        if (!is_array($languages) || count($languages) == 0) {
            throw new ClientException("No languages configured for translation, should not have gotten here.");
        }
        $notTranslated = explode(",", JITTTranslationService::wordsNotToTranslate());
        $languageString = "";
        foreach ($languages as $key => $language) {
            if ($originLanguage == $language) {
                continue;
            }
            $languageString .= "- {$language}\n";
        }
        $notTranslatedString = "";
        foreach ($notTranslated as $string) {
            $notTranslatedString .= "- {$string}\n";
        }
        $prompt = OpenAIPrompt::create()->instruct(
            <<<PROMPT
Translate the provided object into the selected target languages, ensuring that specified words remain untranslated.

Provide clear translations while preserving the original format, context, and meaning of the text. Avoid translating or altering the specified words or phrases listed.

# Instructions

1. Identify the input text and the target languages for translation.
2. Review the list of words or phrases provided that must remain untranslated.
3. Skip language that matches input string language.
4. Translate the text into the target languages, ensuring contextual accuracy and retention of the original meaning.
   - Do not translate the specified terms; preserve them exactly as provided, including their formatting (e.g., capitalization or punctuation).
   - Retain the structure of the source content, including sentence order, formatting, and spacing.

# Steps

1. Read the user-provided text.
2. Identify the provided list of words or phrases that must remain untranslated.
3. For each target language:
   - Translate the text accurately.
   - Leave the specified words or phrases unchanged.
4. Ensure that grammar and syntax in the target language accommodate any untranslated terms seamlessly.

# Output Format

Provide translations in json format with clear key for each target language. Keep original object format intact.  Example:

json [["Language 1"] =>[Translated version with specified words left unchanged.],
Repeat for each target language.
]

**Specified Words (DO NOT TRANSLATE):**
$notTranslatedString

**Target Languages:**
$languageString

# Notes

- If the text contains idiomatic expressions, aim for equivalent expressions in the target language while still maintaining the untranslated words as-is.
- For input with complex text or formatting (e.g., bulleted lists, html), ensure that the original structure is preserved in translations.
PROMPT
        );

        return $prompt;
    }

    /**
     * Get the schema for storing the answer response from OpenAI.
     *
     * @return Schema
     */
    public static function getTranslationSchema(): Schema
    {
        return Schema::parse(["translation:o"]);
    }

    public static function getSystemCallableMethods(): array
    {
        return ["generateTranslations"];
    }
}
