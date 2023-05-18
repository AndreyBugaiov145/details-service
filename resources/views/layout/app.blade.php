<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="shortcut icon" href="{{asset('images/logo.svg')}}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="title" content="Деталі для вашого авто">
    <meta name="description" content="Зручний каталог запчастин для вашого авто">
    <meta name="keywords" content="Деталі, Деталі для авто, Авто Деталі, Запчастини ,Деталі BUICK,Деталі CHEVROLET,Деталі CHRYSLER,Деталі DODGE,Деталі FORD
    ,Деталі JEEP,Деталі MAZDA,Деталі AUDI,Деталі CADILLAC">

    <meta property="og:title" content="Деталі для вашого авто" />
    <meta property="og:description" content="Зручний каталог запчастин для вашого авто" />
    <meta property="og:image" content="{{asset('images/logo.svg')}}" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Detail List') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body >
    @yield('navbar')
<div>
    @yield('content')
</div>

<!-- Scripts -->
@yield('scripts')
</body>
</html>
