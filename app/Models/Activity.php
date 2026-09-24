<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'label', 'title', 'hint', 'answers', 'correct', 'icon',
        'format', 'format_type', 'instruction', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['answers' => 'array', 'correct' => 'integer', 'sort_order' => 'integer'];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}