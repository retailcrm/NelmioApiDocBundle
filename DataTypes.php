<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle;

/**
 * All the supported data-types which will be specified in the `actualType` properties in parameters.
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
class DataTypes
{
    public const INTEGER = 'integer';

    public const FLOAT = 'float';

    public const STRING = 'string';

    public const BOOLEAN = 'boolean';

    public const FILE = 'file';

    public const ENUM = 'choice';

    public const COLLECTION = 'collection';

    public const MODEL = 'model';

    public const DATE = 'date';

    public const DATETIME = 'datetime';

    public const TIME = 'time';

    /**
     * Returns true if the supplied `actualType` value is considered a primitive type. Returns false, otherwise.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isPrimitive($type)
    {
        return in_array(strtolower($type), [
            static::INTEGER,
            static::FLOAT,
            static::STRING,
            static::BOOLEAN,
            static::FILE,
            static::DATE,
            static::DATETIME,
            static::TIME,
            static::ENUM,
        ]);
    }
}
