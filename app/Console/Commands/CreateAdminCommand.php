<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdminCommand extends Command
{
  protected $signature = 'scf:create-admin
                            {--name= : Administrator display name}
                            {--email= : Administrator email address}
                            {--password= : Administrator password (avoid on shared shells)}
                            {--force : Skip production confirmation prompt}';

  protected $description = 'Create the first production administrator securely';

  public function handle(): int
  {
    $name = $this->option('name') ?: $this->ask(__('scf.release.admin_name_prompt'));
    $email = $this->option('email') ?: $this->ask(__('scf.release.admin_email_prompt'));

    if (User::query()->where('email', $email)->exists()) {
      $this->error(__('scf.release.admin_email_exists', ['email' => $email]));

      return self::FAILURE;
    }

    $password = $this->option('password') ?: $this->secret(__('scf.release.admin_password_prompt'));
    $passwordConfirmation = $this->option('password') ?: $this->secret(__('scf.release.admin_password_confirm'));

    $validator = Validator::make([
      'name' => $name,
      'email' => $email,
      'password' => $password,
      'password_confirmation' => $passwordConfirmation,
    ], [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255'],
      'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    if ($validator->fails()) {
      foreach ($validator->errors()->all() as $error) {
        $this->error($error);
      }

      return self::FAILURE;
    }

    if (app()->isProduction() && ! $this->option('force')) {
      if (! $this->confirm(__('scf.release.admin_production_confirm'))) {
        $this->warn(__('scf.release.admin_cancelled'));

        return self::FAILURE;
      }
    }

    $role = Role::findByName('super-admin', 'web');

    $user = User::query()->create([
      'name' => $name,
      'email' => $email,
      'password' => Hash::make($password),
      'email_verified_at' => now(),
      'is_active' => true,
    ]);

    $user->assignRole($role);

    AuditLog::query()->create([
      'auditable_type' => User::class,
      'auditable_id' => $user->id,
      'action' => 'admin.created',
      'new_values' => [
        'email' => $email,
        'via' => 'scf:create-admin',
      ],
    ]);

    $this->info(__('scf.release.admin_created', ['email' => $email]));

    return self::SUCCESS;
  }
}
