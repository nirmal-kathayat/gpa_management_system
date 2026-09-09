<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Turns an Eloquent query into the JSON envelope TableHelper expects
 * ({ success, data, total }), applying the global search, the header-row column
 * filters, sorting and paging that the table sends as query parameters.
 *
 * See TABLE-HELPER-GUIDE.md §3 (backend contract) and §16 (Laravel recipe).
 */
class TableResponse
{
    /** Hard ceiling so a crafted per_page cannot ask for the whole table. */
    private const MAX_PER_PAGE = 200;

    /**
     * @param  array  $spec  [
     *     'search'  => ['name', 'email'],          columns OR'd together for the search box
     *     'filters' => ['name' => 'name', ...],    request param => column, or a closure
     *     'sort'    => ['name' => 'users.name'],   whitelist for sort_field
     *     'default' => ['name', 'asc'],            fallback ordering
     * ]
     * @param  callable  $map  fn ($model, int $serial): array  - one row for the table
     */
    public static function make(Request $request, Builder $query, array $spec, callable $map): JsonResponse
    {
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) $request->input('per_page', 10)));
        $page = max(1, (int) $request->input('page', 1));

        $search = trim((string) $request->input('search', ''));

        // The table sends either a search term or the column filters, never both:
        // applying a filter clears the search box and vice versa.
        if ($search !== '' && ! empty($spec['search'])) {
            $query->where(function (Builder $inner) use ($spec, $search) {
                foreach ($spec['search'] as $column) {
                    if ($column instanceof Closure) {
                        $inner->orWhere(fn (Builder $q) => $column($q, $search));
                    } else {
                        $inner->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        } else {
            foreach ($spec['filters'] ?? [] as $param => $column) {
                $value = trim((string) $request->input($param, ''));

                if ($value === '') {
                    continue;
                }

                if ($column instanceof Closure) {
                    $column($query, $value);
                } elseif (is_array($column)) {
                    // ['column', 'exact'] - for select filters, where LIKE would
                    // let "1" also match "10".
                    $query->where($column[0], '=', $value);
                } else {
                    $query->where($column, 'like', '%'.$value.'%');
                }
            }
        }

        $sortField = (string) $request->input('sort_field', '');
        $direction = strtolower((string) $request->input('sort_direction', '')) === 'desc' ? 'desc' : 'asc';

        if ($sortField !== '' && isset($spec['sort'][$sortField])) {
            $query->orderBy($spec['sort'][$sortField], $direction);
        } elseif (! empty($spec['default'])) {
            $query->orderBy($spec['default'][0], $spec['default'][1] ?? 'asc');
        }

        $total = (clone $query)->count();

        $rows = $query->forPage($page, $perPage)->get();
        $offset = ($page - 1) * $perPage;

        return response()->json([
            'success' => true,
            'data' => $rows->values()->map(fn ($model, $index) => $map($model, $offset + $index + 1))->all(),
            'total' => $total,
        ]);
    }
}
