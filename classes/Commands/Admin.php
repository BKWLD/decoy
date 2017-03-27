<?php

namespace Bkwld\Decoy\Commands;

use Illuminate\Console\Command;
use Bkwld\Decoy\Models\Admin as BkwldAdmin;

class Admin extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'decoy:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin';

    /**
     * Create the new admin with input from the user
     *
     * @return void
     */
    public function handle()
    {
        $firstName = $this->ask('First Name');
        $lastName = $this->ask('Last Name');
        $email = $this->ask('Email');

        // Check the email to see if its already being used
        if ($admin = BkwldAdmin::where('email', $email)->exists()) {
            $this->error('That email is already in use');

            return;
        }

        $password = $this->secret('Enter your password');
        $confirm = $this->secret('Confirm your password');

        // password matching check
        if ($password != $confirm) {
            $this->error('Your passwords do not match.');

            return;
        }

        // Create a new admin
        $admin = new BkwldAdmin;
        $admin->first_name = $firstName;
        $admin->last_name = $lastName;
        $admin->email = $email;
        $admin->password = $password;

        // Save out the new admin
        $admin->save();
        $this->info('Admin created!');
    }
}
