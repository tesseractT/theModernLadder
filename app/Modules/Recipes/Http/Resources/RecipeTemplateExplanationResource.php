<?php

namespace App\Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeTemplateExplanationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'template_id' => $this->templateId,
            'source' => $this->source,
            'meta' => $this->meta,
            'explanation' => $this->explanation,
        ];
    }
}
