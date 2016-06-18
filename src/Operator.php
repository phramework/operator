<?php
/**
 * Copyright 2015-2016 Xenofon Spafaridis
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Phramework\Operator;

/**
 * Operators
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 0.0.0
 */
class Operator
{
    const ISSET = 'isset';
    const NOT_ISSET = '!isset';
    const GREATER = '>';
    const GREATER_EQUAL = '>=';
    const LESS = '<';
    const LESS_EQUAL = '<=';
    const EQUAL = '==';
    const NOT_EQUAL = '!=';
    const ISNULL = 'ISNULL';
    const NOT_ISNULL = '!ISNULL';
    const EMPTY = 'empty';
    const NOT_EMPTY = '!empty';
    const LIKE = '~~';
    const NOT_LIKE = '!~~';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    /**
     * ∈, is an element of array *(URL encoded : `%E2%88%88"`)*
     */
    const IN_ARRAY = '∈';
    /**
     * ∉, is not an element of array *(URL encoded : `%E2%88%89`)*
     */
    const NOT_IN_ARRAY = '∉';

    /**
     * @var string[]
     */
    protected static $operators = [
        Operator::EMPTY,
        Operator::EQUAL,
        Operator::GREATER,
        Operator::GREATER_EQUAL,
        Operator::ISSET,
        Operator::LESS,
        Operator::LESS_EQUAL,
        Operator::NOT_EMPTY,
        Operator::NOT_EQUAL,
        Operator::NOT_ISSET,
        Operator::ISNULL,
        Operator::NOT_ISNULL,
        Operator::IN,
        Operator::NOT_IN,
        Operator::LIKE,
        Operator::NOT_LIKE,
        Operator::IN_ARRAY,
        Operator::NOT_IN_ARRAY
    ];

    /**
     * Define common operator aliases
     */
    const ALIASES = [
        'gt'  => Operator::GREATER,
        'gte' => Operator::GREATER_EQUAL,
        'lt'  => Operator::LESS,
        'lte' => Operator::LESS_EQUAL,
        '<>'  => Operator::NOT_EQUAL
    ];

    /**
     * @return string[]
     * @since 1.2.0
     */
    public static function getOperators()
    {
        return self::$operators;
    }


    /**
     * @param string $operator
     * @return string
     */
    public static function getCanonical(string $operator) : string
    {
        //Replace if operator is an alias
        if (array_key_exists($operator, Operator::ALIASES)) {
            return Operator::ALIASES[$operator];
        }

        return $operator;
    }

    /**
     * Check if a string is a valid operator
     * @param  string $operator
     * @param  string $attributeName Optional attribute's name, used for thrown exception
     * @throws \DomainException
     * @return string Returns the operator
     * @todo change attribute name to Source
     */
    public static function validate(
        string $operator,
        $attributeName = null
    ) : string {
        $operator = Operator::getCanonical($operator);

        if (!in_array($operator, self::$operators)) {
            throw new \DomainException(
                $attributeName !== null
                ?  sprintf(
                    'Invalid operator "%s" for attribute "%s"',
                    $operator,
                    $attributeName
                )
                : sprintf(
                    'Invalid operator "%s"',
                    $operator
                )
            );
        }

        return $operator;
    }

    /**
     * Operators which can compare if values are same or not
     */
    const CLASS_COMPARABLE = 1;

    /**
     * Operators witch can compare if an value is greater or less than another
     */
    const CLASS_ORDERABLE = 2;
    /**
     * Can search if a string exists in a text
     */
    const CLASS_LIKE = 4;
    /**
     * Can search if a values is in the allowed list
     */
    const CLASS_IN = 8;
    /**
     * Can search if an array of values contains a certain value
     */
    const CLASS_IN_ARRAY = 32;
    /**
     * Values that can be null
     */
    const CLASS_NULLABLE = 64;
    /**
     * Apply operators in JSON structured values
     */
    const CLASS_JSONOBJECT = 128;

