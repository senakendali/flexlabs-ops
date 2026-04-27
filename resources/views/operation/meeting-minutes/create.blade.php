@extends('layouts.app-dashboard')

@section('title', 'Create MOM')

@section('content')
@include('operation.meeting-minutes._form', [
    'meetingMinute' => $meetingMinute,
    'users' => $users,
    'submitMode' => 'create',
])
@endsection