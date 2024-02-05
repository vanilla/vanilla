<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Psr\Log\LoggerInterface;
use Vanilla\Logger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\DeveloperProfileModel;
use Vanilla\Utility\Spans\CacheReadSpan;
use Vanilla\Utility\Spans\CacheWriteSpan;
use Vanilla\Utility\Spans\DbReadSpan;
use Vanilla\Utility\Spans\DbWriteSpan;
use Vanilla\Utility\Spans\GenericSpan;
use Vanilla\Utility\Spans\RequestSpan;
use Vanilla\Utility\Spans\AbstractSpan;

/**
 * Contains some light-weight timers for profiling and logging application code.
 *
 * To record a timer one of the Timers::start() methods, then you've finished the action,
 * call $span->finish();
 *
 * It's highly recommended to put the finish call in a try {} finally {} block so that
 * it is gaurenteed to stop.
 */
final class Timers
{
    /**
     * @var array<string, int>
     */
    private array $warningLimitsMs = [];

    /** @var array<string, AbstractSpan> */
    private array $timeSpans = [];

    private ?float $offsetMs = null;
    private ?string $rootSpanUUID = null;
    private ?string $currentSpanUUID = null;

    private bool $isEnabled = true;

    /**
     * Set if timers are enabled.
     *
     * @param bool $isEnabled
     *
     * @return void
     */
    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * @return float|null
     */
    public function getOffsetMs(): ?float
    {
        return $this->offsetMs;
    }

    /**
     * @param float|null $offsetMs
     * @return void
     */
    public function setOffsetMs(?float $offsetMs): void
    {
        $this->offsetMs = $offsetMs;
    }

    /**
     * Get an instance of the Timers class.
     *
     * @return Timers
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public static function instance(): Timers
    {
        $timers = \Gdn::getContainer()->get(Timers::class);
        return $timers;
    }

    /**
     * The root span should only ever be created once after the application is bootstrapped.
     *
     * @return GenericSpan
     */
    public function startRootSpan(): GenericSpan
    {
        $span = $this->startGeneric("root");
        $this->setOffsetMs($span->getStartMs());
        $this->rootSpanUUID = $span->getUuid();

        // Since this the root, we need to apply the offset ourselves.
        $span->setOffsetMs($span->getStartMs());
        return $span;
    }

    /**
     * Track an http request.
     *
     * @param \Garden\Http\HttpRequest|\Garden\Web\RequestInterface $request
     *
     * @return RequestSpan
     */
    public function startRequest($request): RequestSpan
    {
        $span = new RequestSpan($request, $this->currentSpanUUID);
        $this->trackSpan($span);
        return $span;
    }

    /**
     * Track a cache read.
     *
     * @return CacheReadSpan
     */
    public function startCacheRead(): CacheReadSpan
    {
        $span = new CacheReadSpan($this->currentSpanUUID);
        $this->trackSpan($span);
        return $span;
    }

    /**
     * Track a cache write.
     *
     * @return CacheWriteSpan
     */
    public function startCacheWrite(): CacheWriteSpan
    {
        $span = new CacheWriteSpan($this->currentSpanUUID);
        $this->trackSpan($span);
        return $span;
    }

    /**
     * Start a generic timer.
     *
     * @param string $type The type of timer.
     * @param array $data Metadata about the timer.
     *
     * @return GenericSpan
     */
    public function startGeneric(string $type, array $data = []): GenericSpan
    {
        $span = new GenericSpan($type, $this->currentSpanUUID, $data);
        $this->trackSpan($span);
        return $span;
    }

    /**
     * @return DbReadSpan
     */
    public function startDbRead(): DbReadSpan
    {
        $span = new DbReadSpan($this->currentSpanUUID);
        $this->trackSpan($span);
        return $span;
    }

    /**
     * @return DbWriteSpan
     */
    public function startDbWrite(): DbWriteSpan
    {
        $span = new DbWriteSpan($this->currentSpanUUID);
        $this->trackSpan($span);
        return $span;
    }

    /**
     * Record a started timer.
     *
     * This method is also responsible for tracking the "nesting" of spans
     * and adjusting the offsets of a span.
     *
     * @param AbstractSpan $span
     *
     * @return void
     */
    private function trackSpan(AbstractSpan $span): void
    {
        if (!$this->isEnabled) {
            return;
        }
        if (count($this->timeSpans) > 5000) {
            ErrorLogger::warning("Too many timers have been recorded.", ["timers"]);
            return;
        }
        $this->currentSpanUUID = $span->getUuid();
        $span->setTimers($this);
        $this->timeSpans[$span->getUuid()] = $span;
        if ($offsetMs = $this->getOffsetMs()) {
            $span->setOffsetMs($offsetMs);
        }
    }

