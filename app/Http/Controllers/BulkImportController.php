<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Adds many students at once from a CSV.
 *
 * A bad row is never allowed to cost the whole file: rows are read and checked
 * one at a time, the good ones are written, and every skipped row comes back
 * with the line number and the reason it was skipped.
 */
class BulkImportController extends Controller
{
    /** Columns a row cannot be imported without. */
    private const REQUIRED = ['name', 'class', 'section', 'roll_number', 'school_id'];

    /** Read if the file carries them, ignored if it does not. */
    private const OPTIONAL = ['father_name', 'mother_name', 'address', 'phone', 'date_of_birth'];

    /** Enough for a whole school; beyond this the file wants splitting. */
    private const MAX_ROWS = 2000;

    /** The class the template's example row is filed under. */
    private const SAMPLE_CLASS = '10';
    private const SAMPLE_SECTION = 'A';

    public function index()
    {
        return view('bulk-import.index', [
            'schools' => $this->selectableSchools(),
        ]);
    }

    public function importStudents(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $read = $this->read($request->file('csv_file')->getRealPath());

        if (isset($read['error'])) {
            return back()->with('error', $read['error']);
        }

        $result = $this->importRows($read['rows']);

        return redirect()->route('bulk-import.index')
            ->with($result['imported'] > 0 ? 'success' : 'warning', $this->summary($result))
            ->with('importResult', $result);
    }

    /**
     * Reads the file into one array per row, keyed by the header.
     *
     * fgetcsv is used rather than str_getcsv over file() so that quoted fields
     * holding commas or newlines survive, and a row whose column count does not
     * match the header is reported instead of throwing.
     */
    private function read(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return ['error' => 'That file could not be opened. Try uploading it again.'];
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return ['error' => 'That file is empty.'];
        }

        // Excel writes a byte order mark, which would otherwise leave the first
        // column name unrecognisable.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($header[0] ?? ''));
        $header = array_map(fn ($name) => strtolower(trim((string) $name)), $header);

        if ($missing = array_diff(self::REQUIRED, $header)) {
            fclose($handle);

            return ['error' => 'The file is missing these columns: '.implode(', ', $missing)
                .'. Download the template to see the format.'];
        }

        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            // fgetcsv hands back [null] for a blank line.
            if ($values === [null] || trim(implode('', array_map('strval', $values))) === '') {
                continue;
            }

            if (count($values) !== count($header)) {
                $rows[] = [
                    'line' => $line,
                    'data' => null,
                    'error' => 'This row has '.count($values).' columns; the header has '
                        .count($header).'. A value holding a comma needs quotes around it.',
                ];

                continue;
            }

            // An empty cell is nothing, not an empty string: 'nullable' rules
            // only skip a value that is actually null.
            $values = array_map(function ($value) {
                $value = trim((string) $value);

                return $value === '' ? null : $value;
            }, $values);

            $rows[] = ['line' => $line, 'data' => array_combine($header, $values), 'error' => null];

