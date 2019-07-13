<?php

namespace Ennexa\Snowflake;

use Exception\InvalidArgumentException;
use Exception\InvalidSystemClockException;

class Generator {
    const NODE_LEN = 8;
    const WORKER_LEN = 8;
    const SEQUENCE_LEN = 8;

    private $instanceId = 0;
    private $startEpoch = 1546300800000;
    private $sequenceMax;
    private $store;

    private static function getMaxValue(int $len)
    {
        return -1 ^ (-1 << $len);
    }

    public static function generateInstanceId($nodeId = 0, $workerId = 0)
    {
        $nodeIdMax = $this->getMaxValue(self::NODE_LEN);
        if ($nodeId < 0 || $nodeId > $nodeIdMax) {
            throw InvalidArgumentException("Node ID should be between 0 and $nodeIdMax");
        }

        $workerIdMax = $this->getMaxValue(self::WORKER_LEN);
        if ($workerId < 0 || $workerId > $workerIdMax) {
            throw InvalidArgumentException("Worker ID should be between 0 and $workerIdMax");
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
     *
     * @param int Instance Id
     * @return void
     */
    public function setStore(StoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * Get the current generator instance id
     *
     * @param int Instance Id
     * @return void
     */
    public function getInstanceId()
    {
        return $this->instanceId >> self::SEQUENCE_LEN;
    }

    /**
     * Set the instance id for the generator instance
     *
     * @param int Instance Id
     * @return void
     */
    public function setInstanceId(int $instanceId)
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
     * @return int unique 64-bit id
     * @throws InvalidSystemClockException
     */
    public function nextId()
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
