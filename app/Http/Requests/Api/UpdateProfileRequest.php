<?php

namespace App\Http\Requests\Api;

use App\Commands\UpdateProfileCommand;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstName' => 'sometimes|string|max:255',
            'lastName' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'custom_fields' => 'sometimes|array',
        ];
    }

    public function toCommand(): UpdateProfileCommand
    {
        return new UpdateProfileCommand(
            $this->user()->id,
            $this->validated()
        );
    }
}
