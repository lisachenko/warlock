<?php
/**
 * Warlock AOP+DIC packet
 *
 * @copyright Lisachenko Alexander <lisachenko.it@gmail.com>
 */

namespace Warlock\Exception;

use Exception;
use RuntimeException;

/**
 * Runtime exception that points to the concrete source code by configuring file and line
 */
class SourceRuntimeException extends RuntimeException
{
    /**
     * {@inheritdoc}
     * @param mixed $reflector
     */
    public function __construct($message, $reflector, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        if (method_exists($reflector, 'getFileName')) {
            $this->file = $reflector->getFileName();
        }
        if (method_exists($reflector, 'getStartLine')) {
            $this->line = $reflector->getStartLine();
        }
    }
} 