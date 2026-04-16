<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Signed Employment Contract</title>
    <style>
        @page { margin: 44px 36px 54px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.5; }
        .agreement-doc { font-size: 10.5px; line-height: 1.5; }
        .agreement-doc table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .agreement-doc td, .agreement-doc th { border: 1px solid #d1d5db; padding: 4px 6px; }
        .section { margin-top: 14px; }
        .box { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; margin-top: 8px; }
        .label { color: #6b7280; font-size: 10px; }
        .hash { font-family: DejaVu Sans Mono, monospace; font-size: 9px; word-break: break-all; }
        .img-sign { width: 260px; height: 80px; object-fit: contain; border: 1px solid #cbd5e1; }
        .img-selfie { width: 140px; height: 140px; object-fit: cover; border: 1px solid #cbd5e1; }
    </style>
</head>
<body>
<div class="agreement-doc">{!! $agreementBodyHtml !!}</div>

<div class="section">
    <h3>Employee e-signature record</h3>
    <div class="box">
        <div><span class="label">Signer name:</span> {{ $evidence['signer_name'] ?? 'n/a' }}</div>
        <div><span class="label">Signed at:</span> {{ $evidence['signed_at'] ?? 'n/a' }}</div>
        <div><span class="label">IP address:</span> {{ $evidence['ip'] ?? 'n/a' }}</div>
        <div><span class="label">Device fingerprint:</span> {{ $evidence['device_fingerprint'] ?? 'n/a' }}</div>
        <div style="margin-top:8px;"><span class="label">Signature:</span></div>
        <img src="{{ $signatureDataUri }}" class="img-sign" alt="Signature">
    </div>
</div>

<div class="section">
    <h3>Selfie verification snapshot</h3>
    <div class="box">
        <img src="{{ $selfieDataUri }}" class="img-selfie" alt="Selfie">
    </div>
</div>

<div class="section">
    <h3>Cryptographic evidence</h3>
    <div class="box">
        <div><span class="label">Contract content hash (SHA-256):</span></div>
        <div class="hash">{{ $evidence['document_hash'] ?? '' }}</div>
        <div style="margin-top:8px;"><span class="label">Signature image hash (SHA-256):</span></div>
        <div class="hash">{{ $evidence['signature_hash'] ?? '' }}</div>
        <div style="margin-top:8px;"><span class="label">Selfie image hash (SHA-256):</span></div>
        <div class="hash">{{ $evidence['selfie_hash'] ?? '' }}</div>
    </div>
</div>
</body>
</html>
