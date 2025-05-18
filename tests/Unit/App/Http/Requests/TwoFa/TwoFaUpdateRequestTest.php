<?php

declare(strict_types=1);

use App\Http\Requests\TwoFa\TwoFaUpdateRequest;

beforeEach(function () {
    $this->requestData = new TwoFaUpdateRequest;
});

test('rules', function () {
    $rules = [
        'code' => [
            'required',
            'string',
            'min:6',
        ],
    ];

    expect(test()->requestData->rules())->toEqual($rules);
});

test('authenticate', function () {
    expect(test()->requestData->authorize())->toBeTrue();
});
