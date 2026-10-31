<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Buat akun admin baru tanpa mengubah akun yang sudah ada';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan perintah ini secara interaktif untuk memasukkan password dengan aman.');

            return self::FAILURE;
        }

        $name = $this->ask('Nama admin');
        $email = $this->ask('Email admin');
        $password = $this->secret('Password baru (minimal 12 karakter)');
        $passwordConfirmation = $this->secret('Ulangi password');

        $validator = Validator::make([
            'name' => $name, 'email' => $email, 'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:4096', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create(['name' => $name, 'email' => $email, 'password' => $password, 'is_admin' => true]);
        $this->info('Akun admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
