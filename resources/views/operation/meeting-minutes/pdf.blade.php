<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meetingMinute->meeting_no }}</title>

    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222222;
            line-height: 1.5;
        }

        .header {
            margin-bottom: 24px;
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .header-left {
            width: 45%;
            text-align: left;
        }

        .header-right {
            width: 55%;
            text-align: right;
        }

        .logo {
            width: 180px;
            height: auto;
        }

        .document-label {
            font-size: 13px;
            font-weight: bold;
            color: #5B3E8E;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #111111;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 11px;
            color: #666666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .section {
            margin-top: 18px;
            page-break-inside: auto;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #5B3E8E;
            margin-bottom: 8px;
            page-break-after: avoid;
        }

        .info-table {
            page-break-inside: avoid;
        }

        .info-table td {
            padding: 7px 8px;
            border: 1px solid #e8e8e8;
            vertical-align: top;
        }

        .info-label {
            width: 28%;
            background: #FAF8FF;
            font-weight: bold;
            color: #555555;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tbody {
            display: table-row-group;
        }

        .data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .data-table th {
            background: #5B3E8E;
            color: #ffffff;
            padding: 7px 8px;
            border: 1px solid #5B3E8E;
            text-align: left;
            font-size: 10px;
        }

        .data-table td {
            padding: 7px 8px;
            border: 1px solid #e8e8e8;
            vertical-align: top;
            font-size: 10px;
        }

        .text-box {
            border: 1px solid #e8e8e8;
            background: #FAF8FF;
            padding: 10px;
            min-height: 42px;
            white-space: pre-line;
            page-break-inside: avoid;
        }

        .muted {
            color: #777777;
        }

        .action-items-section {
            page-break-inside: auto;
        }

        .action-items-section .section-title {
            page-break-after: avoid;
        }

        .footer {
            margin-top: 26px;
            padding-top: 10px;
            font-size: 10px;
            color: #777777;
        }

        .signature-wrap {
            margin-top: 28px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 34%;
            text-align: center;
            float: right;
        }

        .signature-space {
            height: 58px;
        }

        .clear {
            clear: both;
        }
    </style>
</head>

<body>
    @php
        $meetingDate = $meetingMinute->meeting_date
            ? \Illuminate\Support\Carbon::parse($meetingMinute->meeting_date)->format('d M Y')
            : '-';

        $startTime = $meetingMinute->start_time
            ? substr((string) $meetingMinute->start_time, 0, 5)
            : '-';

        $endTime = $meetingMinute->end_time
            ? substr((string) $meetingMinute->end_time, 0, 5)
            : '-';

        $logoPath = public_path('assets/images/logo-black.png');

        if (! file_exists($logoPath)) {
            $logoPath = public_path('images/logo-black.png');
        }

        if (! file_exists($logoPath)) {
            $logoPath = public_path('logo-black.png');
        }
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if(file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="logo" alt="Logo">
                    @endif
                </td>

                <td class="header-right">
                    <div class="document-label">Minutes of Meeting</div>
                    <div class="title">{{ $meetingMinute->title }}</div>
                    <div class="subtitle">
                        {{ $meetingMinute->meeting_no }} - {{ $meetingDate }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Meeting Information</div>

        <table class="info-table">
            <tr>
                <td class="info-label">Meeting Title</td>
                <td>{{ $meetingMinute->title }}</td>
            </tr>
            <tr>
                <td class="info-label">Meeting No</td>
                <td>{{ $meetingMinute->meeting_no }}</td>
            </tr>
            <tr>
                <td class="info-label">Date</td>
                <td>{{ $meetingDate }}</td>
            </tr>
            <tr>
                <td class="info-label">Time</td>
                <td>{{ $startTime }} - {{ $endTime }}</td>
            </tr>
            <tr>
                <td class="info-label">Organizer</td>
                <td>{{ $meetingMinute->organizer?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Department</td>
                <td>{{ $meetingMinute->department ? ucwords(str_replace('_', ' ', $meetingMinute->department)) : '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Location</td>
                <td>{{ $meetingMinute->location ?: '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Platform</td>
                <td>{{ $meetingMinute->platform ?: '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Related Project</td>
                <td>{{ $meetingMinute->related_project ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Agenda</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th style="width: 32%;">Topic</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($meetingMinute->agendas as $agenda)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $agenda->topic }}</td>
                        <td>{{ $agenda->description ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="muted">No agenda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Participants</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th style="width: 46%;">Name</th>
                    <th style="width: 46%;">Email</th>
                </tr>
            </thead>
            <tbody>
                @forelse($meetingMinute->participants as $participant)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $participant->display_name }}</td>
                        <td>{{ $participant->display_email ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="muted">No participants.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Summary</div>
        <div class="text-box">{{ $meetingMinute->summary ?: '-' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Notes</div>
        <div class="text-box">{{ $meetingMinute->notes ?: '-' }}</div>
    </div>

    <div class="section action-items-section">
        <div class="section-title">Action Items</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 28%;">Task</th>
                    <th style="width: 18%;">PIC</th>
                    <th style="width: 14%;">Priority</th>
                    <th style="width: 15%;">Due Date</th>
                
                    <th style="width: 10%;">Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($meetingMinute->actionItems as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->title }}</strong>
                            @if($item->description)
                                <br>
                                <span class="muted">{{ $item->description }}</span>
                            @endif
                        </td>
                        <td>{{ $item->pic_display_name }}</td>
                        <td>{{ ucwords($item->priority ?? 'medium') }}</td>
                        <td>
                            {{ $item->due_date ? \Illuminate\Support\Carbon::parse($item->due_date)->format('d M Y') : '-' }}
                        </td>
                        
                        <td>{{ $item->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No action items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="signature-wrap">
        <div class="signature-box">
            <div>Prepared by,</div>
            <div class="signature-space"></div>
            <strong>{{ $meetingMinute->creator?->name ?? auth()->user()?->name ?? 'FlexOps' }}</strong>
        </div>
        <div class="clear"></div>
    </div>

    
</body>
</html>