<?php

namespace App\Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSuspiciousActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'as_of' => $this['as_of'] ?? null,
            'lookback_days' => $this['lookback_days'] ?? null,
            'signals' => $this['signals'] ?? [],
        ];
    }
}
