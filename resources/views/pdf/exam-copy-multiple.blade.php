<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Exam Report - {{ $student->user->name ?? 'Student' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2c3e50;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 26px;
        }

        .header h2 {
            margin: 10px 0 5px 0;
            color: #34495e;
            font-size: 18px;
        }

        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #3498db;
            color: white;
            padding: 10px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 30%;
            padding: 10px;
            background-color: #ecf0f1;
            font-weight: bold;
            border: 1px solid #bdc3c7;
        }

        .info-value {
            display: table-cell;
            width: 70%;
            padding: 10px;
            border: 1px solid #bdc3c7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 11px;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .grade-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 12px;
        }

        .grade-a {
            background-color: #d4edda;
            color: #155724;
        }

        .grade-b {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .grade-c {
            background-color: #fff3cd;
            color: #856404;
        }

        .grade-d {
            background-color: #f8d7da;
            color: #721c24;
        }

        .grade-f {
            background-color: #f5c6cb;
            color: #721c24;
        }

        .summary-box {
            background-color: #e8f4f8;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .summary-item {
            display: inline-block;
            width: 48%;
            margin-bottom: 15px;
            vertical-align: top;
        }

        .summary-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #bdc3c7;
            text-align: center;
            color: #7f8c8d;
            font-size: 10px;
        }

        .signature-section {
            margin-top: 60px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $organization->name ?? 'School Name' }}</h1>
        <p>{{ $organization->address ?? 'School Address' }}</p>
        <h2>Comprehensive Exam Report</h2>
        <p>{{ $exam->exam_name ?? 'Exam Name' }}</p>
    </div>

    <!-- Student Information -->
    <div class="section">
        <div class="section-title">Student Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Student Name</div>
                <div class="info-value">{{ $student->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Roll Number</div>
                <div class="info-value">{{ $student->roll_no ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Standard</div>
                <div class="info-value">{{ $examCopies->first()->standard->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Section</div>
                <div class="info-value">{{ $examCopies->first()->section->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Report Date</div>
                <div class="info-value">{{ date('d-m-Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Overall Summary -->
    <div class="section">
        <div class="section-title">Performance Summary</div>
        <div class="summary-box">
            @php
                $totalMarksObtained = $examCopies->sum('marks_obtained');
                $totalMaxMarks = $examCopies->sum('max_marks');
                $overallPercentage = $totalMaxMarks > 0 ? round(($totalMarksObtained / $totalMaxMarks) * 100, 2) : 0;
                $averagePercentage = $examCopies->avg('percentage');

                // Calculate overall grade
                if ($overallPercentage >= 90) {
                    $overallGrade = 'A+';
                } elseif ($overallPercentage >= 80) {
                    $overallGrade = 'A';
                } elseif ($overallPercentage >= 70) {
                    $overallGrade = 'B+';
                } elseif ($overallPercentage >= 60) {
                    $overallGrade = 'B';
                } elseif ($overallPercentage >= 50) {
                    $overallGrade = 'C+';
                } elseif ($overallPercentage >= 40) {
                    $overallGrade = 'C';
                } elseif ($overallPercentage >= 33) {
                    $overallGrade = 'D';
                } else {
                    $overallGrade = 'F';
                }
            @endphp

            <div class="summary-item">
                <div class="summary-label">Total Marks Obtained</div>
                <div class="summary-value">{{ $totalMarksObtained }} / {{ $totalMaxMarks }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Overall Percentage</div>
                <div class="summary-value">{{ $overallPercentage }}%</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Overall Grade</div>
                <div class="summary-value">{{ $overallGrade }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">Total Subjects</div>
                <div class="summary-value">{{ $examCopies->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Subject-wise Performance -->
    <div class="section">
        <div class="section-title">Subject-wise Performance</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 30%">Subject</th>
                    <th style="width: 15%">Marks Obtained</th>
                    <th style="width: 15%">Max Marks</th>
                    <th style="width: 15%">Percentage</th>
                    <th style="width: 10%">Grade</th>
                    <th style="width: 10%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($examCopies as $index => $examCopy)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $examCopy->subject->name ?? 'N/A' }}</strong></td>
                        <td>{{ $examCopy->marks_obtained }}</td>
                        <td>{{ $examCopy->max_marks }}</td>
                        <td>{{ $examCopy->percentage }}%</td>
                        <td>
                            @php
                                $gradeClass = 'grade-f';
                                if (in_array($examCopy->grade, ['A', 'A+'])) {
                                    $gradeClass = 'grade-a';
                                } elseif (in_array($examCopy->grade, ['B', 'B+'])) {
                                    $gradeClass = 'grade-b';
                                } elseif (in_array($examCopy->grade, ['C', 'C+'])) {
                                    $gradeClass = 'grade-c';
                                } elseif ($examCopy->grade === 'D') {
                                    $gradeClass = 'grade-d';
                                }
                            @endphp
                            <span class="grade-badge {{ $gradeClass }}">{{ $examCopy->grade }}</span>
                        </td>
                        <td>
                            @if ($examCopy->percentage >= 33)
                                <span style="color: #155724; font-weight: bold;">Pass</span>
                            @else
                                <span style="color: #721c24; font-weight: bold;">Fail</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Remarks Section -->
    @php
        $remarksExist = $examCopies->filter(function ($copy) {
            return !empty($copy->remarks);
        });
    @endphp

    @if ($remarksExist->count() > 0)
        <div class="section">
            <div class="section-title">Subject-wise Remarks</div>
            @foreach ($remarksExist as $examCopy)
                <div
                    style="margin-bottom: 15px; padding: 10px; background-color: #fef9e7; border-left: 4px solid #f39c12;">
                    <strong>{{ $examCopy->subject->name }}:</strong> {{ $examCopy->remarks }}
                </div>
            @endforeach
        </div>
    @endif

    <!-- Grading Scale -->
    <div class="section">
        <div class="section-title">Grading Scale</div>
        <table>
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>Percentage Range</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="grade-badge grade-a">A+</span></td>
                    <td>90% - 100%</td>
                    <td>Outstanding</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-a">A</span></td>
                    <td>80% - 89%</td>
                    <td>Excellent</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-b">B+</span></td>
                    <td>70% - 79%</td>
                    <td>Very Good</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-b">B</span></td>
                    <td>60% - 69%</td>
                    <td>Good</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-c">C+</span></td>
                    <td>50% - 59%</td>
                    <td>Above Average</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-c">C</span></td>
                    <td>40% - 49%</td>
                    <td>Average</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-d">D</span></td>
                    <td>33% - 39%</td>
                    <td>Pass</td>
                </tr>
                <tr>
                    <td><span class="grade-badge grade-f">F</span></td>
                    <td>0% - 32%</td>
                    <td>Fail</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">Class Teacher</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Principal</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Parent/Guardian</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ date('d-m-Y H:i:s') }}</p>
        <p>This is a computer-generated document and does not require a signature.</p>
        <p>&copy; {{ date('Y') }} {{ $organization->name ?? 'School Name' }}. All rights reserved.</p>
    </div>
</body>

</html>
