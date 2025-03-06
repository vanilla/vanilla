<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace MachineTranslation\Services;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ServerException;
use Gdn;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;
use Vanilla\OpenAI\OpenAIClient;
use Vanilla\OpenAI\OpenAIPrompt;

/**
 * Auto translation service.
 *
 * Used Open IA configuration to translate provided content.
 */
class GptTranslationService implements CommunityMachineTranslationServiceInterface
{
    use LoggerAwareTrait;

    public const CONFIG_KEY = "MachineTranslation.translationServices.Gpt";

    /**
     * GPT Translation constructor.
     *
     * @param ConfigurationInterface $config
     * @param OpenAIClient $openAIClient
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function __construct(private ConfigurationInterface $config, private OpenAIClient $openAIClient)
    {
        $this->logger = Gdn::getContainer()->get(LoggerInterface::class);
    }

    /**
     * Get number of languages from the config.
     *
     * @return int
     */
    public static function getLocaleCount(): int
    {
        return Gdn::config(self::CONFIG_KEY . ".maxLocale", 0);
    }

    /**
     * Get the languages selected for translation.
     *
     * @return array
     */
    public static function getLocaleSelected(): array
    {
        return Gdn::config(self::CONFIG_KEY . ".locales", []);
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
     * Check if GPT translations are enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Gdn::config("Feature.aiFeatures.Enabled") &&
            Gdn::config(CommunityMachineTranslationModel::FEATURE_FLAG) &&
            self::getLocaleCount() > 0 &&
            count(self::getLocaleSelected()) > 0;
    }

    /**
     * Generate original language from a provided text.
     *
     * @param array $request
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
            $language = $return["language"][0] ?? (Gdn::locale()->current() ?? Gdn::locale()->default());
        } catch (\Exception $e) {
            $this->logger->warning("Error getting language of the string", [
                "exception" => $e->getMessage(),
            ]);
            $language = "en";
        }
        return $language;
    }

    /**
     * Translate every field from an array of objects.
     *
     * @param array $texts [Key => Value]
     *
     * @return array
     * @throws ClientException
     * @throws ServerException
     */
    public function translate(array $texts, string $originLanguage): array
    {
        $translationSchema = GptTranslationService::getTranslationSchema();
        $prompt = GptTranslationService::getBasePrompt($originLanguage);
        $prompt->addUserMessage($texts);
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
        $languages = GptTranslationService::getLocaleSelected();
        if (count($languages) == 0) {
            throw new ClientException("No languages configured for translation, should not have gotten here.");
        }
        $notTranslated = explode(",", GptTranslationService::wordsNotToTranslate());
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
}