    /**
     * Called by a span when it complets.
     *
     * - Pop the span off the stack to handle nesting.
     * - Warn if the span took too long.
     *
     * @param AbstractSpan $span
     *
     * @return void
     *
     * @internal Don't call this yourself. Instead use TimerSpan::finish().
     */
    public function trackSpanFinished(AbstractSpan $span): void
    {
        if (!$this->isEnabled) {
            return;
        }
        // Reset the "current" span.
        $this->currentSpanUUID = $span->getParentUuid();

        $warningLimitMs = $this->warningLimitsMs[$span->getType()] ?? null;
        $elapsedMs = $span->getElapsedMs();
        if ($warningLimitMs !== null && $elapsedMs > $warningLimitMs) {
            $formattedDuration = self::formatDuration($elapsedMs);
            // Issue a warning that the timer took too long.
            ErrorLogger::warning(
                "Timer {$span->getType()} took {$formattedDuration}. This was longer than the allowed limit.",
                ["timerWarning", $span->getType()],
                [
                    "elapsedMs" => $elapsedMs,
                    "allowedMs" => $warningLimitMs,
                ] + $span->getData()
            );
        }
    }

    private ?bool $shouldRecordProfile = null;

    /**
     * Force a profile to record or not record.
     *
     * @param bool|null $shouldRecordProfile
     */
    public function setShouldRecordProfile(?bool $shouldRecordProfile): void
    {
        $this->shouldRecordProfile = $shouldRecordProfile;
    }

    /**
     * Determine if a profile should be recorded.
     */
    private function shouldRecordProfile(): bool
    {
        if (!$this->isEnabled) {
            return false;
        }
        $session = \Gdn::session();
        $config = \Gdn::config();

        if (!$config->get("trace.profiler", false)) {
            // Never record traces if the feature is disabled. Off by default.
            return false;
        }

        if ($this->shouldRecordProfile === true) {
            return true;
        } elseif ($this->shouldRecordProfile === false) {
            return false;
        }

        if ($session->getPermissions()->isSysAdmin()) {
            // Always record sysadmin traces
            return true;
        }

        if ($this->isSlowRequest()) {
            return true;
        }

        // Otherwise do a random sampling rate.
        $samplingRate = $config->get("trace.samplingRate", 1000);
        $isSampleHit = random_int(1, $samplingRate) === 1;
        return $isSampleHit;
    }

    /**
     * Determine if a request took too long and should be recorded.
     *
     * @return bool
     */
    private function isSlowRequest(): bool
    {
        $rootSpan = $this->getSpans()[$this->rootSpanUUID];
        if ($rootSpan === null) {
            return false;
        }

        // A 10 second request is slow.
        return $rootSpan->getElapsedMs() > 10 * 1000;
    }

    /**
     * Record if the current profile in the database if possible.
     *
     * @return void
     */
    public function recordProfile(): void
    {
        if (!$this->shouldRecordProfile()) {
            return;
        }

        $spans = $this->getSpans();
        foreach ($spans as $span) {
            $span->stopTimer();
        }
        $profile = [
            "source" => "backend",
            "rootSpanUuid" => $this->rootSpanUUID,
            "spans" => $this->getSpans(),
        ];
        $timers = $this->getAggregateTimers();
        $rootSpan = $this->getSpans()[$this->rootSpanUUID] ?? null;
        $elapsedMs = $rootSpan ? $rootSpan->getElapsedMs() : 0;

        $profileModel = \Gdn::getContainer()->get(DeveloperProfileModel::class);
        $request = \Gdn::request();

        $name = $request->getMethod() . " " . $request->getPath();
        $name = substr($name, 0, 255);

        try {
            $profileModel->insert([
                "profile" => $profile,
                "timers" => $timers,
                "name" => $name,
                "isTracked" => false,
                "requestElapsedMs" => $elapsedMs,
                "requestID" => $request->getMeta("requestID"),
                "requestMethod" => $request->getMethod(),
                "requestPath" => $request->getPath(),
                "requestQuery" => $request->getQuery(),
            ]);
        } catch (\Throwable $throwable) {
            ErrorLogger::warning("Failed to record developer profile.", ["developerProfile"], ["error" => $throwable]);
        }
    }

