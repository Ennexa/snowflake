<?php

declare(strict_types=1);

namespace Ennexa\Snowflake\Exception;

use Ennexa\Snowflake\ExceptionInterface;

class InvalidSystemClockException
    extends \Exception
    implements ExceptionInterface {
}
