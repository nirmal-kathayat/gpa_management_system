<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A class a school runs, with its sections: "10" with A and B. Students and
 * report cards still carry the names as text - this is what the pickers
 * offer and what a student record is checked against.
 */
class SchoolClass extends Model
{
    protected $fillable = ['school_id', 'name', 'sections', 'sort_order'];

    protected $casts = ['sections' => 'array'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /** Lowest class first: by number, then by name for anything unnumbered. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Students sitting in this class right now. */
    public function students()
    {
        return $this->hasMany(Student::class, 'school_id', 'school_id')->where('class', $this->name);
    }

    public function hasSection(string $section): bool
    {
        return in_array($section, $this->sections ?? [], true);
    }

    /**
     * The class => sections map for one or more schools, keyed by school id:
     * the shape the class picker and the student form cascade from.
     */
    public static function mapFor(array $schoolIds): array
    {
        $map = [];

        foreach (static::whereIn('school_id', $schoolIds)->ordered()->get() as $class) {
            $map[$class->school_id][$class->name] = array_values($class->sections ?? []);
        }

        return $map;
    }

    /** Whether a school runs this class and section. */
    public static function runs(int $schoolId, string $class, string $section): bool
    {
        $row = static::where('school_id', $schoolId)->where('name', $class)->first();

        return $row !== null && $row->hasSection($section);
    }
}
