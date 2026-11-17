<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @include('pdf.styles')
        @font-face {
            font-family: 'Anuphan';
            font-style: normal;
            font-weight: 400;
            src: url('{{ resource_path('fonts/Anuphan-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Anuphan';
            font-style: normal;
            font-weight: 700;
            src: url('{{ resource_path('fonts/Anuphan-Bold.ttf') }}') format('truetype');
        }
        @page { margin: 0; }
        body { margin: 0; color: #1e2a5a; font-family: 'Anuphan', sans-serif; }
        .badge { position: relative; width: {{ $page['width'] }}pt; height: {{ $page['height'] - 1 }}pt; overflow: hidden; }
        .badge + .badge { page-break-before: always; }
        .bg { position: absolute; left: 0; top: 0; width: {{ $page['width'] }}pt; height: {{ $page['height'] }}pt; }
        .photo { position: absolute; left: {{ $box['left'] }}pt; top: {{ $box['top'] }}pt; width: {{ $box['width'] }}pt; height: {{ $box['height'] }}pt; border-radius: 6pt; }
        .text { position: absolute; left: 6%; right: 6%; top: {{ $textTop }}pt; text-align: center; line-height: 1; }
        .name { font-size: 15pt; font-weight: bold; }
        .position { font-size: 10pt; margin-top: 2pt; }
        .university { font-size: 10pt; font-weight: bold; margin-top: 1pt; }
        .role { font-size: 11pt; font-weight: bold; color: #082068; margin-top: 2pt; }
    </style>
</head>
<body>
    @foreach ($badges as $badge)
        <div class="badge">
            <img class="bg" src="{{ $backgrounds[$badge['kind']] }}" alt="">
            @if ($badge['photo'])
                <img class="photo" src="{{ $badge['photo'] }}" alt="">
            @endif
            <div class="text">
                <div class="name">{{ $badge['name'] }}</div>
                @if ($badge['position'])
                    <div class="position">{{ $badge['position'] }}</div>
                @endif
                <div class="university">{{ $badge['university'] }}</div>
                <div class="role">{{ $badge['role'] }}</div>
            </div>
        </div>
    @endforeach
</body>
</html>
