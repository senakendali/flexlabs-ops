@extends('layouts.app-dashboard')

@section('title', 'Create Marketing Event')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Create Marketing Event</h4>
            <small class="text-muted">Add a new marketing event for tracking participation and performance.</small>
        </div>

        <a href="{{ route('marketing.events.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('marketing.events.store') }}">
        @csrf

        @include('marketing.events._form', [
            'event' => null,
            'users' => $users,
            'formTitle' => 'Event Information',
            'formDescription' => 'Fill in the required information to create a new marketing event.',
            'submitLabel' => 'Save Event',
        ])
    </form>
</div>
@endsection