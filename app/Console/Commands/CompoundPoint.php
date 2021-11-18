<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Base\TodoBase;

class CompoundPoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todo:compound_point';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compound Points for users created same day as today';

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
        $todo_base = new TodoBase();
        $_result = $todo_base->compound_points();
        echo $_result['status']." ".$_result['message'];
        return 0;
    }
}
