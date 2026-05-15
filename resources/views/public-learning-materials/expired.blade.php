@extends('layouts.public')

@section('title', 'Material Expired | FlexLabs')
@section('meta_description', 'Link materi FlexLabs sudah melewati batas waktu akses.')
@section('brand_url', url('/workshop'))

@section('content')
@php
    $expiredAt = $material->access_ends_at
        ? $material->access_ends_at->format('d M Y H:i')
        : '-';

    $eventDate = $material->event_date
        ? $material->event_date->format('d M Y')
        : '-';

    $schedule = ($material->starts_at && $material->ends_at)
        ? $material->starts_at->format('H:i') . ' - ' . $material->ends_at->format('H:i')
        : '-';

    $coverImage = $material->cover_image_path
        ? asset('storage/' . $material->cover_image_path)
        : asset('images/hero.png');
@endphp

<section class="hero-section">
    <div class="container">
        <div class="row g-4 hero-row align-items-center">
            <div class="col-lg-7">
                <div class="hero-content">
                    <span class="hero-badge">
                        <i class="bi bi-lock"></i>
                        Access Expired
                    </span>

                    <h1 class="display-4 fw-bold lh-1 mt-4 mb-3">
                        Materi sudah tidak tersedia
                    </h1>

                    <p class="fs-3 fw-bold text-primary mb-3">
                        {{ $material->title }}
                    </p>

                    <div class="hero-desc mb-0">
                        Link materi ini sudah melewati batas waktu akses. Kalau teman-teman masih perlu akses ulang,
                        silakan hubungi tim FlexLabs atau instructor terkait.
                    </div>

                    <div class="hero-mobile-image-wrap d-lg-none mt-4">
                        <img
                            src="{{ $coverImage }}"
                            alt="{{ $material->title }}"
                            class="hero-image hero-image-mobile"
                        >
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <span class="hero-badge">
                            <i class="bi bi-hourglass-bottom"></i>
                            Berakhir: {{ $expiredAt }}
                        </span>

                        <span class="hero-badge">
                            <i class="bi bi-calendar-event"></i>
                            {{ $eventDate }}
                        </span>

                        <span class="hero-badge">
                            <i class="bi bi-clock"></i>
                            {{ $schedule }}
                        </span>
                    </div>

                    
                </div>
            </div>

            <div class="col-lg-5 hero-visual-col d-none d-lg-flex">
                <div class="hero-visual">
                    <img
                        src="{{ $coverImage }}"
                        alt="{{ $material->title }}"
                        class="hero-image hero-image-desktop"
                    >
                </div>
            </div>
        </div>
    </div>
</section>
@endsection