<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that may be mass assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'owner',
        'owner_id',
        'due_date',
        'priority',
        'status',
        'progress_note',
        'progress_updated_at',
    ];

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== TaskStatus::Done && $this->due_date->lt(today());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', TaskStatus::Done->value);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()->whereDate('due_date', '<', today());
    }

    public function scopeDueWithinDays(Builder $query, int $days): Builder
    {
        return $query->open()
            ->whereDate('due_date', '>=', today())
            ->whereDate('due_date', '<=', today()->addDays($days));
    }

    public function scopeStale(Builder $query): Builder
    {
        return $query->open()
            ->where(function (Builder $query): void {
                $query->whereNull('progress_updated_at')
                    ->orWhere('progress_updated_at', '<', now()->startOfWeek());
            });
    }

    public function scopeVisibleTo(Builder $query, User $user, bool $assignedOnly = false): Builder
    {
        return $query->when(
            $assignedOnly || ! $user->can('viewAll', self::class),
            fn (Builder $query) => $query->where('owner_id', $user->id)
        );
    }

    /**
     * @return array<string, int>
     */
    public static function getDashboardStatistics(): array
    {
        return [
            'total' => static::query()->count(),
            'active' => static::query()->open()->count(),
            'open' => static::query()->open()->count(),
            'overdue' => static::query()->overdue()->count(),
            'due_next_7_days' => static::query()->dueWithinDays(7)->count(),
            'completed' => static::query()->where('status', TaskStatus::Done->value)->count(),
            'done' => static::query()->where('status', TaskStatus::Done->value)->count(),
            'stale' => static::query()->stale()->count(),
        ];
    }

    public static function getOldestOverdueTasks(int $limit = 10)
    {
        return static::query()
            ->overdue()
            ->with('assignedOfficer')
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'progress_updated_at' => 'datetime',
        ];
    }
}
