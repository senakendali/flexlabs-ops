@extends('layouts.public')

@section('title', $workshop['title'] . ' | Workshop FlexLabs')
@section('meta_description', $workshop['short_description'])
@section('brand_url', url('/worokshop'))

@section('content')
@php
    $priceText = 'Rp ' . number_format($workshop['price'], 0, ',', '.');
    $oldPriceText = $workshop['old_price']
        ? 'Rp ' . number_format($workshop['old_price'], 0, ',', '.')
        : null;

    $waText = rawurlencode('Halo FlexLabs, saya ingin daftar workshop: ' . $workshop['title']);
    $waUrl = 'https://wa.me/62811134759?text=' . $waText;
@endphp

<section class="workshop-show-hero">
    <div class="container">
        <a href="{{ route('workshop.index') }}" class="workshop-show-back">
            <i class="bi bi-arrow-left"></i>
            Kembali ke daftar workshop
        </a>

        <div class="workshop-show-main">
            <span class="workshop-show-badge">{{ $workshop['badge'] }}</span>

            <h1 class="workshop-show-title">
                {{ $workshop['title'] }}
            </h1>

            <p class="workshop-show-desc">
                {{ $workshop['overview'] }}
            </p>

            <div class="workshop-show-meta">
                <span><i class="bi bi-star-fill"></i> {{ $workshop['rating'] }}/5</span>
                <span>({{ number_format($workshop['rating_count'], 0, ',', '.') }})</span>
                <span><i class="bi bi-clock"></i> {{ $workshop['duration'] }}</span>
                <span><i class="bi bi-bar-chart"></i> {{ $workshop['level'] }}</span>
                <span><i class="bi bi-grid"></i> {{ $workshop['category'] }}</span>
            </div>

            <div class="workshop-video-card">
                <div class="workshop-video-wrap">
                    @if (($workshop['intro_video_type'] ?? 'youtube') === 'youtube')
                        <iframe
                            src="{{ $workshop['intro_video_url'] }}"
                            title="{{ $workshop['title'] }} Intro Video"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        ></iframe>
                    @else
                        <video controls playsinline preload="metadata">
                            <source src="{{ $workshop['intro_video_url'] }}" type="video/mp4">
                            Browser lu belum support video.
                        </video>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content-section pt-4">
    <div class="container">
        <div class="row g-4 workshop-show-layout">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-card-header">
                        <span class="section-label">What You Will Learn</span>
                        <h2 class="section-title mt-3">Yang akan dipelajari</h2>
                        <p class="section-subtitle">
                            Workshop ini dibuat praktis, fokus, dan langsung bisa dipakai.
                        </p>
                    </div>

                    <div class="section-card-body">
                        <div class="workshop-list">
                            @foreach ($workshop['benefits'] as $benefit)
                                <div class="workshop-list-item">
                                    <span class="workshop-list-icon">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                    <span>{{ $benefit }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="workshop-detail-copy mt-4">
                            <h3 class="workshop-subtitle">Workshop ini cocok untuk</h3>
                            <p class="mb-0">{{ $workshop['audience'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="section-card mt-4">
                    <div class="section-card-header">
                        <span class="section-label">About This Workshop</span>
                        <h2 class="section-title mt-3">Ringkasan workshop</h2>
                    </div>

                    <div class="section-card-body">
                        <p class="about-main-text mb-0">
                            {{ $workshop['short_description'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="workshop-show-sidebar-col">
                    <aside class="workshop-show-sidebar">
                        <div class="workshop-show-sidebar-image">
                            <img
                                src="{{ asset($workshop['image']) }}"
                                alt="{{ $workshop['title'] }}"
                                onerror="this.src='{{ asset('images/hero.png') }}'"
                            >
                        </div>

                        <div class="workshop-show-price-box">
                            @if ($oldPriceText)
                                <div class="workshop-show-old-price">{{ $oldPriceText }}</div>
                            @endif

                            <div class="workshop-show-price">{{ $priceText }}</div>
                        </div>

                        <a href="{{ $waUrl }}" target="_blank" class="btn btn-brand btn-lg w-100 mt-3">
                            Daftar Workshop
                        </a>

                        <a href="{{ route('workshop.index') }}" class="btn btn-soft btn-lg w-100 mt-3">
                            Lihat Workshop Lain
                        </a>

                        <div class="workshop-show-summary">
                            <div class="workshop-show-summary-row">
                                <span>Kategori</span>
                                <strong>{{ $workshop['category'] }}</strong>
                            </div>

                            <div class="workshop-show-summary-row">
                                <span>Level</span>
                                <strong>{{ $workshop['level'] }}</strong>
                            </div>

                            <div class="workshop-show-summary-row">
                                <span>Durasi</span>
                                <strong>{{ $workshop['duration'] }}</strong>
                            </div>

                            <div class="workshop-show-summary-row">
                                <span>Rating</span>
                                <strong>{{ $workshop['rating'] }}/5</strong>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection