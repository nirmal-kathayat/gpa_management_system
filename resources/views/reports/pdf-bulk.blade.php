{{--
    Every mark sheet of a class in one PDF, one page per student in roll
    order. Same markup and stylesheet as the single card.
--}}
@php $pdf = true; @endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mark Sheets - Class {{ $class }} {{ $section }} - {{ $academicYear }}</title>
    @include('reports._pdf-style')
    <style>
        /* One student per page. */
        .sheet-page { page-break-after: always; }
        .sheet-page:last-child { page-break-after: auto; }
    </style>
</head>

<body>
    @foreach($cards as $card)
        <div class="sheet-page">
            @include('reports._sheet', $card)
        </div>
    @endforeach
</body>

</html>
