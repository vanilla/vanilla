<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

use Ramsey\Uuid\Uuid;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\Timers;

/**
 * Abstract class used to represent a period of time of some operation.
 */
abstract class AbstractSpan implements \JsonSerializable
{
    protected ?Timers $timers = null;
    protected ?string $parentUuid;

    protected string $uuid;
    /** @var string[] */
    protected array $tags;
    protected string $type;
    protected array $data;
    protected float $startMs;
    protected ?float $endMs = null;

    private float $offsetMs = 0;

    /**
     * Constructor.
     *
     * @param string $type The type of span.
     * @param string|null $parentUuid The parent span uuid.
     * @param array $data Data to assosciate with the span.
     */
    public function __construct(string $type, ?string $parentUuid, array $data = [])
    {
        $this->parentUuid = $parentUuid;
        $this->uuid = Uuid::uuid4()->toString();
        $this->type = $type;
        $this->data = $data;
        $this->startMs = $this->currentMs();
    }

    /**
     * @param float $offsetMs
     */
    public function setOffsetMs(float $offsetMs): void
    {
        $this->offsetMs = $offsetMs;
    }

    /**
     * @return float
     */
    public function getStartMs(): float
    {
        return round($this->startMs - $this->offsetMs, 3);
    }

    /**
     * @return float|null
     */
    public function getEndMs(): ?float
    {
        if ($this->endMs === null) {
            return null;
        }
        return round($this->endMs - $this->offsetMs, 3);
    }

    /**
     * @return float
     */
    public function getElapsedMs(): float
    {
        $elapsedMs = ($this->endMs ?? $this->currentMs()) - $this->startMs;
        return round($elapsedMs, 3);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param Timers|null $timers
     *
     * @return void
     */
    public function setTimers(?Timers $timers): void
    {
        $this->timers = $timers;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string|null
     */
    public function getParentUuid(): ?string
    {
        return $this->parentUuid;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            "type" => $this->getType(),
            "elapsedMs" => $this->getElapsedMs(),
            "data" => new \ArrayObject($this->getData()),
            "startMs" => $this->getStartMs(),
            "endMs" => $this->getEndMs(),
            "uuid" => $this->getUuid(),
            "parentUuid" => $this->getParentUuid(),
        ];
    }

    /**
     * Call this to finalize the timing of the span.
     */
    public function stopTimer()
    {
        if ($this->endMs === null) {
            $this->endMs = $this->currentMs();
        }
    }

    /**
     * Stop the timer, and track with the Timers instance that we have finished.
     *
     * @param array $data
     *
     * @return $this
     */
    protected function finishInternal(array $data = []): AbstractSpan
    {
        if ($this->endMs !== null) {
            ErrorLogger::warning("Timer {$this->getType()} was finished more than once.", ["timers"]);
            return $this;
        }

        $this->data = array_replace_recursive($this->data, $data);
        $this->stopTimer();
        if ($this->timers) {
            $this->timers->trackSpanFinished($this);
        }
        return $this;
    }

    /**
     * Get the current time in milliseconds.
     *
     * @return float
     */
    protected function currentMs(): float
    {
        return microtime(true) * 1000;
    }
}
