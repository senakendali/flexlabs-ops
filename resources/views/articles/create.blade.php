@extends('layouts.app-dashboard')

@section('title', 'Create Article')

@section('content')
    @include('articles._form', [
        'submitMode' => 'create',
        'article' => $article,
        'options' => $options,
        'prefill' => $prefill ?? [],
        'sourceWorkshop' => $sourceWorkshop ?? null,
    ])
@endsection