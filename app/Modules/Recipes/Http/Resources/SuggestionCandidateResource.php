<?php

namespace App\Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionCandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'source' => $this['source'],
            'recipe_template_id' => $this['recipe_template_id'],
            'suggestion_type' => $this['suggestion_type'],
            'title' => $this['title'],
            'summary' => $this['summary'],
            'score' => $this['score'],
            'score_breakdown' => $this['score_breakdown'],
            'reason_codes' => $this['reason_codes'],
            'matched_ingredients' => $this['matched_ingredients'],
            'missing_ingredients' => $this['missing_ingredients'],
            'substitutions' => $this['substitutions'],
            'pairing_signals' => $this['pairing_signals'],
            'preference_compatibility' => $this['preference_compatibility'],
            'match_summary' => $this['match_summary'],
        ];
    }
}
