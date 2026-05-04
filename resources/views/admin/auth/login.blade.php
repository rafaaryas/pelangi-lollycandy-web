<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    @vite(['resources/css/app.css'])
</head>
<body>
<section class="section">
    <div class="container" style="max-width:420px">
        <form class="card" method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <h1>Admin Login</h1>
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Password">
            <button class="btn btn-primary" style="margin-top:1rem;width:100%">Masuk</button>
        </form>
    </div>
</section>
</body>
</html>
