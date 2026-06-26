<!DOCTYPE html>
<html>

<head>
    <style>
        .id-card {
            width: 85mm;
            height: 54mm;
            border: 1px solid #ccc;
            margin: 5mm;
            display: inline-block;
            page-break-inside: avoid;
        }

        /* Add your ID card styling here */
    </style>
</head>

<body>
    @foreach ($cards as $card)
        <div class="id-card">
            <!-- Your ID card design here -->
            <h3>{{ $organization->name }}</h3>
            <p>Student: {{ $card->student->full_name ?? 'N/A' }}</p>
            <p>Card: {{ $card->card_number }}</p>
            <p>Valid Until: {{ $card->expiry_date->format('d/m/Y') }}</p>
        </div>
    @endforeach
</body>

</html>
