<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory;

    protected $fillable = ['board_column_id', 'title', 'description', 'position'];

    public function boardColumn(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class);
    }
}
