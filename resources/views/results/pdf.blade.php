{{-- The downloadable result sheet. Same markup and stylesheet as results/index. --}}
@php $pdf = true; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Class {{ $sheet->filters['class'] }} {{ $sheet->filters['section'] }} - {{ $sheet->examLabel }} - {{ $sheet->filters['academic_year'] }}</title>
    <style>
        /*
         * DomPDF cannot follow a <link>, so the sheet's stylesheet is inlined.
         * It also has only a normal and a bold face for DejaVu Sans, and a
         * weight it cannot find (500, 600) silently falls back to a serif -
         * so those are folded into the two faces it has.
         */
        {!! str_replace(
            ['font-weight: 500', 'font-weight: 600'],
            ['font-weight: normal', 'font-weight: bold'],
            file_get_contents(public_path('css/resultsheet.css'))
        ) !!}

        @page { margin: 10mm 9mm; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
        }

        .rsheet {
            max-width: none;
            padding: 0;
            border: 0;
            border-radius: 0;
            font-family: DejaVu Sans, sans-serif;
        }
    </style>
</head>

<body>
    @include('results._sheet')
</body>

</html>
