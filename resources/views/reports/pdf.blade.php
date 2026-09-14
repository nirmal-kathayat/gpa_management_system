{{-- The downloadable mark sheet. Same markup and stylesheet as reports/show. --}}
@php $pdf = true; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mark Sheet - {{ $report->student->name }} - {{ $report->academic_year }}</title>
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
            file_get_contents(public_path('css/marksheet.css'))
        ) !!}

        @page { margin: 10mm 10mm; }

        /* The bold face is wider than the screen font, and a subject name
           that wraps pushes the signatures onto a second page. There is room
           across the row for the names if the number columns give way. */
        .sheet-subject { white-space: nowrap; }

        /* And the rows sit a touch tighter than on screen, so the whole
           sheet, signatures included, stays on one A4 page. */
        .sheet-table th, .sheet-table td { padding: 2px 6px; }
        .sheet-pairs td { padding: 4px 9px; }
        .sheet-legend td { padding: 3px 6px; }
        .sheet-result { margin-top: 8px; padding: 6px 14px; }
        .sheet-remarks { margin-top: 8px; padding: 6px 12px; }
        .sheet-signs { margin-top: 8px; }

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
