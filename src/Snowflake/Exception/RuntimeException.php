<?php

declare(strict_types=1);

namespace Ennexa\Snowflake\Exception;

use Ennexa\Snowflake\ExceptionInterface;

class RuntimeException
    extends \RuntimeException
    implements ExceptionInterface {

}
