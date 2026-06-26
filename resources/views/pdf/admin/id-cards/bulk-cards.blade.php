<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bulk ID Cards - {{ now()->format('Y-m-d') }}</title>
    <style>
        @page {
            margin: 5mm;
            size: A4 portrait;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
            background: #f5f5f5 !important;
        }

        .page-container {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
            box-sizing: border-box;
            page-break-after: always;
            background: white;
            position: relative;
        }

        .page-header {
            text-align: center;
            margin-bottom: 15mm;
            padding-bottom: 8mm;
            border-bottom: 2px solid #4f46e5;
        }

        .title {
            color: #1f2937;
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 5mm 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-gap: 12mm;
            justify-items: center;
            align-items: start;
        }

        /* Individual Card Styles (Same as single card but scaled) */
        .card-wrapper {
            width: 86mm;
            height: 54mm;
            page-break-inside: avoid;
            break-inside: avoid;
            position: relative;
        }

        .id-card {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg,
                    #4f46e5 0%,
                    #7c3aed 30%,
                    #8b5cf6 70%,
                    #a78bfa 100%);
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Card Header */
        .card-header {
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(4px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .org-name {
            color: white;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-align: center;
            font-family: 'Arial Black', sans-serif;
        }

        /* Card Content */
        .card-content {
            padding: 6px 8px;
            display: flex;
            gap: 5px;
            flex: 1;
        }

        /* Photo Section */
        .photo-column {
            flex: 0 0 20mm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-frame {
            width: 19mm;
            height: 24mm;
            background: white;
            border-radius: 6px;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .student-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            font-family: 'Arial Black', sans-serif;
        }

        /* Information Section */
        .info-column {
            flex: 1;
            color: white;
            min-width: 0;
        }

        .student-name {
            font-size: 9px;
            font-weight: 800;
            margin-bottom: 4px;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: rgba(255, 255, 255, 0.1);
            padding: 3px 6px;
            border-radius: 3px;
            border-left: 2px solid #fbbf24;
        }

        .info-row {
            font-size: 6px;
            margin-bottom: 3px;
            line-height: 1.2;
            display: flex;
            align-items: center;
        }

        .info-label {
            font-weight: 700;
            min-width: 15mm;
            color: rgba(255, 255, 255, 0.9);
            text-transform: uppercase;
            font-size: 5.5px;
        }

        .info-label::after {
            content: ':';
            color: #fbbf24;
            margin-left: 1px;
        }

        .info-value {
            font-weight: 600;
            color: white;
            flex: 1;
            padding-left: 3px;
            font-size: 6px;
        }

        /* QR Code Section */
        .qr-column {
            flex: 0 0 16mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-container {
            width: 15mm;
            height: 15mm;
            background: white;
            border-radius: 4px;
            padding: 1px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .qr-code {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 3px;
        }

        .qr-label {
            color: white;
            font-size: 5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: rgba(0, 0, 0, 0.2);
            padding: 1px 4px;
            border-radius: 6px;
            font-family: 'Arial Black', sans-serif;
        }

        /* Card Footer */
        .card-footer {
            padding: 3px 8px;
            background: rgba(0, 0, 0, 0.25);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .card-number {
            font-size: 6px;
            font-weight: 700;
            letter-spacing: 0.3px;
            font-family: 'Consolas', monospace;
        }

        .card-status {
            font-size: 5px;
            font-weight: 800;
            padding: 1px 6px;
            border-radius: 8px;
            text-transform: uppercase;
        }

        .status-active {
            background: linear-gradient(45deg, #10b981, #059669);
        }

        .status-inactive {
            background: linear-gradient(45deg, #ef4444, #dc2626);
        }

        /* Page Footer */
        .page-footer {
            position: absolute;
            bottom: 10mm;
            left: 10mm;
            right: 10mm;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
            padding-top: 10mm;
            border-top: 1px solid #e5e7eb;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-number {
            font-weight: 600;
        }

        .print-date {
            font-weight: 600;
        }

        /* Print Optimizations */
        @media print {
            body {
                background: white !important;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .page-container {
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                background: white !important;
            }

            .id-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .card-wrapper {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    @php
        $totalPages = ceil($cards->count() / 6);
        $currentPage = 1;
    @endphp

    @foreach ($cards->chunk(6) as $pageCards)
        <div class="page-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="title">STUDENT ID CARDS</h1>
                <p class="subtitle">{{ $organization->name ?? 'SCHOOL MANAGEMENT SYSTEM' }}</p>
                <p class="subtitle" style="font-size: 12px; margin-top: 3mm;">
                    Generated on {{ now()->format('F d, Y - h:i A') }} | Total Cards: {{ $cards->count() }}
                </p>
            </div>

            <!-- Cards Grid -->
            <div class="grid-container">
                @foreach ($pageCards as $card)
                    @php
                        $student = $card->studentDetail;
                    @endphp
                    <div class="card-wrapper">
                        <div class="id-card">
                            <!-- Card Header -->
                            <div class="card-header">
                                <div class="org-name">{{ $organization->name ?? 'SCHOOL' }}</div>
                            </div>

                            <!-- Card Content -->
                            <div class="card-content">
                                <!-- Photo Column -->
                                <div class="photo-column">
                                    <div class="photo-frame">
                                        @if ($student && $student->image)
                                            @php
                                                $imagePath = storage_path('app/public/' . $student->image);
                                                if (file_exists($imagePath)) {
                                                    $imageData = base64_encode(file_get_contents($imagePath));
                                                    $imageSrc = 'data:image/jpeg;base64,' . $imageData;
                                                } else {
                                                    $imageSrc = null;
                                                }
                                            @endphp

                                            @if ($imageSrc)
                                                <img src="{{ $imageSrc }}" class="student-photo"
                                                    alt="Student Photo">
                                            @else
                                                <div class="photo-placeholder">
                                                    {{ strtoupper(substr($student->full_name ?? 'S', 0, 1)) }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="photo-placeholder">
                                                {{ strtoupper(substr($student->full_name ?? 'S', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Information Column -->
                                <div class="info-column">
                                    <div class="student-name" title="{{ $student->full_name ?? 'Student Name' }}">
                                        {{ $student->full_name ?? 'Student Name' }}
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Admission No</span>
                                        <span class="info-value">{{ $student->admission_no ?? 'N/A' }}</span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Class & Section</span>
                                        <span class="info-value">
                                            {{ $student->standard->name ?? 'N/A' }}
                                            @if ($student->section)
                                                - {{ $student->section->name }}
                                            @endif
                                        </span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Valid Till</span>
                                        <span class="info-value">{{ $card->expiry_date->format('d/m/Y') }}</span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Issue Date</span>
                                        <span class="info-value">{{ $card->issue_date->format('d/m/Y') }}</span>
                                    </div>
                                </div>

                                <!-- QR Code Column -->
                                <div class="qr-column">
                                    <div class="qr-container">
                                        @if ($card->qr_code)
                                            <img src="data:image/png;base64,{{ $card->qr_code }}" class="qr-code"
                                                alt="QR Code">
                                        @else
                                            <div
                                                style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#666;font-size:5px;font-weight:bold;text-align:center;">
                                                QR<br>CODE
                                            </div>
                                        @endif
                                    </div>
                                    <div class="qr-label">SCAN</div>
                                </div>
                            </div>

                            <!-- Card Footer -->
                            <div class="card-footer">
                                <div class="card-details">
                                    <div class="card-number">
                                        {{ $card->card_number }}
                                    </div>
                                    <div
                                        class="card-status {{ $card->status == 'active' ? 'status-active' : 'status-inactive' }}">
                                        {{ strtoupper($card->status) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Page Footer -->
            <div class="page-footer">
                <div class="footer-info">
                    <div class="print-date">
                        Printed: {{ now()->format('d/m/Y H:i') }}
                    </div>
                    <div class="page-number">
                        Page {{ $currentPage }} of {{ $totalPages }}
                    </div>
                    <div style="font-weight: 600;">
                        Total: {{ $cards->count() }} cards
                    </div>
                </div>
            </div>
        </div>
        @php $currentPage++; @endphp
    @endforeach
</body>

</html>
