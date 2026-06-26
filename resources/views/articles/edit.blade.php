@extends('layouts.app-dashboard')

@section('title', 'Edit Article')

@section('content')
    @include('articles._form', [
        'submitMode' => 'edit',
        'article' => $article,
        'options' => $options,
        'prefill' => $prefill ?? [],
        'sourceWorkshop' => $sourceWorkshop ?? null,
    ])
@endsection