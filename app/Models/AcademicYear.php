<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A year the school runs, e.g. 2081. One of them is the current one, which
 * every year picker starts on.
 */
class AcademicYear extends Model
{
    protected $fillable = ['year', 'is_current'];

    protected $casts = ['is_current' => 'boolean'];

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('year');
    }

    /** The year in progress, or the latest one when none is marked. */
    public static function current(): ?string
    {
        return static::where('is_current', true)->value('year')
            ?? static::ordered()->value('year');
    }

    /** Makes this the one current year. */
    public function makeCurrent(): void
    {
        static::where('is_current', true)->where('id', '!=', $this->id)->update(['is_current' => false]);
        $this->update(['is_current' => true]);
    }

    public function reports()
    {
        return $this->hasMany(StudentReport::class, 'academic_year', 'year');
    }
}
