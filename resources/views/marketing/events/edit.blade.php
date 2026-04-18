@extends('layouts.app-dashboard')

@section('title', 'Edit Marketing Event')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Edit Marketing Event</h4>
            <small class="text-muted">Update event information, participation numbers, and outcome metrics.</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('marketing.events.show', $event) }}" class="btn btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View Detail
            </a>

            <a href="{{ route('marketing.events.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('marketing.events.update', $event) }}">
        @csrf
        @method('PUT')

        @include('marketing.events._form', [
            'event' => $event,
            'users' => $users,
            'formTitle' => 'Event Information',
            'formDescription' => 'Update the required information for this marketing event.',
            'submitLabel' => 'Update Event',
        ])
    </form>
</div>
@endsection