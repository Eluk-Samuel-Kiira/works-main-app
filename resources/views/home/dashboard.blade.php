@extends('layouts.app')
@section('title', 'Dashboard - Stardena Works')

@section('app-content')

<script>
    window.location.href = "{{ route('analytics.dashboard') }}";
</script>
@endsection