<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Fixtures\Controller;

use Nelmio\ApiDocBundle\Attribute\ApiDoc;

class ResourceController
{
    #[ApiDoc(
        resource: true,
        views: ['test', 'premium', 'default'],
        resourceDescription: 'Operations on resource.',
        description: 'List resources.',
        output: "array<Nelmio\ApiDocBundle\Tests\Fixtures\Model\Test> as tests",
        statusCodes: [200 => 'Returned on success.', 404 => 'Returned if resource cannot be found.']
    )]
    public function listResourcesAction(): void
    {
    }

    #[ApiDoc(description: 'Retrieve a resource by ID.')]
    public function getResourceAction(): void
    {
    }

    #[ApiDoc(description: 'Delete a resource by ID.')]
    public function deleteResourceAction(): void
    {
    }

    #[ApiDoc(
        description: 'Create a new resource.',
        views: ['default', 'premium'],
        input: ['class' => "Nelmio\ApiDocBundle\Tests\Fixtures\Form\SimpleType", 'name' => ''],
        output: "Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested",
        responseMap: [
            400 => ['class' => "Nelmio\ApiDocBundle\Tests\Fixtures\Form\SimpleType", 'form_errors' => true],
        ]
    )]
    public function createResourceAction(): void
    {
    }

    #[ApiDoc(
        resource: true,
        views: ['default', 'premium'],
        description: 'List another resource.',
        resourceDescription: 'Operations on another resource.',
        output: "array<Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest>"
    )]
    public function listAnotherResourcesAction(): void
    {
    }

    #[ApiDoc(description: 'Retrieve another resource by ID.')]
    public function getAnotherResourceAction(): void
    {
    }

    #[ApiDoc(description: 'Update a resource bu ID.')]
    public function updateAnotherResourceAction(): void
    {
    }
}
