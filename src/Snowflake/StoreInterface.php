<?php

namespace Ennexa\Snowflake;

interface StoreInterface
{
    public function next(int $instanceId): array;
}
