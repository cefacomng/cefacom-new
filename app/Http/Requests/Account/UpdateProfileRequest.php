<?php

namespace App\Http\Requests\Account;

use App\Support\NigerianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = is_string($this->phone) ? trim($this->phone) : null;

        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'business_name' => is_string($this->business_name) ? (trim($this->business_name) ?: null) : $this->business_name,
            'phone' => $phone === '' ? null : $phone,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->phone !== null && NigerianPhone::normalize($this->phone) === null) {
                $validator->errors()->add('phone', 'Enter a valid Nigerian mobile number, for example 0803 123 4567.');
            }
        }];
    }

    /** @return array{name: string, business_name: ?string, phone: ?string} */
    public function profileData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'business_name' => $this->input('business_name'),
            'phone' => NigerianPhone::normalize($this->phone),
        ];
    }
}
