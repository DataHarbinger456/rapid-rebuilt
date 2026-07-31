<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Case {{ $caseNumber }} — Rapid Rebuilt Tracker</title>
    <style>
        :root {
            --brand: #1d4ed8;
            --ok: #16a34a;
            --muted: #94a3b8;
            --bg: #f1f5f9;
            --card: #ffffff;
            --text: #0f172a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px 16px 48px;
        }
        .wrap { max-width: 480px; margin: 0 auto; }
        .brand {
            text-align: center;
            font-weight: 700;
            font-size: 18px;
            color: var(--brand);
            margin-bottom: 20px;
        }
        .card {
            background: var(--card);
            border-radius: 16px;
            padding: 24px 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            margin-bottom: 16px;
        }
        .case-number { font-size: 13px; color: var(--muted); letter-spacing: 0.05em; text-transform: uppercase; }
        .vehicle { font-size: 22px; font-weight: 700; margin-top: 4px; }
        .status-pill {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            background: {{ $cancelled ? '#fee2e2' : '#dbeafe' }};
            color: {{ $cancelled ? '#b91c1c' : '#1d4ed8' }};
        }
        .steps { list-style: none; }
        .step {
            display: flex;
            gap: 14px;
            position: relative;
            padding-bottom: 24px;
        }
        .step:last-child { padding-bottom: 0; }
        .step::before {
            content: "";
            position: absolute;
            left: 13px;
            top: 28px;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        .step:last-child::before { display: none; }
        .step.done::before { background: var(--ok); }
        .dot {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: var(--muted);
            z-index: 1;
        }
        .step.done .dot {
            border-color: var(--ok);
            background: var(--ok);
            color: #fff;
        }
        .step-label { font-weight: 600; font-size: 15px; padding-top: 3px; }
        .step.pending .step-label { color: var(--muted); font-weight: 500; }
        .step-date { font-size: 13px; color: var(--muted); margin-top: 2px; }
        .footer { text-align: center; font-size: 12px; color: var(--muted); margin-top: 24px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">Rapid Rebuilt</div>

        <div class="card">
            <div class="case-number">Case {{ $caseNumber }}</div>
            @if ($vehicle)
            <div class="vehicle">{{ $vehicle }}</div>
            @endif
            <span class="status-pill">{{ $cancelled ? 'Cancelled' : $statusLabel }}</span>
        </div>

        <div class="card">
            <ul class="steps">
                @foreach ($stages as $stage)
                <li class="step {{ $stage['reached'] ? 'done' : 'pending' }}">
                    <div class="dot">{{ $stage['reached'] ? '✓' : $loop->iteration }}</div>
                    <div>
                        <div class="step-label">{{ $stage['label'] }}</div>
                        @if ($stage['date'])
                        <div class="step-date">{{ $stage['date']->format('M j, Y') }}</div>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="footer">Questions? Contact your Rapid Rebuilt case manager.</div>
    </div>
</body>
</html>
