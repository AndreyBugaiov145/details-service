<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:registration {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'registration new user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::create([
            'name' => $this->argument('email'),
            'email' => $this->argument('email'),
            'password' => bcrypt($this->argument('password')),
        ]);

        echo 'success';
        return;
    }
}
