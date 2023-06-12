<?php

namespace Ennexa\Snowflake\Store;

use Ennexa\Snowflake\StoreInterface;
use Ennexa\Snowflake\Exception\RuntimeException;

class RedisStore implements StoreInterface
{
    private \Redis $backend;
    public function __construct(\Redis $backend)
    {
        $this->backend = $backend;
    }

    private function getLuaScript()
    {
        return <<<LUA
redis.replicate_commands()
local ts = redis.call('time')
local old_ts = tonumber(redis.call('get',KEYS[1] .. '.ts')) or 0
local new_ts = ts[1] * 1000 + (ts[2] - ts[2] % 1000) / 1000

redis.call('set', KEYS[1] .. '.ts', new_ts)
redis.log(3, old_ts .. '|' .. new_ts)
if (old_ts < new_ts) then
  redis.call('set', KEYS[1] .. '.seq', 0)
  return {new_ts, 0}
else
  return {new_ts, redis.call('incr', KEYS[1] .. '.seq')}
end
LUA;
    }

    public function next(int $instanceId):array
    {
        try {
            return $this->backend->eval($this->getLuaScript(), [
                "snowflake_{$instanceId}"
            ], 1);
        } catch (\RedisException $e) {
            throw new RuntimeException('[Snowflake] Failed to generate sequence', null, $e);
        }
    }
}
