@extends('statamic::layout')
@section('title', 'Places')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Places</h1>
    </div>

    <places-dashboard></places-dashboard>
@endsection