    /**
     * @return AbstractSpan[]
     */
    public function getSpans(): array
    {
        return $this->timeSpans;
    }

    /**
     * Format a time duration.
     *
     * @param float $milliseconds The duration in milliseconds and fractions of a second.
     * @return string Returns the duration formatted for humans.
     * @see microtime()
     */
    public static function formatDuration(float $milliseconds): string
    {
        if ($milliseconds === 0.0) {
            return "0";
        } elseif ($milliseconds < 1.0) {
            $n = number_format($milliseconds * 1.0e3, 0);
            $sx = "μs";
        } elseif ($milliseconds < 1000) {
            $n = number_format($milliseconds, 0);
            $sx = "ms";
        } elseif ($milliseconds < 60000) {
            $n = number_format($milliseconds / 1000, 1);
            $sx = "s";
        } elseif ($milliseconds < 3600000) {
            $n = number_format($milliseconds / 60000, 1);
            $sx = "m";
        } elseif ($milliseconds < 86400000) {
            $n = number_format($milliseconds / 3600000, 1);
            $sx = "h";
        } else {
            $n = number_format($milliseconds / 86400000, 1);
            $sx = "d";
        }
        if (str_ends_with($n, ".0")) {
            $n = substr($n, 0, -2);
        }

        $result = $n . $sx;
        return $result;
    }

    /**
     * Stop all of the currently running timers.
     */
    public function stopAll(): void
    {
        $this->currentSpanUUID = null;
        foreach ($this->getSpans() as $span) {
            $span->stopTimer();
        }
    }

    /**
     * Log all recorded timers with a given logger and event name.
     *
     *
     * @param LoggerInterface $logger
     * @param string $eventName
     */
    public function logAll(LoggerInterface $logger, string $eventName): void
    {
        $aggregates = $this->getAggregateTimers();
        $logger->info("Recording Timers: {$eventName}", [
            Logger::FIELD_TIMERS => $aggregates + [
                "request_elapsed_ms" => $_SERVER["REQUEST_TIME_FLOAT"]
                    ? (int) ((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000)
                    : null,
                "peak_memory" => memory_get_peak_usage(true),
            ],
            Logger::FIELD_EVENT => $eventName,
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
        ]);
    }

    /**
     * Calculate an aggregate of each type of timer and how long they took.
     *
     * @return array
     */
    public function getAggregateTimers(): array
    {
        $results = [];
        foreach ($this->getSpans() as $span) {
            $results[$span->getType() . "_elapsed_ms"] =
                ($results[$span->getType()] ?? 0) + $this->getSpanSelfMs($span);
        }

        return $results;
    }

    /**
     * Calculate how long a span spent in its own time, excluding child spans.
     *
     * @param AbstractSpan $span
     *
     * @return float
     */
    private function getSpanSelfMs(AbstractSpan $span): float
    {
        $spanMs = $span->getElapsedMs();
        $selfMs = $spanMs;
        foreach ($this->getSpans() as $otherSpan) {
            if ($otherSpan->getParentUuid() === $span->getUuid()) {
                $selfMs -= $otherSpan->getElapsedMs();
            }
        }
        return $selfMs;
    }

    /**
     * Set a number of ms at which a timer will generate a warning.
     *
     * @param string $timerName
     * @param int $warnAtMs
     */
    public function setWarningLimit(string $timerName, int $warnAtMs)
    {
        $this->warningLimitsMs[$timerName] = $warnAtMs;
    }

    /**
     * Reset timers.
     *
     * @return void
     */
    public function reset()
    {
        $this->timeSpans = [];
        $this->rootSpanUUID = null;
        $this->currentSpanUUID = null;
    }

    /**
     * Legacy stubs. Remove once vanillainfrastructure has been updated
     *
     * @deprecated
     */
    public function start(string $_)
    {
        // Do nothing
    }

    /**
     * Legacy stubs. Remove once vanillainfrastructure has been updated
     *
     * @deprecated
     */
    public function stop(string $_)
    {
    }

    /**
     * Legacy stubs. Remove once vanillainfrastructure has been updated
     *
     * @deprecated
     */
    public function get(string $_): array
    {
        return [
            "time" => 0,
        ];
    }
}