            if (count($rows) > self::MAX_ROWS) {
                fclose($handle);

                return ['error' => 'That file has more than '.self::MAX_ROWS
                    .' rows. Split it into smaller files.'];
            }
        }

        fclose($handle);

        if (! $rows) {
            return ['error' => 'That file has a header but no rows under it.'];
        }

        return ['rows' => $rows];
    }

    /**
     * Writes the rows that pass. The transaction is there for a database
     * failure; a row the file got wrong is skipped, not thrown.
     */
    private function importRows(array $rows): array
    {
        // The school id in the file is not trusted just because it exists - it
        // has to be one this user may file records under.
        $allowed = $this->selectableSchools()->pluck('id')->all();

        $imported = 0;
        $skipped = [];

        // Roll numbers already used, so the file cannot duplicate itself either.
        $seen = [];

        DB::transaction(function () use ($rows, $allowed, &$imported, &$skipped, &$seen) {
            foreach ($rows as $row) {
                if ($row['error'] !== null) {
                    $skipped[] = ['line' => $row['line'], 'name' => '—', 'reason' => $row['error']];

                    continue;
                }

                $data = $row['data'];

                $validator = Validator::make($data, [
                    'name' => 'required|string|max:255',
                    'class' => 'required|string|max:50',
                    'section' => 'required|string|max:10',
                    'roll_number' => 'required|integer|min:1',
                    'school_id' => ['required', Rule::in($allowed)],
                    'father_name' => 'nullable|string|max:255',
                    'mother_name' => 'nullable|string|max:255',
                    'address' => 'nullable|string|max:500',
                    'phone' => 'nullable|string|max:20',
                    // Fixed format on purpose: 02-01-2011 is a different day
                    // depending on where you are from.
                    'date_of_birth' => 'nullable|date_format:Y-m-d',
                ], [
                    'school_id.in' => 'That is not a school you can import into.',
                    'date_of_birth.date_format' => 'The date of birth must be written as YYYY-MM-DD.',
                ]);

                if ($validator->fails()) {
                    $skipped[] = [
                        'line' => $row['line'],
                        'name' => $data['name'] ?? '—',
                        'reason' => implode(' ', $validator->errors()->all()),
                    ];

                    continue;
                }

                $key = implode('|', [
                    $data['school_id'],
                    mb_strtolower($data['class']),
                    mb_strtolower($data['section']),
                    (int) $data['roll_number'],
                ]);

                if (isset($seen[$key])) {
                    $skipped[] = [
                        'line' => $row['line'],
                        'name' => $data['name'],
                        'reason' => 'Line '.$seen[$key].' of this file already uses roll number '
                            .$data['roll_number'].' in class '.$data['class'].' '.$data['section'].'.',
                    ];

                    continue;
                }

                $exists = Student::where('school_id', $data['school_id'])
                    ->where('class', $data['class'])
                    ->where('section', $data['section'])
                    ->where('roll_number', $data['roll_number'])
                    ->exists();

                if ($exists) {
                    $skipped[] = [
                        'line' => $row['line'],
                        'name' => $data['name'],
                        'reason' => 'A student with roll number '.$data['roll_number'].' already exists in class '
                            .$data['class'].' '.$data['section'].' at that school.',
                    ];

                    continue;
                }

                Student::create([
                    'name' => $data['name'],
                    'class' => $data['class'],
                    'section' => $data['section'],
                    'roll_number' => $data['roll_number'],
                    'school_id' => $data['school_id'],
                    'father_name' => $data['father_name'] ?? null,
                    'mother_name' => $data['mother_name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'is_active' => true,
                ]);

                $seen[$key] = $row['line'];
                $imported++;
            }
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'read' => count($rows),
        ];
    }

    private function summary(array $result): string
    {
        $imported = $result['imported'] === 1 ? '1 student imported.' : $result['imported'].' students imported.';

        if (! $result['skipped']) {
            return $imported;
        }

        $count = count($result['skipped']);

        return $imported.' '.($count === 1 ? '1 row skipped.' : $count.' rows skipped.');
    }

    /**
     * The sample row is built from live data so that downloading the template
     * and uploading it straight back actually imports: a real school id, and a
     * roll number that is free in that class rather than one already taken.
     */
    public function downloadTemplate()
    {
        $columns = array_merge(self::REQUIRED, self::OPTIONAL);
        $schoolId = $this->selectableSchools()->first()?->id ?? 1;

        $roll = 1 + (int) Student::where('school_id', $schoolId)
            ->where('class', self::SAMPLE_CLASS)
            ->where('section', self::SAMPLE_SECTION)
            ->max('roll_number');

        $sample = [
            'name' => 'Ramesh Thapa',
            'class' => self::SAMPLE_CLASS,
            'section' => self::SAMPLE_SECTION,
            'roll_number' => (string) $roll,
            'school_id' => (string) $schoolId,
            'father_name' => 'Hari Thapa',
            'mother_name' => 'Sita Thapa',
            'address' => 'Baneshwor, Kathmandu',
            'phone' => '9841234567',
            'date_of_birth' => '2010-01-15',
        ];

        return response()->stream(function () use ($columns, $sample) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, array_map(fn ($column) => $sample[$column], $columns));
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ]);
    }
}
