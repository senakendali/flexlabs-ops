@php
    $gaConversionFunnel = collect($gaConversionFunnel ?? []);
@endphp

<div class="meta-ads-detail-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="fw-semibold text-dark">Conversion Funnel</div>
            <div class="small text-muted">
                Alur dari traffic masuk sampai action penting seperti klik CTA, submit form, atau payment.
            </div>
        </div>
        <span class="badge rounded-pill bg-success-subtle text-success">
            Funnel
        </span>
    </div>

    @if($gaConversionFunnel->isEmpty())
        <div class="row g-3">
            @foreach([
                ['label' => 'Sessions', 'value' => 0, 'help' => 'Total traffic masuk.'],
                ['label' => 'Engaged Sessions', 'value' => 0, 'help' => 'Traffic yang engage.'],
                ['label' => 'CTA Click', 'value' => 0, 'help' => 'Klik tombol daftar/WhatsApp.'],
                ['label' => 'Form Submit', 'value' => 0, 'help' => 'Submit form lead/event.'],
            ] as $item)
                <div class="col-xl-3 col-md-6">
                    <div class="meta-ads-mini-metric h-100">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ number_format($item['value']) }}</strong>
                        <small class="text-muted">{{ $item['help'] }}</small>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="row g-3">
            @foreach($gaConversionFunnel as $step)
                <div class="col-xl-3 col-md-6">
                    <div class="meta-ads-mini-metric h-100">
                        <span>{{ $step['label'] ?? '-' }}</span>
                        <strong>{{ number_format((int) ($step['value'] ?? 0)) }}</strong>
                        <small class="text-muted">
                            Rate: {{ number_format((float) ($step['rate'] ?? 0), 1) }}%
                        </small>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>