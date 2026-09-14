{{-- The downloadable mark sheet. Same markup and stylesheet as reports/show. --}}
@php $pdf = true; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mark Sheet - {{ $report->student->name }} - {{ $report->academic_year }}</title>
    @include('reports._pdf-style')
</head>

<body>
    @include('reports._sheet')
</body>

</html>
