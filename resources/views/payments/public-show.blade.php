@extends('layouts.public')

@section('title', 'Payment Invoice')

@section('content')
@php
    $invoiceDate = $payment->payment_date ?: $payment->created_at;
    $canPay = !$isPaid && !$isExpired && !empty($payment->payment_url);
    $invoicePdfFilename = Str::slug(($payment->invoice_number ?: 'invoice-' . $payment->id)) . '.pdf';
    $companyAddressLines = [
        'MyRepublic Plaza Wing B 2nd Floor',
        'Jl. BSD Grand Boulevard',
        'BSD Green Office Park BSD City',
        'Desa Sampora, Kec. Cisauk',
        'Tangerang 15345',
    ];

    $subtotal = (float) ($payment->amount ?? 0);
    $tax = 0;
    $grandTotal = $subtotal + $tax;

    $itemTitle = $schedule?->title ?: 'Program Payment';
    $itemSubtitleParts = array_values(array_filter([
        $program?->name,
        $batch?->name,
    ]));
    $itemSubtitle = implode(' / ', $itemSubtitleParts);
    $paymentMethod = $payment->payment_method
        ?: ($payment->gateway_provider ? ucfirst($payment->gateway_provider) : 'Payment Link');
@endphp

<div class="container py-5 public-payment-wrapper">
    @if ($isPaid)
        <div class="alert alert-success public-payment-alert mb-4">
            <div class="public-payment-alert-title">
                <i class="bi bi-check-circle-fill me-1"></i>
                Payment completed
            </div>
            <div class="public-payment-alert-text">
                Pembayaran untuk invoice ini sudah berhasil diterima.
            </div>
        </div>
    @elseif ($isExpired)
        <div class="alert alert-warning public-payment-alert mb-4">
            <div class="public-payment-alert-title">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Payment link expired
            </div>
            <div class="public-payment-alert-text">
                Link pembayaran sudah tidak aktif. Silakan hubungi admin FlexLabs untuk mendapatkan link pembayaran baru.
            </div>
        </div>
    @elseif (!$payment->payment_url)
        <div class="alert alert-secondary public-payment-alert mb-4">
            <div class="public-payment-alert-title">
                <i class="bi bi-clock-history me-1"></i>
                Payment link belum tersedia
            </div>
            <div class="public-payment-alert-text">
                Link pembayaran untuk invoice ini belum aktif. Silakan hubungi admin FlexLabs.
            </div>
        </div>
    @endif

    <div class="invoice-shell">
        <div class="invoice-toolbar no-print">
            <div>
                <div class="public-payment-eyebrow">FlexLabs Payment</div>
                <h1 class="public-payment-heading">Student Payment Invoice</h1>
                <p class="public-payment-subtitle mb-0">
                    Review invoice ini sebelum melanjutkan pembayaran.
                </p>
            </div>

            <div class="public-payment-actions">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    id="downloadInvoicePdfBtn"
                    data-pdf-target="#publicInvoiceDocument"
                    data-pdf-filename="{{ $invoicePdfFilename }}"
                >
                    <i class="bi bi-download me-1"></i>
                    Download PDF
                </button>

                @if ($isPaid)
                    <button type="button" class="btn btn-success" disabled>
                        <i class="bi bi-check-circle me-1"></i>
                        Already Paid
                    </button>
                @elseif ($isExpired)
                    <button type="button" class="btn btn-secondary" disabled>
                        <i class="bi bi-x-circle me-1"></i>
                        Link Expired
                    </button>
                @elseif ($canPay)
                    <a
                        href="{{ $payment->payment_url }}"
                        rel="noopener noreferrer"
                        class="btn btn-brand"
                    >
                        <i class="bi bi-credit-card me-1"></i>
                        Pay Now
                    </a>
                @else
                    <button type="button" class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-clock-history me-1"></i>
                        Payment Link Not Ready
                    </button>
                @endif
            </div>
        </div>

        <div class="invoice-page">
            <article class="invoice-card" id="publicInvoiceDocument">
                <div class="invoice-content">
                    <header class="invoice-header">
                        <div class="invoice-logo-wrap">
                            <img
                                src="{{ asset('images/logo-black.png') }}"
                                alt="FlexLabs Logo"
                                class="invoice-logo"
                            >
                        </div>

                        <div class="invoice-number-box">
                            <span class="invoice-number-label">Invoice No.</span>
                            <span>:</span>
                            <span class="invoice-number-value">{{ $payment->invoice_number ?: '-' }}</span>
                        </div>
                    </header>

                    <main>
                        <h1 class="invoice-title">INVOICE</h1>

                        <div class="invoice-date-line">
                            <div class="invoice-info-line">
                                <div class="invoice-info-label">Date</div>
                                <div class="invoice-info-colon">:</div>
                                <div class="invoice-info-value">{{ $invoiceDate?->format('d F Y') ?? '-' }}</div>
                            </div>
                        </div>

                        <section class="invoice-parties">
                            <div class="invoice-party-card">
                                <h2>Billed to</h2>
                                <div class="invoice-party-name">{{ $student->full_name ?? '-' }}</div>

                                @if (!empty($student?->email))
                                    <div>{{ $student->email }}</div>
                                @endif

                                @if (!empty($student?->phone))
                                    <div>{{ $student->phone }}</div>
                                @endif

                                @if (!empty($student?->city))
                                    <div>{{ $student->city }}</div>
                                @endif
                            </div>

                            <div class="invoice-party-card">
                                <h2>From</h2>
                                <div class="invoice-party-name">FlexLabs</div>
                                <div class="invoice-company-address">
                                    @foreach ($companyAddressLines as $addressLine)
                                        <div>{{ $addressLine }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </section>

                        <section class="invoice-table-section">
                            <div class="table-responsive invoice-table-wrap">
                                <table class="table invoice-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-center invoice-table-qty">Qty</th>
                                            <th class="text-end invoice-table-price">Rate/Unit</th>
                                            <th class="text-end invoice-table-amount">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="invoice-item-title">{{ $itemTitle }}</div>
                                                @if ($itemSubtitle !== '')
                                                    <div class="invoice-item-subtitle">{{ $itemSubtitle }}</div>
                                                @endif
                                            </td>
                                            <td class="text-center">1</td>
                                            <td class="text-end">Rp {{ number_format((float) $subtotal, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format((float) $subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="invoice-summary-wrap">
                            <table class="invoice-summary-table">
                                <tr>
                                    <td>Sub Total</td>
                                    <td>Rp {{ number_format((float) $subtotal, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Tax</td>
                                    <td>Rp {{ number_format((float) $tax, 0, ',', '.') }}</td>
                                </tr>
                                <tr class="invoice-summary-total">
                                    <td>Total</td>
                                    <td>Rp {{ number_format((float) $grandTotal, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </section>

                        <section class="invoice-payment-section">
                            <div class="invoice-info-line">
                                <div class="invoice-info-label">Payment method</div>
                                <div class="invoice-info-colon">:</div>
                                <div class="invoice-info-value">{{ $paymentMethod }}</div>
                            </div>

                            @if (!empty($payment->reference_number))
                                <div class="invoice-info-line">
                                    <div class="invoice-info-label">Reference no</div>
                                    <div class="invoice-info-colon">:</div>
                                    <div class="invoice-info-value">{{ $payment->reference_number }}</div>
                                </div>
                            @endif

                            <div class="invoice-info-line">
                                <div class="invoice-info-label">Note</div>
                                <div class="invoice-info-colon">:</div>
                                <div class="invoice-info-value">
                                    {{ $payment->notes ?: 'Please complete your payment using the Pay Now button above.' }}
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </article>
        </div>

        <div class="public-payment-bottom-action no-print">
            @if ($isPaid)
                <button type="button" class="btn btn-success btn-lg px-5" disabled>
                    <i class="bi bi-check-circle me-1"></i>
                    Already Paid
                </button>
            @elseif ($isExpired)
                <button type="button" class="btn btn-secondary btn-lg px-5" disabled>
                    <i class="bi bi-x-circle me-1"></i>
                    Link Expired
                </button>
            @elseif ($canPay)
                <a
                    href="{{ $payment->payment_url }}"
                    rel="noopener noreferrer"
                    class="btn btn-brand btn-lg px-5"
                >
                    <i class="bi bi-credit-card me-1"></i>
                    Pay Now
                </a>
            @else
                <button type="button" class="btn btn-outline-secondary btn-lg px-5" disabled>
                    <i class="bi bi-clock-history me-1"></i>
                    Payment Link Not Ready
                </button>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const downloadButton = document.getElementById('downloadInvoicePdfBtn');

        if (!downloadButton) {
            return;
        }

        downloadButton.addEventListener('click', async function () {
            const targetSelector = downloadButton.dataset.pdfTarget;
            const filename = downloadButton.dataset.pdfFilename || 'invoice.pdf';
            const target = document.querySelector(targetSelector);

            if (!target) {
                return;
            }

            if (!window.html2canvas || !window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library belum siap. Silakan refresh halaman lalu coba lagi.');
                return;
            }

            const originalText = downloadButton.innerHTML;
            downloadButton.disabled = true;
            downloadButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Preparing PDF...';
            target.classList.add('invoice-exporting');

            try {
                const canvas = await window.html2canvas(target, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: document.documentElement.offsetWidth,
                    windowHeight: document.documentElement.offsetHeight,
                });

                const imageData = canvas.toDataURL('image/png');
                const pdf = new window.jspdf.jsPDF('p', 'mm', 'a4');
                const a4Width = 210;
                const a4Height = 297;

                pdf.addImage(
                    imageData,
                    'PNG',
                    0,
                    0,
                    a4Width,
                    a4Height,
                    undefined,
                    'FAST'
                );

                pdf.save(filename);
            } catch (error) {
                console.error(error);
                alert('PDF gagal dibuat. Silakan coba lagi.');
            } finally {
                target.classList.remove('invoice-exporting');
                downloadButton.disabled = false;
                downloadButton.innerHTML = originalText;
            }
        });
    });
</script>
@endpush
