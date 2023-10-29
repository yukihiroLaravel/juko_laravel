<!DOCTYPE html>
<html>
<head>
    <title>認証コードのお知らせです</title>
</head>
<body>
    <p>{{ $fullName }}さん</p>
    <p>あなたの認証コードは{{ $code }}です。認証コード有効時間は１時間です。</p>
    <p>以下のURLにアクセスして、認証を行ってください。</p>
    <p>{{ config('cache.allow_origin') }}/verification/{{ $token }}</p>
</body>
</html>