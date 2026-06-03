<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminUser extends Command
{
    protected $signature = 'app:make-user
        {email : Email address}
        {password : Password}
        {--name=Admin : Display name}';

    protected $description = 'Create or update an admin user.';

    public function handle(): int
    {
        $email    = $this->argument('email');
        $password = $this->argument('password');
        $name     = $this->option('name');

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'is_admin' => true, 'chat_id' => 'web-' . substr(md5($email), 0, 12)]
        );

        $user->name     = $name ?: $user->name;
        $user->password = $password;
        $user->is_admin = true;
        $user->save();

        $this->info("✅ Admin tayyor:");
        $this->line("   ID:    {$user->id}");
        $this->line("   Email: {$user->email}");
        $this->line("   Name:  {$user->name}");

        return self::SUCCESS;
    }
}
