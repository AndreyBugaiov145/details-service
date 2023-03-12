@extends('layout.app')

@section('navbar')
    @include('components.navbar_goust')
@endsection

@section('content')
    <div id="app">

    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/page/home/main.js') }}"></script>
@endsection
