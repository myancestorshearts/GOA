<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\RabbitMq;

class CallbackWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callback:worker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        $rabbit_client = new RabbitMq;

        $message = $rabbit_client->get('Callbacks');
        while(isset($message)) {

            echo 'Remaining Messages: ' . $message->getMessageCount();
            echo "\n";

            
            $rabbit_client->ack($message);

            $message = $rabbit_client->get('Callbacks');
        }


        return 0;
    }
}
