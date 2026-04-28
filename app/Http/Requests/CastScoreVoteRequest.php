<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CastScoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vote' => ['required', 'string', 'in:approve,dispute'],
            'submitted_score_a' => ['nullable', 'required_if:vote,dispute', 'integer', 'min:0', 'max:999'],
            'submitted_score_b' => ['nullable', 'required_if:vote,dispute', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'submitted_score_a.required_if' => 'You must provide an alternative score when disputing.',
            'submitted_score_b.required_if' => 'You must provide an alternative score when disputing.',
        ];
    }
}
