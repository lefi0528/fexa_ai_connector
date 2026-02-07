<?php

namespace PhpMcp\Server\Tests\Fixtures\Schema;

use PhpMcp\Server\Attributes\Schema;
use PhpMcp\Server\Attributes\Schema\Format;
use PhpMcp\Server\Attributes\Schema\ArrayItems;
use PhpMcp\Server\Attributes\Schema\Property;
use PhpMcp\Server\Tests\Fixtures\Enums\BackedIntEnum;
use PhpMcp\Server\Tests\Fixtures\Enums\BackedStringEnum;
use PhpMcp\Server\Tests\Fixtures\Enums\UnitEnum;
use stdClass;

class SchemaGenerationTarget
{
    public function noParamsMethod(): void
    {
    }

    
    public function simpleRequiredTypes(string $pString, int $pInt, bool $pBool, float $pFloat, array $pArray, stdClass $pObject): void
    {
    }

    
    public function optionalTypesWithDefaults(
        string $pStringOpt = "hello",
        int $pIntOpt = 123,
        bool $pBoolOpt = true,
        ?float $pFloatOptNullable = 1.23,
        array $pArrayOpt = ['a', 'b'],
        ?stdClass $pObjectOptNullable = null
    ): void {
    }

    
    public function nullableTypes(?string $pNullableString, ?int $pUnionNullableInt, ?BackedStringEnum $pNullableEnum): void
    {
    }

    
    public function unionTypes(string|int $pStringOrInt, $pBoolOrFloatOrNull): void
    {
    } 

    
    public function arrayTypes(
        array $pStringArray,
        array $pIntArrayGeneric,
        array $pAssocArray,
        array $pEnumArray,
        array $pShapeArray,
        array $pArrayOfShapes
    ): void {
    }

    
    public function enumTypes(BackedStringEnum $pBackedStringEnum, BackedIntEnum $pBackedIntEnum, UnitEnum $pUnitEnum): void
    {
    }

    
    public function variadicParams(string ...$pVariadicStrings): void
    {
    }

    
    public function mixedType(mixed $pMixed): void
    {
    }

    
    public function withSchemaAttributes(
        #[Schema(format: Format::EMAIL)]
        string $email,
        #[Schema(minimum: 1, maximum: 100, multipleOf: 5)]
        int $quantity,
        #[Schema(minItems: 1, maxItems: 5, uniqueItems: true, items: new ArrayItems(minLength: 3))]
        array $tags,
        #[Schema(
            properties: [
                new Property(name: 'id', minimum: 1),
                new Property(name: 'username', pattern: '^[a-z0-9_]{3,16}$'),
            ],
            required: ['id', 'username'],
            additionalProperties: false
        )]
        array $userProfile
    ): void {
    }
}
