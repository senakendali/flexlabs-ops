@extends('layouts.public')

@section('title', 'Workshop | FlexLabs')
@section('meta_description', 'Workshop berbayar FlexLabs untuk skill digital yang praktis, relevan, dan langsung bisa diterapkan.')
@section('brand_url', url('/workshop'))

@section('content')
<section class="hero-section">
    <div class="container">
        <div class="row g-4 hero-row">
            <div class="col-lg-7">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        Paid Workshop FlexLabs
                    </span>

                    <h1 class="hero-title">
                        Upgrade skill digitalmu
                        <span class="highlight">lewat workshop praktis</span>
                        bareng FlexLabs
                    </h1>

                    <p class="hero-desc">
                        Ikuti workshop FlexLabs untuk belajar skill yang langsung bisa dipakai:
                        praktis, fokus, dan relevan dengan kebutuhan kerja maupun project nyata.
                        Cocok buat pemula, freelancer, content creator, sampai profesional yang ingin upgrade cepat.
                    </p>

                    <div class="hero-mobile-image-wrap d-lg-none">
                        <img
                            src="{{ asset('images/hero.png') }}"
                            alt="Workshop FlexLabs"
                            class="hero-image hero-image-mobile"
                        >
                    </div>

                   

                    <div class="hero-cta-wrap d-flex flex-wrap gap-3 mt-4">
                        <a href="#workshop-list" class="btn btn-brand btn-lg">
                            Lihat Workshop
                        </a>
                       
                    </div>
                </div>
            </div>

            <div class="col-lg-5 hero-visual-col d-none d-lg-flex">
                <div class="hero-visual">
                    <img
                        src="{{ asset('images/hero.png') }}"
                        alt="Workshop FlexLabs"
                        class="hero-image hero-image-desktop"
                    >
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-highlight-section">
    <div class="container-fluid px-0">
        <div class="stats-highlight-full">
            <div class="row g-0">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-mortarboard"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Beginner-Friendly
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-tools"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Hands-On Learning
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-compass"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Industry-Focused
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-highlight-item stats-highlight-item-last">
                        <div class="stats-highlight-head">
                            <div class="stats-highlight-icon">
                                <i class="bi bi-kanban"></i>
                            </div>
                            <div class="stats-highlight-title">
                                Engaging Projects
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="about-section" id="about-workshop">
    <div class="about-full">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <span class="section-label">Why Workshop</span>
                    <h2 class="section-title-large mt-3">
                        Belajar cepat, fokus, dan langsung bisa dipakai
                    </h2>
                </div>

                <div class="col-lg-7">
                    <p class="about-main-text">
                        Workshop FlexLabs dirancang untuk peserta yang ingin belajar lebih singkat tetapi tetap
                        padat manfaat. Setiap workshop fokus pada satu topik yang jelas, dengan pendekatan
                        praktik dan hasil yang bisa langsung dirasakan.
                    </p>

                    <p class="about-main-text mb-0">
                        Jadi bukan cuma dengar teori, tapi benar-benar mencoba alur kerja, tools, dan output
                        yang biasa dipakai di dunia kerja digital saat ini.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content-section workshop-list-section" id="workshop-list">
    <div class="container">
        <div class="workshop-list-heading text-center">
            <span class="section-label">Workshop List</span>
            <h2 class="section-title mt-3">Pilih workshop yang mau lu ikutin</h2>
            <p class="section-subtitle">
                Klik salah satu workshop untuk lihat detail lengkapnya.
            </p>
        </div>

        <div class="row workshop-grid">
            @foreach ($workshops as $workshop)
                @php
                    $priceText = 'Rp ' . number_format($workshop['price'], 0, ',', '.');
                    $oldPriceText = $workshop['old_price']
                        ? 'Rp ' . number_format($workshop['old_price'], 0, ',', '.')
                        : null;
                @endphp

                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('workshop.show', $workshop['slug']) }}" class="workshop-card-link">
                        <article class="workshop-card">
                            <div class="workshop-card-media">
                                <img
                                    src="{{ asset($workshop['image']) }}"
                                    alt="{{ $workshop['title'] }}"
                                    class="workshop-card-image"
                                    onerror="this.src='{{ asset('images/hero.png') }}'"
                                >
                                <span class="workshop-card-badge">
                                    {{ $workshop['badge'] }}
                                </span>
                            </div>

                            <div class="workshop-card-body">
                                <div class="workshop-meta">
                                    <span>{{ $workshop['level'] }}</span>
                                    <span class="workshop-meta-dot"></span>
                                    <span>{{ $workshop['duration'] }}</span>
                                </div>

                                <h3 class="workshop-card-title">
                                    {{ $workshop['title'] }}
                                </h3>

                                <p class="workshop-card-desc">
                                    {{ $workshop['short_description'] }}
                                </p>

                                <div class="workshop-card-price-wrap">
                                    @if ($oldPriceText)
                                        <span class="workshop-price-old">{{ $oldPriceText }}</span>
                                    @endif

                                    <span class="workshop-price-current">{{ $priceText }}</span>
                                </div>

                                <div class="workshop-rating">
                                    <div class="workshop-stars">
                                        @for ($i = 0; $i < $workshop['rating']; $i++)
                                            <i class="bi bi-star-fill"></i>
                                        @endfor
                                    </div>

                                    <span>({{ number_format($workshop['rating_count'], 0, ',', '.') }})</span>
                                </div>

                                <div class="workshop-card-action">
                                    <span>Lihat detail workshop</span>
                                    <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                        </article>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection