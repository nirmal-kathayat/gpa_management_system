{{-- The downloadable mark sheet. Same markup and stylesheet as reports/show. --}}
@php $pdf = true; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mark Sheet - {{ $report->student->name }} - {{ $report->academic_year }}</title>
    <style>
        /* DomPDF cannot follow a <link>, so the sheet's stylesheet is inlined. */
        {!! file_get_contents(public_path('css/marksheet.css')) !!}

        @page { margin: 12mm 10mm; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
        }

        /* On paper the page itself is the sheet, so it needs no card of its own. */
        .sheet {
            max-width: none;
            padding: 0;
            border: 0;
            border-radius: 0;
            font-family: DejaVu Sans, sans-serif;
        }
    </style>
</head>

<body>
    @include('reports._sheet')
</body>

</html>
