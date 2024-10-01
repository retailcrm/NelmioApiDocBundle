<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * App Test Kernel for functional tests.
 */
class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle($this),
            new \Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new \Nelmio\ApiDocBundle\Tests\Fixtures\NelmioApiDocTestBundle(),
        ];

        return $bundles;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/' . Kernel::VERSION . '/nelmio-api-doc/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/' . Kernel::VERSION . '/nelmio-api-doc/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/' . $this->environment . '.yml');

        // If symfony/framework-bundle > 3.0
        if (!class_exists('Symfony\Bundle\FrameworkBundle\Command\RouterApacheDumperCommand')) {
            $loader->load(__DIR__ . '/config/twig_assets.yml');
        }
    }

    public function serialize()
    {
        return serialize([$this->getEnvironment(), $this->isDebug()]);
    }

    public function unserialize($str): void
    {
        call_user_func_array([$this, '__construct'], unserialize($str));
    }
}
