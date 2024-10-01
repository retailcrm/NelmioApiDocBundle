<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Formatter;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class SimpleFormatter extends AbstractFormatter
{
    public function formatOne(ApiDoc $annotation)
    {
        return $annotation->toArray();
    }

    public function format(array $collection)
    {
        $array = [];
        foreach ($collection as $coll) {
            $annotationArray = $coll['annotation']->toArray();
            unset($annotationArray['parsedResponseMap']);

            $array[$coll['resource']][] = $annotationArray;
        }

        return $array;
    }

    protected function renderOne(array $data): void
    {
    }

    protected function render(array $collection): void
    {
    }
}
