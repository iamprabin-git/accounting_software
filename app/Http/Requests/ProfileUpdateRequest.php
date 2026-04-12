<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\EmailAddress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => EmailAddress::normalize($this->string('email')->toString()) ?? '',
            ]);
        }

        // Multipart forms may send remove_profile_photo as ""; boolean rule rejects that.
        $flag = $this->input('remove_profile_photo');
        if ($flag === '' || $flag === null) {
            $this->merge(['remove_profile_photo' => false]);
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
            'phone' => ['nullable', 'string', 'max:40'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
        ];
    }
}
