@extends('layouts.public')

@section('title', 'Material Locked | FlexLabs')
@section('meta_description', 'Materi FlexLabs belum dibuka sesuai jadwal akses.')
@section('brand_url', url('/workshop'))

@section('content')
@php
    $accessStartsAt = $material->access_starts_at
        ? $material->access_starts_at->format('d M Y H:i')
        : '-';

    $schedule = ($material->starts_at && $material->ends_at)
        ? $material->starts_at->format('H:i') . ' - ' . $material->ends_at->format('H:i')
        : '-';

    $eventDate = $material->event_date
        ? $material->event_date->format('d M Y')
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
                        <i class="bi bi-clock-history"></i>
                        Access Locked
                    </span>

                    <h1 class="display-4 fw-bold lh-1 mt-4 mb-3">
                        Materi belum dibuka
                    </h1>

                    <p class="fs-3 fw-bold text-primary mb-3">
                        {{ $material->title }}
                    </p>

                    <div class="hero-desc mb-0">
                        Materi ini belum bisa diakses saat ini. Silakan buka kembali sesuai jadwal
                        yang sudah ditentukan oleh tim FlexLabs.
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
                            <i class="bi bi-unlock"></i>
                            Dibuka: {{ $accessStartsAt }}
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