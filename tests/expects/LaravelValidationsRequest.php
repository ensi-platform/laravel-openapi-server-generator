<?php

namespace App\Http\Requests;

use App\Http\ApiV1\Support\Requests\BaseFormRequest;
use App\Http\ApiV1\OpenApiGenerated\Enums\TestIntegerEnum;
use Illuminate\Validation\Rules\Enum;

class LaravelValidationsRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'field_object_nullable' => ['nullable'],
            'field_object_nullable.field' => ['integer'],
            'field_array_nullable' => ['nullable', 'array'],
            'field_array_nullable.*.field' => ['integer'],
            'field_enum_nullable' => ['nullable', new Enum(TestIntegerEnum::class)],
            'field_number_nullable' => ['nullable', 'numeric'],
            'field_boolean_nullable' => ['nullable', 'boolean'],
            'field_string_nullable' => ['nullable', 'string'],
            'field_integer_nullable' => ['nullable', 'integer'],
            'field_object_required' => ['required'],
            'field_object_required.field' => ['integer'],
            'field_array_required' => ['required', 'array'],
            'field_array_required.*.field' => ['integer'],
            'field_enum_required' => [new Enum(TestIntegerEnum::class)],
            'field_number_required' => ['required', 'numeric'],
            'field_boolean_required' => ['required', 'boolean'],
            'field_string_required' => ['required', 'string'],
            'field_integer_required' => ['required', 'integer'],
            'field_object' => ['required'],
            'field_object.field' => ['integer'],
            'field_array' => ['required', 'array'],
            'field_array.*.field' => ['integer'],
            'field_enum' => ['required', new Enum(TestIntegerEnum::class)],
            'field_number' => ['required', 'numeric'],
            'field_boolean' => ['required', 'boolean'],
            'field_string' => ['required', 'string'],
            'field_integer' => ['required', 'integer'],
        ];
    }
}