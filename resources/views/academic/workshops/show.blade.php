@extends('layouts.app-dashboard')

@section('title', 'Workshop Detail')

@section('content')
@php
    $priceText = 'Rp ' . number_format((float) $workshop->price, 0, ',', '.');
    $oldPriceText = $workshop->old_price ? 'Rp ' . number_format((float) $workshop->old_price, 0, ',', '.') : null;
    $reviewText = number_format((int) $workshop->rating_count, 0, ',', '.');
@endphp

<div class="container-fluid px-4 py-4 workshops-show-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">{{ $workshop->title }}</h1>
                <p class="page-subtitle mb-0">
                    Lihat detail lengkap workshop, preview media, pricing, benefit, dan status publikasinya.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.workshops.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                @if (Route::has('workshop.show'))
                    <a href="{{ route('workshop.show', $workshop->slug) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Public Page
                    </a>
                @endif

                <a href="{{ route('academic.workshops.edit', $workshop) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> Edit Workshop
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="stat-title">Current Price</div>
                        <div class="stat-value">{{ $priceText }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    @if ($oldPriceText)
                        Old price: {{ $oldPriceText }}
                    @else
                        Tidak ada harga coret untuk workshop ini.
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Rating</div>
                        <div class="stat-value">{{ $workshop->rating }}/5</div>
                    </div>
                </div>
                <div class="stat-description">
                    Berdasarkan {{ $reviewText }} review.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Benefits</div>
                        <div class="stat-value">{{ $workshop->benefits->count() }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jumlah poin yang tampil di bagian “Yang akan dipelajari”.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-on"></i>
                    </div>
                    <div>
                        <div class="stat-title">Status</div>
                        <div class="stat-value">{{ $workshop->is_active ? 'Active' : 'Inactive' }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Sort order: {{ $workshop->sort_order }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Workshop Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan utama yang akan dibaca user sebelum memutuskan ikut workshop.
                        </p>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="workshop-copy-block">
                        <div class="workshop-copy-label">Short Description</div>
                        <p class="mb-4 text-muted">{{ $workshop->short_description ?: '-' }}</p>

                        <div class="workshop-copy-label">Overview</div>
                        <p class="mb-0 text-muted">{{ $workshop->overview ?: '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Intro Video Preview</h5>
                        <p class="content-card-subtitle mb-0">
                            Preview media yang dipakai pada halaman detail workshop.
                        </p>
                    </div>
                </div>
                <div class="content-card-body">
                    @if ($workshop->intro_video_url)
                        <div class="admin-workshop-video-wrap">
                            @if ($workshop->intro_video_type === 'youtube')
                                <iframe
                                    src="{{ $workshop->intro_video_url }}"
                                    title="{{ $workshop->title }} Intro Video"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                ></iframe>
                            @else
                                <video controls playsinline preload="metadata">
                                    <source src="{{ $workshop->intro_video_url }}" type="video/mp4">
                                    Browser tidak mendukung video preview.
                                </video>
                            @endif
                        </div>
                    @else
                        <div class="empty-inline-state">
                            Belum ada intro video untuk workshop ini.
                        </div>
                    @endif
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Benefits</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar benefit pembelajaran yang akan tampil ke user.
                        </p>
                    </div>
                </div>
                <div class="content-card-body">
                    @if ($workshop->benefits->isNotEmpty())
                        <div class="d-grid gap-3">
                            @foreach ($workshop->benefits as $benefit)
                                <div class="benefit-display-item">
                                    <div class="benefit-display-icon">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <div>{{ $benefit->content }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-inline-state">
                            Belum ada benefit untuk workshop ini.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="workshop-show-sticky">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Workshop Summary</h5>
                            <p class="content-card-subtitle mb-0">
                                Ringkasan cepat untuk pricing, kategori, dan target workshop.
                            </p>
                        </div>
                    </div>
                    <div class="content-card-body">
                        <div class="workshop-summary-price-box">
                            @if ($oldPriceText)
                                <div class="workshop-summary-old-price">{{ $oldPriceText }}</div>
                            @endif
                            <div class="workshop-summary-current-price">{{ $priceText }}</div>
                        </div>

                        <div class="workshop-summary-grid">
                            <div class="workshop-summary-item">
                                <span>Badge</span>
                                <strong>{{ $workshop->badge ?: '-' }}</strong>
                            </div>
                            <div class="workshop-summary-item">
                                <span>Category</span>
                                <strong>{{ $workshop->category ?: '-' }}</strong>
                            </div>
                            <div class="workshop-summary-item">
                                <span>Level</span>
                                <strong>{{ $workshop->level ?: '-' }}</strong>
                            </div>
                            <div class="workshop-summary-item">
                                <span>Duration</span>
                                <strong>{{ $workshop->duration ?: '-' }}</strong>
                            </div>
                            <div class="workshop-summary-item">
                                <span>Video Type</span>
                                <strong>{{ $workshop->intro_video_type ?: '-' }}</strong>
                            </div>
                            <div class="workshop-summary-item">
                                <span>Status</span>
                                <strong>{{ $workshop->is_active ? 'Active' : 'Inactive' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Audience</h5>
                            <p class="content-card-subtitle mb-0">
                                Target peserta workshop yang ditampilkan di halaman public.
                            </p>
                        </div>
                    </div>
                    <div class="content-card-body">
                        <p class="mb-0 text-muted">{{ $workshop->audience ?: '-' }}</p>
                    </div>
                </div>

                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Image Preview</h5>
                            <p class="content-card-subtitle mb-0">
                                Thumbnail utama workshop saat ini.
                            </p>
                        </div>
                    </div>
                    <div class="content-card-body">
                        <div class="workshop-preview-image">
                            <img
                                src="{{ $workshop->image ? asset($workshop->image) : asset('images/hero.png') }}"
                                alt="{{ $workshop->title }}"
                                onerror="this.src='{{ asset('images/hero.png') }}'"
                            >
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Technical Info</h5>
                            <p class="content-card-subtitle mb-0">
                                Data teknis untuk kebutuhan admin.
                            </p>
                        </div>
                    </div>
                    <div class="content-card-body">
                        <div class="simple-list">
                            <div class="simple-list-item">
                                <div class="simple-list-title">Slug</div>
                                <div class="simple-list-meta">{{ $workshop->slug }}</div>
                            </div>

                            <div class="simple-list-item">
                                <div class="simple-list-title">Image Path</div>
                                <div class="simple-list-meta text-break">{{ $workshop->image ?: '-' }}</div>
                            </div>

                            <div class="simple-list-item">
                                <div class="simple-list-title">Intro Video URL</div>
                                <div class="simple-list-meta text-break">{{ $workshop->intro_video_url ?: '-' }}</div>
                            </div>

                            <div class="simple-list-item">
                                <div class="simple-list-title">Created At</div>
                                <div class="simple-list-meta">{{ optional($workshop->created_at)->format('d M Y H:i') ?? '-' }}</div>
                            </div>

                            <div class="simple-list-item">
                                <div class="simple-list-title">Updated At</div>
                                <div class="simple-list-meta">{{ optional($workshop->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush