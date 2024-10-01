<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Fixtures\Model;

class JsonSerializableTest implements \JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return [
            'id' => 123,
            'name' => 'My name',
            'child' => [
                'value' => [1, 2, 3],
            ],
            'another' => new JsonSerializableOptionalConstructorTest(),
        ];
    }
}
