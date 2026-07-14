<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_column_id' => $this->board_column_id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
        ];
    }
}
