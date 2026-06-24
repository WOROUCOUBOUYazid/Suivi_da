<?php

namespace App\Http\Requests\Commentaire;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'max:5000'],
        ];
    }
}
