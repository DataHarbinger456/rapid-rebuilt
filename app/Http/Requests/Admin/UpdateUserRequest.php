<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('targetUser')?->id ?? $this->route('user')?->id;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$userId}",
            'password' => ['sometimes', Password::defaults()],
            'role' => 'sometimes|in:user,inspector,staff,admin',
        ];
    }
}
