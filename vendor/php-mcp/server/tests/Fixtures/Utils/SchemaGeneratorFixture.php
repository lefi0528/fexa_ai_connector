<?php

namespace PhpMcp\Server\Tests\Fixtures\Utils;

use PhpMcp\Server\Attributes\Schema;
use PhpMcp\Server\Tests\Fixtures\Enums\BackedIntEnum;
use PhpMcp\Server\Tests\Fixtures\Enums\BackedStringEnum;
use PhpMcp\Server\Tests\Fixtures\Enums\UnitEnum;
use stdClass;


class SchemaGeneratorFixture
{
    

    public function noParams(): void
    {
    }

    
    public function typeHintsOnly(string $name, int $age, bool $active, array $tags, ?stdClass $config = null): void
    {
    }

    
    public function docBlockOnly($username, $count, $enabled, $data): void
    {
    }

    
    public function typeHintsWithDocBlock(string $email, int $score, bool $verified): void
    {
    }

    

    
    #[Schema(definition: [
        'type' => 'object',
        'description' => 'Creates a custom filter with complete definition',
        'properties' => [
            'field' => ['type' => 'string', 'enum' => ['name', 'date', 'status']],
            'operator' => ['type' => 'string', 'enum' => ['eq', 'gt', 'lt', 'contains']],
            'value' => ['description' => 'Value to filter by, type depends on field and operator']
        ],
        'required' => ['field', 'operator', 'value'],
        'if' => [
            'properties' => ['field' => ['const' => 'date']]
        ],
        'then' => [
            'properties' => ['value' => ['type' => 'string', 'format' => 'date']]
        ]
    ])]
    public function methodLevelCompleteDefinition(string $field, string $operator, mixed $value): array
    {
        return compact('field', 'operator', 'value');
    }

    
    #[Schema(
        description: "Creates a new user with detailed information.",
        properties: [
            'username' => ['type' => 'string', 'minLength' => 3, 'pattern' => '^[a-zA-Z0-9_]+$'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'age' => ['type' => 'integer', 'minimum' => 18, 'description' => 'Age in years.'],
            'isActive' => ['type' => 'boolean', 'default' => true]
        ],
        required: ['username', 'email']
    )]
    public function methodLevelWithProperties(string $username, string $email, int $age, bool $isActive = true): array
    {
        return compact('username', 'email', 'age', 'isActive');
    }

    
    #[Schema(
        properties: [
            'profiles' => [
                'type' => 'array',
                'description' => 'An array of user profiles to update.',
                'minItems' => 1,
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'data' => ['type' => 'object', 'additionalProperties' => true]
                    ],
                    'required' => ['id', 'data']
                ]
            ]
        ],
        required: ['profiles']
    )]
    public function methodLevelArrayArgument(array $profiles): array
    {
        return ['updated_count' => count($profiles)];
    }

    

    
    public function parameterLevelOnly(
        #[Schema(description: "Recipient ID", pattern: "^user_")]
        string $recipientId,
        #[Schema(maxLength: 1024)]
        string $messageBody,
        #[Schema(type: 'integer', enum: [1, 2, 5])]
        int $priority = 1,
        #[Schema(
            type: 'object',
            properties: [
                'type' => ['type' => 'string', 'enum' => ['sms', 'email', 'push']],
                'deviceToken' => ['type' => 'string', 'description' => 'Required if type is push']
            ],
            required: ['type']
        )]
        ?array $notificationConfig = null
    ): array {
        return compact('recipientId', 'messageBody', 'priority', 'notificationConfig');
    }

    
    public function parameterStringConstraints(
        #[Schema(format: 'email')]
        string $email,
        #[Schema(minLength: 8, pattern: '^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$')]
        string $password,
        string $regularString
    ): void {
    }

    
    public function parameterNumericConstraints(
        #[Schema(minimum: 18, maximum: 120)]
        int $age,
        #[Schema(minimum: 0, maximum: 5, exclusiveMaximum: true)]
        float $rating,
        #[Schema(multipleOf: 10)]
        int $count
    ): void {
    }

    
    public function parameterArrayConstraints(
        #[Schema(type: 'array', items: ['type' => 'string'], minItems: 1, uniqueItems: true)]
        array $tags,
        #[Schema(type: 'array', items: ['type' => 'integer', 'minimum' => 0, 'maximum' => 100], minItems: 1, maxItems: 5)]
        array $scores
    ): void {
    }

    

    
    #[Schema(
        properties: [
            'settingKey' => ['type' => 'string', 'description' => 'The key of the setting.'],
            'newValue' => ['description' => 'The new value for the setting (any type).']
        ],
        required: ['settingKey', 'newValue']
    )]
    public function methodAndParameterLevel(
        string $settingKey,
        #[Schema(description: "The specific new boolean value.", type: 'boolean')]
        mixed $newValue
    ): array {
        return compact('settingKey', 'newValue');
    }

    
    public function typeHintDocBlockAndParameterSchema(
        #[Schema(minLength: 3, pattern: '^[a-zA-Z0-9_]+$')]
        string $username,
        #[Schema(minimum: 1, maximum: 10)]
        int $priority
    ): void {
    }

    

    
    public function enumParameters(
        BackedStringEnum $stringEnum,
        BackedIntEnum $intEnum,
        UnitEnum $unitEnum,
        ?BackedStringEnum $nullableEnum = null,
        BackedIntEnum $enumWithDefault = BackedIntEnum::First
    ): void {
    }

    

    
    public function arrayTypeScenarios(
        array $genericArray,
        array $stringArray,
        array $intArray,
        array $mixedMap,
        array $objectLikeArray,
        array $nestedObjectArray
    ): void {
    }

    

    
    public function nullableAndOptional(
        ?string $nullableString,
        ?int $nullableInt = null,
        string $optionalString = 'default',
        bool $optionalBool = true,
        array $optionalArray = []
    ): void {
    }

    

    
    public function unionTypes(
        string|int $stringOrInt,
        bool|string|null $multiUnion
    ): void {
    }

    

    
    public function variadicStrings(string ...$items): void
    {
    }

    
    public function variadicWithConstraints(
        #[Schema(items: ['type' => 'integer', 'minimum' => 0])]
        int ...$numbers
    ): void {
    }

    

    
    public function mixedTypes(
        mixed $anyValue,
        mixed $optionalAny = 'default'
    ): void {
    }

    

    
    public function complexNestedSchema(
        #[Schema(
            type: 'object',
            properties: [
                'customer' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'pattern' => '^CUS-[0-9]{6}$'],
                        'name' => ['type' => 'string', 'minLength' => 2],
                        'email' => ['type' => 'string', 'format' => 'email']
                    ],
                    'required' => ['id', 'name']
                ],
                'items' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'string', 'pattern' => '^PRD-[0-9]{4}$'],
                            'quantity' => ['type' => 'integer', 'minimum' => 1],
                            'price' => ['type' => 'number', 'minimum' => 0]
                        ],
                        'required' => ['product_id', 'quantity', 'price']
                    ]
                ],
                'metadata' => [
                    'type' => 'object',
                    'additionalProperties' => true
                ]
            ],
            required: ['customer', 'items']
        )]
        array $order
    ): array {
        return ['order_id' => uniqid()];
    }

    

    
    public function typePrecedenceTest(
        string $numericString,
        #[Schema(format: 'email', minLength: 5)]
        string $stringWithConstraints,
        #[Schema(items: ['type' => 'integer', 'minimum' => 1, 'maximum' => 100])]
        array $arrayWithItems
    ): void {
    }

    

    
    #[Schema(description: "Gets server status. Takes no arguments.", properties: [])]
    public function noParamsWithSchema(): array
    {
        return ['status' => 'OK'];
    }

    
    public function parameterSchemaInferredType(
        #[Schema(description: "Some parameter", minLength: 3)]
        $inferredParam
    ): void {
    }
}
