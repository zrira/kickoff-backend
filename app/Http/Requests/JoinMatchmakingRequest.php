<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinMatchmakingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
        ];
    }
}
