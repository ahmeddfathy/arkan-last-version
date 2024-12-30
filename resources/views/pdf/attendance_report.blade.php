<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الحضور - أركان للاستشارات الاقتصادية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">

    <style>
      @page {
            margin: 20px;
            size: A4 landscape;
        }

        * {
            box-sizing: border-box;
            font-family: "Cairo", "Amiri", "Tajawal", "DejaVu Sans", sans-serif !important;
        }

        body {
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 15px;
            font-size: 13px;
            line-height: 1.4;
        }

        .report-container {
            width: 100%;
        }

        /* Header Styles */
        .company-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-header img {
            width: 100px;
            height: 100px;
        }

        .company-header h1 {
            color: #4AA4E8;
            font-size: 24px;
            margin: 0 0 10px 0;
        }

        .company-header p {
            margin: 5px 0;
        }

        /* Employee Info Styles */
        .employee-info {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .employee-info td {
            padding: 8px;
            border: 1px solid #E5E7EB;
            background: #f8fafc;
        }

        .info-label {
            color: #4AA4E8;
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
        }

        /* Attendance Table Styles */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #E5E7EB;
            padding: 4px;
            text-align: center;
            font-size: 10px;
            white-space: nowrap;
        }

        .attendance-table th {
            background: #4AA4E8;
            color: white;
        }

        .attendance-table tr:nth-child(even) {
            background: #f8fafc;
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        .status-present { background: #10B981; color: white; }
        .status-absent { background: #EF4444; color: white; }
        .status-late { background: #F59E0B; color: white; }

        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
                font-size: 12px;
            }


            .status-badge {
                font-size: 8px;
                padding: 2px 4px;
            }
        }

        /* Responsive styles */
        .table-wrapper {
            overflow-x: auto;
            margin-top: 15px;
        }

        @media screen and (max-width: 1024px) {
            .attendance-table {
                min-width: 1200px;
            }

            .employee-info td {
                display: block;
                width: 100%;
            }
        }
        .attendance-table th,
        .attendance-table td {
            direction: rtl;
            unicode-bidi: bidi-override;
        }

        .status-badge {
            direction: rtl;
            unicode-bidi: bidi-override;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Company Header -->
        <div class="company-header">
            <img src="https://th.bing.com/th/id/OIP.bz3odABZqEOm4oHcNvrL5QHaHa?rs=1&pid=ImgDetMain" alt="Logo">
            <h1>أركان للاستشارات الاقتصادية</h1>
            <p>الرياض، المملكة العربية السعودية</p>
            <p> Email: info@arkan.com</p>
        </div>

        <!-- Employee Info -->
        <table class="employee-info">
            <tr>
                <td width="25%">
                    <span class="info-label">اسم الموظف</span>
                    {{ $user->name }}
                </td>
                <td width="25%">
                    <span class="info-label">الرقم الوظيفي</span>
                    {{ $user->employee_id }}
                </td>
                <td width="25%">
                    <span class="info-label">القسم</span>
                    {{ $user->department }}
                </td>
                <td width="25%">
                    <span class="info-label">فترة التقرير</span>
                    {{ __('December 2024') }}
                </td>
            </tr>
        </table>

        <!-- Attendance Table -->
        <div class="table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>اليوم</th>
                        <th>الحالة</th>
                        <th>الوردية</th>
                        <th>ساعات العمل</th>
                        <th>وقت الحضور</th>
                        <th>وقت الانصراف</th>
                        <th>التأخير (دقيقة)</th>
                        <th>الخروج المبكر (دقيقة)</th>
                        <th>العمل الإضافي (ساعة)</th>
                        <th>الجزاء</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendanceRecords as $record)
                    <tr>
                        <td>{{ $record->attendance_date }}</td>
                        <td>{{ __($record->day) }}</td>
                        <td>
                            <span class="status-badge status-{{ strtolower($record->status) }}">
                                {{ __($record->status) }}
                            </span>
                        </td>
                        <td>{{ $record->shift }}</td>
                        <td>{{ $record->shift_hours }}</td>
                        <td>{{ $record->entry_time ?: '-' }}</td>
                        <td>{{ $record->exit_time ?: '-' }}</td>
                        <td>{{ $record->delay_minutes ?: '-' }}</td>
                        <td>{{ $record->early_minutes ?: '-' }}</td>
                        <td>{{ $record->overtime_hours ?: '-' }}</td>
                        <td>{{ $record->penalty ?: '-' }}</td>
                        <td>{{ $record->notes ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
