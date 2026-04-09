<?php

use App\Models\Product;

test('producto tiene los campos fillable correctos', function () {
    $fillable = collect((new Product())->getFillable());

    expect($fillable)->toContain('name')
        ->toContain('price')
        ->toContain('stock')
        ->toContain('status')
        ->toContain('seller_profile_id')
        ->toContain('category_id');
});

test('producto castea price como decimal', function () {
    $casts = collect((new Product())->getCasts());

    expect($casts)->toHaveKey('price');
    expect($casts->get('price'))->toBe('decimal:2');
});

test('producto castea stock como integer', function () {
    $casts = collect((new Product())->getCasts());

    expect($casts)->toHaveKey('stock');
    expect($casts->get('stock'))->toBe('integer');
});

test('producto tiene relacion con sellerProfile', function () {
    $product = new Product();

    expect($product->sellerProfile())->toBeInstanceOf(
        \Illuminate\Database\Eloquent\Relations\BelongsTo::class
    );
});

test('producto tiene relacion con seller', function () {
    $product = new Product();

    expect(method_exists(Product::class, 'sellerProfile'))->toBeTrue();
});