<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <p>Halo {{ $user->employee->name }},</p>
    <p>Password akun Anda telah direset oleh sistem.</p>
    <p><strong>Username:</strong> {{ $user->username }}</p>
    <p><strong>Password Baru:</strong> {{ $password }}</p>

    <p>Silakan login dan segera ganti password Anda untuk alasan keamanan.</p>

    <br>
    <p>Hormat kami,</p>
    <p>Tim ICT</p>
</body>
</html>
