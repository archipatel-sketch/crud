@extends('crud::layouts.main')

@section('page-title', '404 Not Found')

@section('content')
<div class="container text-center mt-5">
    <h1 class="text-danger">404</h1>

    <h4>{{ $message ?? 'Page not found' }}</h4>

    <a href="{{ url('/') }}" class="btn btn-primary mt-3">
        Go Home
    </a>
</div>
@endsection
