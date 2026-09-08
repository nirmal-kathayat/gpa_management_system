<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Admins work across every school; teachers and staff are confined to the
     * one they belong to. Route model binding hands a controller any record by
     * id, so every read and write of a school-owned record goes through here.
     */
    protected function authorizeSchool(?int $schoolId): void
    {
        abort_unless(
            auth()->check() && auth()->user()->canManageSchool($schoolId),
            403,
            'You do not have access to this school\'s records.'
        );
    }

    /**
     * The schools the current user may file records under.
     */
    protected function selectableSchools(): Collection
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return School::orderBy('name')->get();
        }

        return School::where('id', $user->school_id)->get();
    }
}
