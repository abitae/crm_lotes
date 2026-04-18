<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAsset extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'kind',
        'title',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
