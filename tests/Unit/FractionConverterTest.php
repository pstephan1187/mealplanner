<?php

use App\Support\FractionConverter;

it('converts simple fractions to decimals', function (string $input, float $expected) {
    expect(FractionConverter::toDecimal($input))->toBe($expected);
})->with([
    'half' => ['1/2', 0.5],
    'quarter' => ['1/4', 0.25],
    'third' => ['1/3', 1 / 3],
    'three quarters' => ['3/4', 0.75],
]);

it('converts mixed numbers to decimals', function (string $input, float $expected) {
    expect(FractionConverter::toDecimal($input))->toBe($expected);
})->with([
    'one and a half' => ['1 1/2', 1.5],
    'two and a quarter' => ['2 1/4', 2.25],
    'three and three quarters' => ['3 3/4', 3.75],
]);

it('converts plain numbers to decimals', function (string $input, float $expected) {
    expect(FractionConverter::toDecimal($input))->toBe($expected);
})->with([
    'integer' => ['3', 3.0],
    'decimal' => ['2.5', 2.5],
    'zero point five' => ['0.5', 0.5],
]);

it('converts decimals to fraction strings', function (float $input, string $expected) {
    expect(FractionConverter::toFraction($input))->toBe($expected);
})->with([
    'half' => [0.5, '1/2'],
    'quarter' => [0.25, '1/4'],
    'three quarters' => [0.75, '3/4'],
    'whole number' => [3.0, '3'],
    'one and a half' => [1.5, '1 1/2'],
    'two and a third' => [2 + 1 / 3, '2 1/3'],
    'non-standard decimal' => [1.37, '1.37'],
]);

it('validates fraction strings', function (string $input, bool $expected) {
    expect(FractionConverter::isValid($input))->toBe($expected);
})->with([
    'simple fraction' => ['1/2', true],
    'mixed number' => ['1 1/2', true],
    'integer' => ['3', true],
    'decimal' => ['2.5', true],
    'zero' => ['0', false],
    'negative' => ['-1', false],
    'text' => ['abc', false],
    'empty' => ['', false],
    'zero fraction' => ['0/4', false],
]);
