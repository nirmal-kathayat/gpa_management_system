<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\School;
use App\Models\Subject;
use App\Models\StudentMark;
use App\Models\GradeSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkImportController extends Controller
{
    public function index()
    {
        return view('bulk-import.index');
    }

    public function importStudents(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csvData);

        $errors = [];
        $imported = 0;

        DB::beginTransaction();
        try {
            foreach ($csvData as $index => $row) {
                $data = array_combine($header, $row);
                
                $validator = Validator::make($data, [
                    'name' => 'required|string|max:255',
                    'class' => 'required|string|max:50',
                    'section' => 'required|string|max:10',
                    'roll_number' => 'required|integer',
                    'school_id' => 'required|exists:schools,id'
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
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
                    'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                ]);

                $imported++;
            }

            DB::commit();
            
            $message = "Successfully imported {$imported} students.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }

            return redirect()->route('bulk-import.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('bulk-import.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ];

        $columns = [
            'name',
            'class', 
            'section',
            'roll_number',
            'school_id',
            'father_name',
            'mother_name',
            'address',
            'phone',
            'date_of_birth'
        ];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add sample data
            fputcsv($file, [
                'John Doe',
                'SEVEN',
                'A',
                '1',
                '1',
                'John Father',
                'John Mother',
                'Kathmandu, Nepal',
                '9841234567',
                '2010-01-15'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
