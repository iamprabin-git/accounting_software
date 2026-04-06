<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Trail Export</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; font-size: 12px; margin: 20px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; text-align: left; }
        th { background: #f7f7f7; }
        .mono { font-family: Consolas, monospace; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Accounting Audit Trail</h1>
    <div class="meta">
        <div>Company: {{ $company->name }} (#{{ $company->id }})</div>
        <div>Generated: {{ $generatedAt }}</div>
        <div>Filters: {{ json_encode($filters, JSON_UNESCAPED_SLASHES) }}</div>
        <div>Rows: {{ $rows->count() }}</div>
        <div>Use browser Print -> Save as PDF.</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Created At</th>
                <th>Action</th>
                <th>Actor</th>
                <th>IP</th>
                <th>Journal</th>
                <th>Hash</th>
                <th>Prev Hash</th>
                <th>Metadata</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->created_at?->toIso8601String() }}</td>
                    <td>{{ $row->action }}</td>
                    <td>{{ $row->user?->name ?? 'System' }}</td>
                    <td>{{ $row->actor_ip }}</td>
                    <td>{{ $row->journal_entry_id }}</td>
                    <td class="mono">{{ $row->event_hash }}</td>
                    <td class="mono">{{ $row->previous_event_hash }}</td>
                    <td class="mono">{{ json_encode($row->metadata ?? [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
