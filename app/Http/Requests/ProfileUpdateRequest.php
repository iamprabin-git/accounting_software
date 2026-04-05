<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\EmailAddress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => EmailAddress::normalize($this->string('email')->toString()) ?? '',
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'max:255',
                EmailAddress::laravelRule(),
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
