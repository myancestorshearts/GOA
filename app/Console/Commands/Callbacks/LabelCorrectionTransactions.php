<?php
namespace App\Console\Commands\Callbacks;

use Illuminate\Console\Command;

use App\Models\Mysql;

class LabelCorrectionTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callbacks:label_correction_transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Callbacks create wallets';

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
        $label_corrections = Mysql\LabelCorrection::on('goa')->whereRaw('wallet_transaction_id IS NULL')->get();

        echo "\n\n";
        echo "Checking duplicates";
        echo "\n\n";

        $index = 0;
        $count = count($label_corrections);

        $label_correction_refs = [];

        foreach($label_corrections as $label_correction) {

            echo ++$index . '/' . $count . "\n";
            if (isset($label_correction_refs[$label_correction->reference_id])) {
                dd($label_correction);
            }
            $label_correction_refs[$label_correction->reference_id] = true;
        }



        echo "\n\n";
        echo "creating wallet transactions";
        echo "\n\n";

        $index = 0;
        foreach($label_corrections as $label_correction) {

            echo ++$index . '/' . $count . " - Adding Transactions\n";
        $label_correction->addTransaction('goa');
        }

    }

}
