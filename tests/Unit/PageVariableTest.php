<?php

declare(strict_types=1);

use B7s\LaraInk\DTOs\PageVariable;

test('PageVariable can be created with basic types', function () {
    $variable = new PageVariable(
        name: 'testVar',
        value: 'test value',
        type: 'string',
        alpineVarName: 'var_testVar_abc123'
    );

    expect($variable->name)->toBe('testVar')
        ->and($variable->value)->toBe('test value')
        ->and($variable->type)->toBe('string')
        ->and($variable->alpineVarName)->toBe('var_testVar_abc123');
});

test('PageVariable can convert string to JSON', function () {
    $variable = new PageVariable(
        name: 'message',
        value: 'Hello World',
        type: 'string',
        alpineVarName: 'var_message_abc123'
    );

    expect($variable->toJson())->toBe('"Hello World"');
});

test('PageVariable can convert array to JSON', function () {
    $variable = new PageVariable(
        name: 'users',
        value: [
            ['name' => 'John Doe'],
            ['name' => 'Jane Smith'],
        ],
        type: 'array',
        alpineVarName: 'var_users_abc123'
    );

    $json = $variable->toJson();
    $decoded = json_decode($json, true);

    expect($decoded)->toBeArray()
        ->and($decoded)->toHaveCount(2)
        ->and($decoded[0]['name'])->toBe('John Doe')
        ->and($decoded[1]['name'])->toBe('Jane Smith');
});

test('PageVariable can convert int to JSON', function () {
    $variable = new PageVariable(
        name: 'count',
        value: 42,
        type: 'int',
        alpineVarName: 'var_count_abc123'
    );

    expect($variable->toJson())->toBe('42');
});

test('PageVariable can convert bool to JSON', function () {
    $variable = new PageVariable(
        name: 'isActive',
        value: true,
        type: 'bool',
        alpineVarName: 'var_isActive_abc123'
    );

    expect($variable->toJson())->toBe('true');
});

test('PageVariable can convert float to JSON', function () {
    $variable = new PageVariable(
        name: 'price',
        value: 19.99,
        type: 'float',
        alpineVarName: 'var_price_abc123'
    );

    expect($variable->toJson())->toBe('19.99');
});
