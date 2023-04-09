@extends('layout.app')

@section('navbar')
    @include('components.navbar')
@endsection

@section('content')
    <div id="app">

    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/page/setting/main.js') }}"></script>
@endsection
