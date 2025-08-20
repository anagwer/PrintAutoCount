<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PIN Anda Telah Diperbarui</title>
</head>
<body>
    <h2>Hai, {{ $employeeName }}</h2>

    <p>PIN Anda telah berhasil diperbarui pada tanggal <strong>{{ \Carbon\Carbon::parse($updatedAt)->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}</strong>.</p>

    <p>
        <strong>PIN Baru Anda:</strong> {{ $newPin }}<br>
        <strong>Recovery Key:</strong> {{ $recoveryKey }}
    </p>

    <p>Simpanlah Recovery Key ini di tempat aman. Digunakan untuk memulihkan akses jika lupa PIN.</p>

    <p>Terima kasih.</p>
</body>
</html>

