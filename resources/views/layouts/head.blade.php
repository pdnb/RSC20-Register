<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' · ' : '' }}RSC20 ลงทะเบียน</title>
<link rel="icon" type="image/png" href="{{ asset('images/logo-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo-192.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
@wirekitStyles
