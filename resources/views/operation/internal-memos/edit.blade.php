@extends('layouts.app-dashboard')

@section('title', 'Edit Internal Memo')

@section('content')
@include('operation.internal-memos._form', [
    'memo' => $memo,
    'users' => $users,
    'acknowledgementDefaults' => $acknowledgementDefaults ?? [],
    'submitMode' => 'edit',
])
@endsection
