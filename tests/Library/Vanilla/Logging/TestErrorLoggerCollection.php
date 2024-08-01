<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Logging;

use Monolog\Test\TestCase;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\TestLogger;

/**
 * Error log collection for tests.
 */
final class TestErrorLoggerCollection
{
    /** @var TestLogger */
    private $testLogger;

    /**
     * Create a temporary log file path.
     *
     * @return string
     */
    public static function createLogFilePath(): string
    {
        $logDir = sys_get_temp_dir() . "/errorcollection-" . random_int(0, 100000);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        return $logDir . "/" . random_int(0, 100000);
    }

    /**
     * Constructor.
     *
     * @param string $filepath The log file path.
     */
    public function __construct(string $filepath)
    {
        $this->testLogger = new TestLogger();
        if (file_exists($filepath)) {
            $data = file_get_contents($filepath);
        } else {
            $data = "";
        }
        $pieces = explode("\n", $data);
        foreach ($pieces as $piece) {
            if (empty($piece)) {
                continue;
            }
            preg_match('/\[.*\] (\$json:)?(.*)/', $piece, $m);
            $isJson = !empty($m[1]);
            $rawMessage = $m[2];
            $level = "rawerror";
            $message = $rawMessage;
            $context = [];
            if ($isJson) {
                $json = json_decode($rawMessage, true);
                ArrayUtils::assertArray($json, "Expected log json to be valid json: " . $rawMessage);
                $level = $json["level"];
                $message = $json["message"];
                $context = $json;
            }

            $this->testLogger->log($level, $message, $context);
        }
    }

    /**
     * @return TestLogger
     */
    public function getTestLogger(): TestLogger
    {
        return $this->testLogger;
    }
}
