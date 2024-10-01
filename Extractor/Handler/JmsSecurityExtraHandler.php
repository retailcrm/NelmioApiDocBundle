<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Extractor\Handler;

use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Symfony\Component\Routing\Route;

class JmsSecurityExtraHandler implements HandlerInterface
{
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method): void
    {
        foreach ($annotations as $annot) {
            if ($annot instanceof PreAuthorize) {
                $annotation->setAuthentication(true);
            } elseif ($annot instanceof Secure) {
                $annotation->setAuthentication(true);
                $annotation->setAuthenticationRoles(is_array($annot->roles) ? $annot->roles : explode(',', $annot->roles));
            }
        }
    }
}
