<!-- resources/views/emails/pin_created.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PIN Berhasil Dibuat</title>
</head>
<body>
    <h2>Hai, {{ $employeeName }}</h2>

     <p>PIN Anda berhasil dibuat pada tanggal <strong>{{ \Carbon\Carbon::parse($createdAt)->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</strong>.</p>

    <p>
        <strong>PIN Anda:</strong> {{ $pin }}<br>
        <strong>Recovery Key:</strong> {{ $recoveryKey }}
    </p>

    <p>Simpanlah Recovery Key ini di tempat aman. Digunakan untuk memulihkan akses jika lupa PIN.</p>

    <p>Terima kasih.</p>
</body>
</html>
