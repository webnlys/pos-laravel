<?php

namespace App\Http\Requests\Admin;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $id = $unit instanceof Unit ? $unit->id : $unit;

        $rules = [
            'name' => ['required', 'string', 'max:50'],
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['name'][] = Rule::unique('units', 'name')->ignore($id);
        }

        return $rules;
    }
}
