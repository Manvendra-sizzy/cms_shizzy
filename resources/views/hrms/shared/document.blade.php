<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        @page { margin: 120px 48px 80px 48px; }
        body { font-family: 'Poppins', sans-serif; line-height: 1.55; color: #0b1020; font-size: 12.5px; }
        .muted { color: #445; }
        h1 { margin: 0 0 6px; font-size: 16px; }
        .box { border: 1px solid #eee; padding: 18px; border-radius: 10px; }
        .meta { display:flex; gap: 8px; align-items: baseline; flex-wrap: wrap; }
        .header { position: fixed; top: -92px; left: 0; right: 0; height: 92px; }
        .footer { position: fixed; bottom: -58px; left: 0; right: 0; height: 58px; }
        .header-inner { border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .row { display:flex; align-items:flex-start; justify-content:space-between; gap: 14px; }
        .logo { width: 120px; height: auto; }
        .company { font-size: 11px; line-height: 1.45; text-align: right; }
        .company strong { font-size: 12px; }
        .doc-type { font-weight: 700; font-size: 13px; margin-top: 2px; }
        .doc-top { margin-top: 6px; }
        .footer-line { border-top:1px solid #eee; padding-top:10px; text-align:center; }
        .footer-hash { margin-top: 6px; font-size: 10.5px; letter-spacing: .6px; text-align:center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-inner">
            <div class="row">
                <div>
                    <img class="logo" src="https://shizzy.in/images/shizzy-logo-red.png" alt="Shizzy logo">
                </div>
                <div class="company muted">
                    <strong>EXIN INTERNET SERVICES PRIVATE LIMITED</strong><br>
                    Spaze Edge, Sector 47, Gurugram, Haryana, 122018<br>
                    www.shizzy.in · office@shizzy.in
                </div>
            </div>
        </div>
    </div>

    <div class="footer muted">
        <div class="footer-line">EXIN INTERNET SERVICES PRIVATE LIMITED</div>
        @if(!empty($document->document_hash))
            <div class="footer-hash">DOC HASH: <strong>{{ $document->document_hash }}</strong></div>
        @endif
    </div>

    <div class="doc-top">
        <div class="meta muted">
            <div class="doc-type">{{ str_replace('_', ' ', ucwords($document->type, '_')) }}</div>
            <div><strong>{{ optional($document->issued_at)->format('Y-m-d') ?? '—' }}</strong></div>
        </div>
    </div>
    <h1 style="margin-top:10px;"><strong>{{ $document->title }}</strong></h1>
    <div class="box">
        {!! nl2br(e($document->body ?? '')) !!}
    </div>
</body>
</html>

