<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Enrollment Form – {{ $enrollment->enrollment_number }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 40px; }
        .sheet { max-width: 820px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 3px double #1f2937; padding-bottom: 16px; }
        .header h1 { margin: 0 0 4px; font-size: 22px; }
        .header p { margin: 0; color: #4b5563; font-size: 13px; }
        .meta { display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 20px; font-size: 13px; }
        .meta div { flex: 1; min-width: 180px; }
        .meta span { color: #6b7280; display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; font-size: 13px; text-align: left; }
        th { background: #f3f4f6; }
        .section-title { font-size: 14px; font-weight: 700; margin: 24px 0 4px; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 9999px; background: #e0e7ff; font-size: 12px; font-weight: 600; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; font-size: 12px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            <h1>Student Enrollment Record</h1>
            <p>{{ $enrollment->academicYear?->name }}</p>
            <p><span class="status">{{ $enrollment->display_status_label }}</span></p>
        </div>

        <div class="meta">
            <div><span>Enrollment No.</span>{{ $enrollment->enrollment_number }}</div>
            <div><span>Reference No.</span>{{ $enrollment->reference_number }}</div>
            <div><span>Type</span>{{ $enrollment->enrollment_type_label }}</div>
            <div><span>Enrolled</span>{{ $enrollment->date_enrolled?->toFormattedDateString() ?? '—' }}</div>
        </div>

        <div class="section-title">Student</div>
        <table>
            <tr>
                <th>Name</th>
                <td>{{ $enrollment->student?->full_name }}</td>
                <th>Student No.</th>
                <td>{{ $enrollment->student?->student_number }}</td>
            </tr>
            <tr>
                <th>LRN</th>
                <td>{{ $enrollment->student?->lrn ?? '—' }}</td>
                <th>Gender / Age</th>
                <td>{{ $enrollment->student?->gender }} / {{ $enrollment->student?->age }}</td>
            </tr>
        </table>

        <div class="section-title">Assignment</div>
        <table>
            <tr>
                <th>Campus</th>
                <td>{{ $enrollment->campus?->name }}</td>
                <th>Academic Term</th>
                <td>{{ $enrollment->academicTerm?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th>Grade Level</th>
                <td>{{ $enrollment->gradeLevel?->name }}</td>
                <th>Section</th>
                <td>{{ $enrollment->section?->name ?? '—' }}</td>
            </tr>
        </table>

        <div class="section-title">Requirements</div>
        <table>
            <thead>
            <tr>
                <th>Requirement</th>
                <th>Required</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($enrollment->requirementItems as $item)
                <tr>
                    <td>{{ $item->requirement?->name }}</td>
                    <td>{{ $item->requirement?->is_required ? 'Yes' : 'No' }}</td>
                    <td>{{ $item->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No requirements defined.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="footer">
            <div>Generated {{ now()->format('M d, Y H:i') }}</div>
            <div>KONEXUS Academic Management System</div>
        </div>
    </div>
</body>
</html>