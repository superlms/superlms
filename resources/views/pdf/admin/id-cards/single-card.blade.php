<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student ID Card - {{ $card->card_number }}</title>
    <style>
        /* CSS Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            margin: 0;
            padding: 0;
            size: 86mm 54mm;
        }

        body {
            width: 86mm;
            height: 54mm;
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            position: relative;
            overflow: hidden;
            background: white;
        }

        /* Base card with solid color as fallback */
        .id-card {
            width: 100%;
            height: 100%;
            background-color: #4f46e5;
            /* Fallback color */
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Gradient overlay using multiple divs (no URL encoding issues) */
        .gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg,
                    rgba(79, 70, 229, 0.9) 0%,
                    rgba(124, 58, 237, 0.8) 50%,
                    rgba(139, 92, 246, 0.7) 100%);
            z-index: 1;
        }

        /* Simple pattern using CSS instead of SVG URL */
        .pattern-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                linear-gradient(45deg, transparent 49%, rgba(255, 255, 255, 0.1) 50%, transparent 51%),
                linear-gradient(-45deg, transparent 49%, rgba(255, 255, 255, 0.1) 50%, transparent 51%);
            background-size: 20px 20px;
            z-index: 2;
            opacity: 0.3;
        }

        /* Content wrapper */
        .card-content {
            position: relative;
            z-index: 10;
            width: 100%;
            height: 100%;
            padding: 8px 10px;
            display: flex;
            gap: 6px;
        }

        /* Header */
        .card-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 6px 10px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 20;
        }

        .org-name {
            color: white;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        /* Photo Column */
        .photo-column {
            flex: 0 0 28mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
            /* Space for header */
        }

        .photo-frame {
            width: 26mm;
            height: 32mm;
            background: white;
            border-radius: 8px;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 6px;
            position: relative;
        }

        .photo-frame::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 2px solid transparent;
            background: linear-gradient(45deg, transparent 48%, rgba(79, 70, 229, 0.3) 50%, transparent 52%);
            z-index: 1;
            pointer-events: none;
        }

        .student-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
            position: relative;
            z-index: 0;
        }

        .photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-size: 24px;
            font-weight: bold;
            border-radius: 5px;
        }

        /* Information Column */
        .info-column {
            flex: 1;
            color: white;
            min-width: 0;
            margin-top: 30px;
            /* Space for header */
        }

        .student-name {
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: rgba(0, 0, 0, 0.2);
            padding: 6px 8px;
            border-radius: 4px;
            border-left: 3px solid #fbbf24;
        }

        .info-row {
            font-size: 7px;
            margin-bottom: 4px;
            line-height: 1.4;
            display: flex;
            align-items: center;
        }

        .info-label {
            font-weight: 700;
            min-width: 22mm;
            color: rgba(255, 255, 255, 0.95);
            text-transform: uppercase;
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
            padding-left: 4px;
        }

        /* QR Code Column */
        .qr-column {
            flex: 0 0 20mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 30px;
            /* Space for header */
        }

        .qr-container {
            width: 18mm;
            height: 18mm;
            background: white;
            border-radius: 6px;
            padding: 2px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
            margin-bottom: 4px;
            position: relative;
            overflow: hidden;
        }

        .qr-code {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }

        .qr-label {
            color: white;
            font-size: 6px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 10px;
        }

        /* Footer */
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.4);
            padding: 6px 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 20;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .card-number {
            font-size: 7px;
            font-weight: 700;
            letter-spacing: 0.5px;
            font-family: 'Courier New', monospace;
        }

        .card-status {
            font-size: 6px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .status-active {
            background: #10b981;
        }

        .status-inactive {
            background: #ef4444;
        }

        /* Print styles */
        @media print {
            body {
                margin: 0 !important;
                padding: 0 !important;
                width: 86mm !important;
                height: 54mm !important;
                overflow: hidden !important;
                background: transparent !important;
            }

            .id-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                box-shadow: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="id-card">
        <!-- Gradient Background -->
        <div class="gradient-overlay"></div>
        <div class="pattern-overlay"></div>

        <!-- Header -->
        <div class="card-header">
            <h1 class="org-name">{{ $organization->name ?? 'SCHOOL MANAGEMENT SYSTEM' }}</h1>
        </div>

        <!-- Main Content -->
        <div class="card-content">
            <!-- Left Column - Photo -->
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
                            <img src="{{ $imageSrc }}" class="student-photo" alt="Student Photo">
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

            <!-- Middle Column - Information -->
            <div class="info-column">
                <div class="student-name">
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
                    <span class="info-label">Date of Birth</span>
                    <span class="info-value">
                        @if ($student->dob)
                            {{ \Carbon\Carbon::parse($student->dob)->format('d/m/Y') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Father's Name</span>
                    <span class="info-value">{{ $student->father_name ?? 'N/A' }}</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Valid Till</span>
                    <span class="info-value">{{ $card->expiry_date->format('d/m/Y') }}</span>
                </div>
            </div>

            <!-- Right Column - QR Code -->
            <div class="qr-column">
                <div class="qr-container">
                    @if ($card->qr_code)
                        <img src="data:image/png;base64,{{ $card->qr_code }}" class="qr-code" alt="QR Code">
                    @else
                        <div
                            style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#666;font-size:6px;font-weight:bold;text-align:center;">
                            QR<br>CODE
                        </div>
                    @endif
                </div>
                <div class="qr-label">SCAN TO VERIFY</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="card-footer">
            <div class="footer-content">
                <div class="card-number">
                    Card No: {{ $card->card_number }}
                </div>
                <div class="card-status {{ $card->status == 'active' ? 'status-active' : 'status-inactive' }}">
                    {{ strtoupper($card->status) }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
