@extends('layouts.app-dashboard')

@section('title', 'Edit MOM')

@section('content')
@include('operation.meeting-minutes._form', [
    'meetingMinute' => $meetingMinute,
    'users' => $users,
    'submitMode' => 'edit',
])
@endsection