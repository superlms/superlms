<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Exam Copy - {{ $examCopy->studentDetail->user->name ?? 'Student' }}</title>
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
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background-color: #3498db;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
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
            padding: 8px;
            background-color: #ecf0f1;
            font-weight: bold;
            border: 1px solid #bdc3c7;
        }

        .info-value {
            display: table-cell;
            width: 70%;
            padding: 8px;
            border: 1px solid #bdc3c7;
        }

        .marks-card {
            display: inline-block;
            width: 23%;
            margin: 0 1% 10px 0;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            vertical-align: top;
        }

        .marks-card.blue {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
        }

        .marks-card.green {
            background-color: #e8f5e9;
            border: 2px solid #4caf50;
        }

        .marks-card.purple {
            background-color: #f3e5f5;
            border: 2px solid #9c27b0;
        }

        .marks-card.orange {
            background-color: #fff3e0;
            border: 2px solid #ff9800;
        }

        .marks-card h3 {
            margin: 0 0 10px 0;
            font-size: 11px;
            color: #666;
        }

        .marks-card p {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }

        .grade-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #34495e;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #bdc3c7;
            text-align: center;
            color: #7f8c8d;
            font-size: 10px;
        }

        .remarks-box {
            padding: 15px;
            background-color: #fef9e7;
            border-left: 4px solid #f39c12;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $organization->name ?? 'School Name' }}</h1>
        <p>{{ $organization->address ?? 'School Address' }}</p>
        <p>Exam Performance Report</p>
    </div>

    <!-- Student Information -->
    <div class="section">
        <div class="section-title">Student Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Student Name</div>
                <div class="info-value">{{ $examCopy->studentDetail->user->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Roll Number</div>
                <div class="info-value">{{ $examCopy->studentDetail->roll_no ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Standard</div>
                <div class="info-value">{{ $examCopy->standard->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Section</div>
                <div class="info-value">{{ $examCopy->section->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Exam Information -->
    <div class="section">
        <div class="section-title">Exam Information</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Exam Name</div>
                <div class="info-value">{{ $examCopy->exam->exam_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Subject</div>
                <div class="info-value">{{ $examCopy->subject->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Exam Date</div>
                <div class="info-value">{{ $examCopy->created_at->format('d-m-Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Performance Summary -->
    <div class="section">
        <div class="section-title">Performance Summary</div>

        <div class="marks-card blue">
            <h3>Marks Obtained</h3>
            <p>{{ $examCopy->marks_obtained }}</p>
        </div>

        <div class="marks-card green">
            <h3>Max Marks</h3>
            <p>{{ $examCopy->max_marks }}</p>
        </div>

        <div class="marks-card purple">
            <h3>Percentage</h3>
            <p>{{ $examCopy->percentage }}%</p>
        </div>

        <div class="marks-card orange">
            <h3>Grade</h3>
            <p>{{ $examCopy->grade }}</p>
        </div>
    </div>

    @if ($examCopy->examSubjectMarks && count($examCopy->examSubjectMarks) > 0)
        <!-- Subject-wise Marks -->
        <div class="section">
            <div class="section-title">Subject-wise Marks Breakdown</div>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Marks Obtained</th>
                        <th>Max Marks</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($examCopy->examSubjectMarks as $subjectMark)
                        <tr>
                            <td>{{ $subjectMark->subject->name ?? 'N/A' }}</td>
                            <td>{{ $subjectMark->marks_obtained }}</td>
                            <td>{{ $subjectMark->max_marks }}</td>
                            <td>{{ $subjectMark->percentage }}%</td>
                            <td>
                                @php
                                    $gradeClass = 'grade-f';
                                    if (in_array($subjectMark->grade, ['A', 'A+'])) {
                                        $gradeClass = 'grade-a';
                                    } elseif (in_array($subjectMark->grade, ['B', 'B+'])) {
                                        $gradeClass = 'grade-b';
                                    } elseif (in_array($subjectMark->grade, ['C', 'C+'])) {
                                        $gradeClass = 'grade-c';
                                    } elseif ($subjectMark->grade === 'D') {
                                        $gradeClass = 'grade-d';
                                    }
                                @endphp
                                <span class="grade-badge {{ $gradeClass }}">{{ $subjectMark->grade }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($examCopy->remarks)
        <!-- Remarks -->
        <div class="section">
            <div class="section-title">Remarks</div>
            <div class="remarks-box">
                {{ $examCopy->remarks }}
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ date('d-m-Y H:i:s') }}</p>
        <p>This is a computer-generated document and does not require a signature.</p>
    </div>
</body>

</html>
