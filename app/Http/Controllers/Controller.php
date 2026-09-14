<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
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
     * Moves an uploaded image into public/assets/{$folder} and returns the path
     * to store, or the current path when the form did not carry a new file.
     *
     * The file is saved under a random name rather than the one it was
     * uploaded with, so a user's filename never reaches the filesystem, two
     * uploads cannot collide, and the URL gives nothing away. A replaced
     * image is removed so the folder does not fill with orphans.
     */
    protected function storeImage(Request $request, string $field, string $folder, ?string $current = null): ?string
    {
        if (! $request->hasFile($field)) {
            return $current;
        }

        $destination = public_path('assets/'.$folder);
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file = $request->file($field);
        $name = $file->hashName();
        $file->move($destination, $name);

        if ($current && is_file(public_path($current))) {
            unlink(public_path($current));
        }

        return 'assets/'.$folder.'/'.$name;
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
