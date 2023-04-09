@extends('layout.app')

@section('navbar')
    @auth
        @include('components.navbar')
        @include('components.navbar_guest')
    @endauth

    @guest
        @include('components.navbar_guest')
    @endguest

@endsection

@section('content')
    <div id="app">

    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/page/home/main.js') }}"></script>
@endsection
