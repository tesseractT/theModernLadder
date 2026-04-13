<?php

namespace App\Modules\Recipes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteRecipeTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
