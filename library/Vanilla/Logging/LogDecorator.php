<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Garden\Container\Container;
use GPBMetadata\Google\Api\Log;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Ramsey\Uuid\Uuid;
use Vanilla\Logger;
use Vanilla\Site\OwnSite;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Utility\DebugUtils;

/**
 * A decorator for the log that adds default context attributes based on the current request.
 */
class LogDecorator implements LoggerInterface
{
    use LoggerTrait;

    public const SCHEMA_VERSION = "v2";
    public const FIELD_SCHEMA = "_schema";
    public const FIELD_DATA = "data";
    public const TAG_LOG_FAILURE_DECORATOR = "logFailure-decorator";

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * @var \Gdn_Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $staticContextDefaults = [];

    /** @var OwnSite */
    private $ownSite;

    /**
     * @var array
     */
    private $obscureKeys = ["access_token", "authorization", "*password", "*secret"];

    /**
     * Decorate a log context and transform it into the standard structure.
     *
     * This will transform the context significantly, even if the decorator fails to load.
     * If it does fail to decorate a tag will be added with information about the error.
     *
     * @param array $context
     * @return array
     */
    public static function applyLogDecorator(array $context): array
    {
        $schema = $context[self::FIELD_SCHEMA] ?? null;
        if ($schema === self::SCHEMA_VERSION) {
            // We are already decorated.
            return $context;
        }

        try {
            $logDecorator = \Gdn::getContainer()->get(LogDecorator::class);
            $context = $logDecorator->decorateContext($context);
            $logDecorator->obscureContext($context);
        } catch (\Throwable $throwable) {
            $context = Logger::hoistLoggerFields($context);
            $context[Logger::FIELD_TAGS][] = self::TAG_LOG_FAILURE_DECORATOR;
            $context["data"][self::TAG_LOG_FAILURE_DECORATOR] = [
                "message" => $throwable->getMessage(),
                "stacktrace" => DebugUtils::stackTraceString($throwable->getTrace()),
            ];
        }

        return $context;
    }

    /**
     * LogDecorator constructor.
     *
     * NOTE: This gets instantiated very early in the request.
     * Do not add any other direct dependencies in here if possible.
     *
     * @param LoggerInterface $logger
     * @param \Gdn_Request $request
     * @param \Gdn_Session $session
     * @param OwnSite $ownSite
     */
    public function __construct(LoggerInterface $logger, \Gdn_Request $request, \Gdn_Session $session, OwnSite $ownSite)
    {
        $this->session = $session;
        $this->request = $request;
        $this->logger = $logger;
        $this->ownSite = $ownSite;
    }

    /**
     * @param \Gdn_Request $request
     */
    public function setRequest(\Gdn_Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @param OwnSite $ownSite
     */
    public function setOwnSite(OwnSite $ownSite): void
    {
        $this->ownSite = $ownSite;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $context = $this->decorateContext($context);
        $this->obscureContext($context);

        $this->logger->log($level, $message, $context);
    }

    /**
     * Decorate logger context with common fields.
     *
     * @param array $context
     * @return array
     */
    public function decorateContext(array $context = []): array
    {
        $coreFields = Logger::hoistLoggerFields($context);

        $coreContext = [
            self::FIELD_SCHEMA => self::SCHEMA_VERSION,

            // Vanilla App Info
            "site" => [
                "version" => APPLICATION_VERSION,
                "siteID" => $this->ownSite->getSiteID(),
                "accountID" => $this->ownSite->getAccountID(),
            ],
            // Info about the current request.
            "request" => [
                "hostname" => $this->request->getHost(),
                "method" => $this->request->getMethod(),
                "path" => $this->request->getPath(),
                "protocol" => $this->request->getScheme(),
                "url" => $this->request->getUrl(),
                "clientIP" => $this->request->getIP(),
                "requestID" => $this->request->getAttribute("requestID", Uuid::uuid1()->toString()),
                // Kludge until the new logging has rolled out everywhere.
                // Once it has, go update this
                // https://github.com/vanilla/vanillainfrastructure/blob/ab00d6463814ea7aac9c100c98c5c59b185ae921/plugins/vfshared/class.vfshared.plugin.php#L91-L105
                // And remove this line.
                "country" =>
                    $this->staticContextDefaults["request"]["country"] ??
                    ($this->staticContextDefaults["requestCountry"] ?? null),
            ],
        ];

        $userData = [
            // User info
            Logger::FIELD_USERID => $context[Logger::FIELD_USERID] ?? $this->session->UserID,
            Logger::FIELD_TARGET_USERID => $context[Logger::FIELD_TARGET_USERID] ?? null,
        ];

        $this->addUsername(Logger::FIELD_USERID, Logger::FIELD_USERNAME, $userData);
        $this->addUsername(Logger::FIELD_TARGET_USERID, Logger::FIELD_TARGET_USERNAME, $userData);

        $context = array_replace_recursive($coreFields, $coreContext, $userData, $this->staticContextDefaults);
        unset($context["requestCountry"]);

        return $context;
    }

    /**
     * Add a username to a log entry.
     *
     * @param string $idField
     * @param string $nameField
     * @param array $context
     */
    private function addUsername(string $idField, string $nameField, array &$context): void
    {
        if (!array_key_exists($idField, $context) || array_key_exists($nameField, $context)) {
            return;
        }

        if (empty($context[$idField])) {
            $context[$nameField] = "anonymous";
        } else {
            try {
                $userModel = \Gdn::getContainer()->get(\UserModel::class);
                $user = $userModel->getID($context[$idField], DATASET_TYPE_OBJECT);
                if ($user === false) {
                    $context[$nameField] = "unknown";
                } else {
                    $context[$nameField] = $user->Name;
                }
            } catch (\Throwable $e) {
                $context[$nameField] = "failed to load";
            }
        }
    }

    /**
     * Add log context defaults.
     *
     * @param array $defaults
     */
    public function addStaticContextDefaults(array $defaults)
    {
        $this->staticContextDefaults = array_replace($this->staticContextDefaults, $defaults);
    }

    /**
     * Add a pattern to remove.
     *
     * @param string $pattern
     */
    public function addObscureKey(string $pattern): void
    {
        $this->obscureKeys[] = strtolower($pattern);
    }

    /**
     * Get the context defaults that will be added to every log entry.
     *
     * @return array
     */
    public function getContextOverrides(): array
    {
        return $this->staticContextDefaults;
    }

    /**
     * Set the context defaults that will be added to every log entry.
     *
     * @param array $staticContextDefaults
     */
    public function setContextOverrides(array $staticContextDefaults): void
    {
        $this->staticContextDefaults = $staticContextDefaults;
    }

    /**
     * Clean sensitive data out of the log context.
     *
     * @param array $context The context to clean.
     */
    public function obscureContext(array &$context): void
    {
        array_walk_recursive($context, function (&$value, $key) {
            foreach ($this->obscureKeys as $pattern) {
                if (fnmatch($pattern, strtolower($key))) {
                    $value = "***";
                }
            }
        });
    }
}
