<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


use App\Libraries\RabbitMq;
use App\Libraries\Slack;

use App\Models\Mysql;
use App\Models\Dynamo;


class KyleTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kyle:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A bunch of tests kyle made';

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


        $this->testRabbit();

        return 0;
    }

    private function testDynamoModel() {
        $model = Dynamo\RateDiscounts::create('test');
        $model->rates = [
            'domestic' => [
                'USPS' => [
                    'priority' => 5,
                    'priority_express' =>  5,
                    'parcel_select' => 5,
                    'cubic' => 7,
                    'first_class' => 0
                ],
            ],
            'international' => [
                'USPS' => [
                    'priority' => 5,
                    'priority_express' =>  5,
                    'first_class' => 0
                ]
            ]
        ];
        $model->updateItem();
    }

    private function testRabbit() {


        $rabbit_client = new RabbitMq;
        for ($i = 0 ;  $i < 1000; $i++)
        {
            $rabbit_client->queueMessage('Callbacks', [
                'test' => $i
            ]);
        }
        $rabbit_client->publishBatch();
    }
}
