<?php

declare(strict_types=1);

namespace Ennexa\Snowflake\Exception;

use Ennexa\Snowflake\ExceptionInterface;

class InvalidArgumentException
    extends \InvalidArgumentException
    implements ExceptionInterface {
}
