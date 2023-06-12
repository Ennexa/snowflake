<?php

namespace Ennexa\Snowflake\Store;

use Ennexa\Snowflake\StoreInterface;
use Ennexa\Snowflake\Exception\RuntimeException;

class FileStore implements StoreInterface
{
    private string $cacheDir;
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;

        if (!file_exists($cacheDir)) {
            $status = mkdir($cacheDir, 0700, true);
            if (!$status) {
                throw new RuntimeException("[Snowflake] Failed to created directory - {$cacheDir}");
            }
        }
    }

    public function next(int $instanceId):array
    {
        $timestamp = (int)floor(microtime(true) * 1000);
        $file = "last_ts_{$instanceId}.fc";

        $fp = fopen("{$this->cacheDir}/{$file}", "c+");
        if (!$fp) {
            throw new RuntimeException('[Snowflake] Failed to open file');
        }

        $counter = 100;
        do {
            // Try to acquire exclusive lock. Fails after 5ms
            $locked = flock($fp, LOCK_EX | LOCK_NB);
            usleep(50);
        } while($counter--);

        if (!$locked) {
            throw new RuntimeException('[Snowflake] Failed to acquire lock');
        }

        try {
            fseek($fp, 0);
            $content = fread($fp, 50);

            $lastTimestamp = $sequence = 0;
            if ($content) {
                list($lastTimestamp, $sequence) = unserialize($content);
            }
            if ($lastTimestamp > $timestamp) {
                $sequence = -1;
            } else if ($lastTimestamp < $timestamp) {
                $sequence = 0;
            } else {
                $sequence++;
            }
            if ($sequence >= 0) {
                fseek($fp, 0);
                ftruncate($fp, 0);
                fwrite($fp, serialize([$timestamp, $sequence]));
                fflush($fp);
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return [$timestamp, $sequence];
    }
}
