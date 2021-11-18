<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Todo;

class CreateItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todo:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create New Todo';

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
        $todo = [
            'user_id' => '2',
            'category' => 'testing',
            'title' => 'testing',
            'due_date' => '2021-10-30 12:19:32',
            'description' => 'testing',
            'constraint_personal' => '1',
            'constraint_value' => '1',
            'constraint_urgency' => '1',
            'constraint_importance' => '1',
            'status' => '-1',
            'deleted_at' => '2021-10-30 12:19:32'
        ];
        $todo = Todo::create($todo);
        //if ($todo) {
        if (false) {
            echo 'Todo created';
        }
        else {
            echo 'Todo Not Created';
        }
        
        return 0;
    }
}