    /**
     * Get operators
     * @param  int $classFlags
     * @return int Operator class
     * @throws \DomainException When invalid operator class flags are given
     */
    public static function getByClassFlags(int $classFlags)
    {
        $operators = [];

        if (($classFlags & Operator::CLASS_COMPARABLE) !== 0) {
            $operators = array_merge(
                $operators,
                Operator::getEqualityOperators()
            );
        }

        if (($classFlags & Operator::CLASS_ORDERABLE) !== 0) {
            $operators = array_merge(
                $operators,
                Operator::getOrderableOperators()
            );
        }

        if (($classFlags & Operator::CLASS_NULLABLE) !== 0) {
            $operators = array_merge(
                $operators,
                Operator::getNullableOperators()
            );
        }

        if (($classFlags & Operator::CLASS_LIKE) !== 0) {
            $operators = array_merge(
                $operators,
                Operator::getLikeOperators()
            );
        }

        if (($classFlags & Operator::CLASS_IN) !== 0) {
            $operators = array_merge(
                $operators,
                Operator::getInOperators()
            );
        }

        if (($classFlags & Operator::CLASS_IN_ARRAY) !== 0) {
            $operators = array_merge(
                $operators,
                Operator::getInArrayOperators()
            );
        }

        if (empty($operators) && ($classFlags & Operator::CLASS_JSONOBJECT) === 0) {
            throw new \DomainException('Invalid operator class flags');
        }

        return array_unique($operators);
    }

    /**
     * @param  string $operatorValueString
     * @return string[2] [operator, operand]
     * @example
     * ```php
     * list($operator, $operand) = Operator::parse('>=5');
     * ```
     * @todo use aliases
     */
    public static function parse($operatorValueString)
    {
        $operator = Operator::EQUAL;
        $value = $operatorValueString;

        $operators = implode(
            '|',
            array_merge(
                Operator::getOrderableOperators(),
                Operator::getLikeOperators(),
                Operator::getInArrayOperators()
            )
        );

        if (!!preg_match(
            '/^('
            . implode(
                '|',
                Operator::getInOperators()
            ) . ')[\ ]{0,1}(.+)$/',
            $operatorValueString,
            $matches
        )) {
            //handle IN operators

            //values MUST be a list
            $values = array_map(
                'trim',
                explode(',', $matches[2])
            );

            return [$matches[1], $values];
        } elseif (!!preg_match(
            '/^(' . $operators . ')[\ ]{0,1}(.+)$/',
            $operatorValueString,
            $matches
        )) {
            return [$matches[1], $matches[2]];
        } elseif (!!preg_match(
            //handle nullable operators

            '/^(' . implode('|', Operator::getNullableOperators()) . ')$/',
            $operatorValueString,
            $matches
        )) {
            return [$matches[1], null];
        }

        return [$operator, $value];
    }

    /**
     * @return string[]
     * @see Operator::CLASS_NULLABLE
     */
    public static function getNullableOperators()
    {
        return [
            Operator::ISNULL,
            Operator::NOT_ISNULL
        ];
    }

    /**
     * @return string[]
     */
    public static function getLikeOperators()
    {
        return [
            Operator::LIKE,
            Operator::NOT_LIKE
        ];
    }

    /**
     * @return string[]
     */
    public static function getEqualityOperators()
    {
        return [
            Operator::EQUAL,
            Operator::NOT_EQUAL
        ];
    }

    /**
     * @return string[]
     */
    public static function getInOperators()
    {
        return [
            Operator::IN,
            Operator::NOT_IN
        ];
    }

    /**
     * @return string[]
     */
    public static function getInArrayOperators()
    {
        return [
            Operator::IN_ARRAY,
            Operator::NOT_IN_ARRAY
        ];
    }

    /**
     * @return string[]
     */
    public static function getOrderableOperators()
    {
        return [
            Operator::EQUAL,
            Operator::NOT_EQUAL,
            Operator::GREATER_EQUAL,
            Operator::GREATER,
            Operator::LESS_EQUAL,
            Operator::LESS
        ];
    }
}
