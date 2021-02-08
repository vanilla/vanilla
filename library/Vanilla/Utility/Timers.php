<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Contains some light weight timers for profiling and logging application code.
 *
 * You can use this class by sharing it application wide and then putting calls to `Timers::start()` and `Timers::stop()`
 * throughout hot spots in the code, such as I/O.
 *
 * Each timer must be given a name such as "db" or "cache". You can call `start()` and `stop()` many times wih the same
 * name and the total time will be tracked alongside the min, max, and count of timers.
 */
final class Timers implements \JsonSerializable {
    const TIMERS = ['cacheRead', 'cacheWrite', 'dbRead', 'dbWrite'];

    private const DEFAULT_TIMER = [
        'time' => 0.0,
        'human' => '0',
        'count' => 0,
        'min' => null,
        'max' => 0.0,
    ];

    private $timers = [];

    private $customTimers = [];

    /**
     * Start a timer.
     *
     * @param string|string[] $name The name of the timer to start or an array of names to start..
     * @return array Returns the timer row or an array of timer rows.
     */
    public function start($name): array {
        if (!is_string($name) && !is_array($name)) {
            throw new \InvalidArgumentException("Timers::start() expects a string or array.", 400);
        }
        $now = microtime(true);

        $names = (array)$name;
        $result = [];
        foreach ($names as $n) {
            $timer = ($this->timers[$n] ?? []) + self::DEFAULT_TIMER;

            if (isset($timer['start']) && !isset($timer['stop'])) {
                trigger_error("Timer was started while another one was running: $n", E_USER_NOTICE);
                // This timer was started, but not stopped, stop it now.
                $timer = $this->stopInternal($timer, $now);
            }
            $this->timers[$n] = $result[$n] = $this->startInternal($timer, $now);
        }
        return is_string($name) ? $result[$name] : $result;
    }

    /**
     * Set a timer's stop fields and return it.
     *
     * @param array $timer The name of the timer to stop.
     * @param float $now The current time.
     * @return array Returns the time array properly stopped.
     */
    private function stopInternal(array $timer, float $now): array {
        $timer['stop'] = $now;
        $elapsed = ($timer['stop'] - $timer['start']) * 1000.0;
        $timer['time'] += $elapsed;
        $timer['human'] = self::formatDuration($timer['time']);
        if ($timer['min'] === null || $timer['min'] > $elapsed) {
            $timer['min'] = $elapsed;
        }
        $timer['max'] = max($timer['max'], $elapsed);

        return $timer;
    }

    /**
     * Format a time duration.
     *
     * @param float $milliseconds The duration in milliseconds and fractions of a second.
     * @return string Returns the duration formatted for humans.
     * @see microtime()
     */
    public static function formatDuration(float $milliseconds): string {
        if ($milliseconds === 0.0) {
            return '0';
        } elseif ($milliseconds < 1.0) {
            $n = number_format($milliseconds * 1.0e3, 0);
            $sx = 'μs';
        } elseif ($milliseconds < 1000) {
            $n = number_format($milliseconds, 0);
            $sx = 'ms';
        } elseif ($milliseconds < 60000) {
            $n = number_format($milliseconds / 1000, 1);
            $sx = 's';
        } elseif ($milliseconds < 3600000) {
            $n = number_format($milliseconds / 60000, 1);
            $sx = 'm';
        } elseif ($milliseconds < 86400000) {
            $n = number_format($milliseconds / 3600000, 1);
            $sx = 'h';
        } else {
            $n = number_format($milliseconds / 86400000, 1);
            $sx = 'd';
        }
        if (str_ends_with($n, '.0')) {
            $n = substr($n, 0, -2);
        }

        $result = $n.$sx;
        return $result;
    }

    /**
     * Set a timer's start fields and return it.
     *
     * @param array $timer
     * @param float $now
     * @return array
     */
    private function startInternal(array $timer, float $now): array {
        $timer['count']++;
        $timer['start'] = $now;
        unset($timer['stop']);

        return $timer;
    }

    /**
     * Stop a timer.
     *
     * @param string|string[] $name The name of the timer to stop or an array of names.
     * @return array Returns the timer row or an array of timer rows..
     */
    public function stop($name): array {
        if (!is_string($name) && !is_array($name)) {
            throw new \InvalidArgumentException("Timers::stop() expects a string or array.", 400);
        }

        $now = microtime(true);
        $names = (array)$name;

        $result = [];
        foreach ($names as $n) {
            $timer = ($this->timers[$n] ?? []) + self::DEFAULT_TIMER;

            if (!isset($timer['start'])) {
                trigger_error("Timer was stopped before calling start: $n", E_USER_NOTICE);
                $timer = $this->startInternal($timer, $now);
            }

            $this->timers[$n] = $result[$n] = $this->stopInternal($timer, $now);
        }
        return is_string($name) ? $result[$name] : $result;
    }

    /**
     * Call a function and time it.
     *
     * This method calls the passed function and returns its result, incrementing timing information for it.
     *
     * @param string|string[] $name A timer name or array of timer names.
     * @param callable $callable The function to call.
     * @return mixed Returns the result of the function call.
     */
    public function time($name, callable $callable) {
        if (!is_string($name) && !is_array($name)) {
            throw new \InvalidArgumentException("Timers::time() expects arument 1 to be a string or array.", 400);
        }
        try {
            $this->start($name);
            return $callable();
        } finally {
            $this->stop($name);
        }
    }

    /**
     * Stop all of the currently running timers.
     */
    public function stopAll(): void {
        $now = microtime(true);
        foreach ($this->timers as &$timer) {
            if (!isset($timer['stop'])) {
                $timer = $this->stopInternal($timer, $now);
            }
        }
    }

    /**
     * Get a timer's info array.
     *
     * @param string $name The name of the timer.
     * @return array|null Returns the timer's info or **null** if one doesn't exist.
     */
    public function get($name): ?array {
        return $this->timers[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize() {
        return $this->timers;
    }

    /**
     * Get a string appropriate to pass as log format to a PSR loggger.
     *
     * @return string Returns a format string.
     */
    public function getLogFormatString(): string {
        $keys = array_keys($this->timers);
        natcasesort($keys);

        $args = array_map(function ($key) {
            return $key . ': {' . $key . '.human}';
        }, $keys);

        return implode(', ', $args);
    }

    /**
     * @return array
     */
    public function getTimers(): array {
        return array_merge(self::TIMERS, $this->customTimers);
    }

    /**
     * @param string $timer
     */
    public function addCustomTimer(string $timer): void {
        $this->customTimers[] = $timer;
    }
}
