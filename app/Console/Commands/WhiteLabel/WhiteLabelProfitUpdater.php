<?php
namespace App\Console\Commands\WhiteLabel;

use Illuminate\Console\Command;

use App\Models\Mysql\WalletTransaction;
use App\Models\Mysql\Label;
use Aws\S3\S3Client;

use App\Common\Functions;

class WhiteLabelProfitUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whitelabel:profitupdater';


    private $s3_client;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'updates the profits columns on white labels';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    private $connection_keys = ['paxsuite', 'shipdude'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach($this->connection_keys as $connection_key) {

            $total_profit = 0;
            
            $transactions = WalletTransaction::on($connection_key)->where([
                ['type', '=', 'Label'],
                ['profit_calculated', '=', 0]
            ])->orderBy('finalized_at', 'DESC')->get();

            $index = 0;
            $count = count($transactions);
            foreach ($transactions as $transaction) {
                echo $connection_key . ' - ' . ++$index . ' / ' . $count . ' - ' . $transaction->id;

                echo ' - finding whitelabel lable ';
                $label = Label::on($connection_key)->find($transaction->label_id);

                if (!isset($label)) {
                    dd('error finding label');
                }

                if ($label->verification_service != 'goa') {
                    dd('where did this come from');
                }

                echo ' - finding goa transaction';
                $goa_transaction = WalletTransaction::on('goa')->where('type', '=', 'Label')->where('label_id', '=', $label->verification_id)->limit(1)->get()->first();
                if (!isset($goa_transaction)) {
                    dd(' could not find the goa transaction');
                }
                

                echo ' - calculating profit';
                $profit = ($transaction->amount * -1) - ($goa_transaction->amount * -1);
                $total_profit += $profit;
                echo ' - profit: ' . $profit;
                echo ' - total Profit: ' . $total_profit;


                $transaction->profit = $profit;
                $transaction->profit_calculated = 1;
                $transaction->save();



                echo "\n";
            }
        }
    }
}