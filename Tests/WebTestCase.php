<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests;

use PHPUnit\Util\ErrorHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class WebTestCase extends BaseWebTestCase
{
    public static $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (version_compare(Kernel::VERSION, '2.2.0', '<')) {
            $this->markTestSkipped('Does not work with Symfony2 2.1 due to a "host" parameter in the `routing.yml` file');
        }
    }

    public static function handleDeprecation($errorNumber, $message, $file, $line)
    {
        if ($errorNumber & E_USER_DEPRECATED) {
            return true;
        }

        return ErrorHandler::handleError($errorNumber, $message, $file, $line);
    }

    protected static function getKernelClass(): string
    {
        require_once __DIR__ . '/Fixtures/app/AppKernel.php';

        return 'Nelmio\ApiDocBundle\Tests\Functional\AppKernel';
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $class = self::getKernelClass();

        return new $class(
            'default',
            $options['debug'] ?? true
        );
    }
}
