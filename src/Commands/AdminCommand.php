<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use TCG\Voyager\Facades\Voyager;

class AdminCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'voyager:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure there is a user with the admin role that has all of the necessary permissions.';

    /**
     * Get user options.
     */
    protected function getOptions()
    {
        return [
            ['create', null, InputOption::VALUE_NONE, 'Create an admin user', null],
        ];
    }

    public function fire()
    {
        return $this->handle();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get or create user
        $user = $this->getUser($this->option('create'));

        if (!$user) {
            $this->warn('Command aborted. No admin user was selected or created.');

            return;
        }

        // Get or create role
        $role = $this->getAdministratorRole();

        // Get all permissions
        $permissions = Voyager::model('Permission')->all();

        // Assign all permissions to the admin role
        $role->permissions()->sync(
            $permissions->pluck('id')->all()
        );

        // Ensure that the user is admin
        $user->role_id = $role->id;
        $user->save();

        $this->info('The user now has full access to your site.');
    }

    /**
     * Get command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['email', InputOption::VALUE_REQUIRED, 'The email of the user.', null],
        ];
    }

    /**
     * Get the administrator role, create it if it does not exists.
     *
     * @return mixed
     */
    protected function getAdministratorRole()
    {
        $role = Voyager::model('Role')->firstOrNew([
            'name' => 'admin',
        ]);

        if (!$role->exists) {
            $role->fill([
                'display_name' => 'Administrator',
            ])->save();
        }

        return $role;
    }

    /**
     * Get or create user.
     *
     * @param bool $create
     *
     * @return \App\User
     */
    protected function getUser($create = false)
    {
        $email = $this->argument('email');

        $model = Auth::guard(app('VoyagerGuard'))->getProvider()->getModel();
        $model = Str::start($model, '\\');

        if ($create) {
            return $this->createUser($model, $email);
        }

        $user = call_user_func($model.'::where', 'email', $email)->first();

        if ($user) {
            return $user;
        }

        $this->warn("User with email {$email} was not found.");

        if ($this->confirm('Create a new admin user with this email?', true)) {
            return $this->createUser($model, $email);
        }

        return null;
    }

    protected function createUser(string $model, ?string $email = null)
    {
        $name = trim((string) $this->ask('Enter the admin name'));
        if ($name === '') {
            $name = 'Administrator';
        }
        $password = $this->secret('Enter admin password');
        $confirmPassword = $this->secret('Confirm Password');

        if (!$email) {
            $email = $this->ask('Enter the admin email');
        }
        $email = trim((string) $email);

        if ($email === '') {
            $this->error('Email is required.');

            return null;
        }

        if ($model::where('email', $email)->exists()) {
            $this->error("Can't create user. User with the email {$email} exists already.");

            return null;
        }

        if ($password !== $confirmPassword) {
            $this->error("Passwords don't match.");

            return null;
        }

        $this->info('Creating admin account');

        $roleId = $this->getAdministratorRole()->id;

        return call_user_func($model.'::forceCreate', [
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role_id'  => $roleId,
        ]);
    }
}
