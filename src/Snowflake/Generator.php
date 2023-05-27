<?php

declare(strict_types=1);

namespace Ennexa\Snowflake;

use Ennexa\Snowflake\Exception\InvalidArgumentException;
use Ennexa\Snowflake\Exception\InvalidSystemClockException;

class Generator
{
    const NODE_LEN = 8;
    const WORKER_LEN = 8;
    const SEQUENCE_LEN = 8;

    private int $instanceId = 0;
    private int $startEpoch = 1546300800000;
    private int $sequenceMask;
    private int $sequenceMax;
    private StoreInterface $store;
    private int $tickShift;

    private static function getMaxValue(int $len): int
    {
        return -1 ^ (-1 << $len);
    }

    public static function generateInstanceId($nodeId = 0, $workerId = 0)
    {
        $nodeIdMax = self::getMaxValue(self::NODE_LEN);
        if ($nodeId < 0 || $nodeId > $nodeIdMax) {
            throw new InvalidArgumentException("Node ID should be between 0 and $nodeIdMax");
        }

        $workerIdMax = self::getMaxValue(self::WORKER_LEN);
        if ($workerId < 0 || $workerId > $workerIdMax) {
            throw new InvalidArgumentException("Worker ID should be between 0 and $workerIdMax");
        }

        return $nodeId << self::WORKER_LEN | $workerId;
    }

    public function __construct(StoreInterface $store, int $instanceId = 0, ?int $startEpoch = null)
    {
        $this->setInstanceId($instanceId);
        $this->setStore($store);

        if (!is_null($startEpoch)) {
            $this->startEpoch = $startEpoch;
        }

        $this->sequenceMask = -1 ^ (-1 << self::SEQUENCE_LEN);
        $this->sequenceMax = -1 & $this->sequenceMask;
        $this->tickShift = self::NODE_LEN + self::WORKER_LEN + self::SEQUENCE_LEN;
    }

    /**
     * Set the sequence store
     */
    public function setStore(StoreInterface $store): void
    {
        $this->store = $store;
    }

    /**
     * Get the current generator instance id
     */
    public function getInstanceId(): int
    {
        return $this->instanceId >> self::SEQUENCE_LEN;
    }

    /**
     * Set the instance id for the generator instance
     */
    public function setInstanceId(int $instanceId): void
    {
        $this->instanceId = $instanceId << self::SEQUENCE_LEN;
    }

    /**
     * Get the next sequence
     *
     * @return array timestamp and sequence number
     */
    public function nextSequence()
    {
        return $this->store->next($this->instanceId);
    }

    /**
     * Generate a unique id based on the epoch and instance id
     *
     * @return string unique 64-bit id
     * @throws InvalidSystemClockException
     */
    public function nextId(): string
    {
        list($timestamp, $sequence) = $this->nextSequence();

        if ($sequence < 0) {
            $ticks = $timestamp - $this->startEpoch;
            throw new InvalidSystemClockException("Clock moved backwards or wrapped around. Refusing to generate id for $ticks ticks");
        }

        $ticks = ($timestamp - $this->startEpoch) << $this->tickShift;

        return (string)($ticks | $this->instanceId | $sequence);
    }
}
