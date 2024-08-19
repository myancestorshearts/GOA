<?php
namespace App\Console\Commands\Usps;

use Illuminate\Console\Command;

use App\Models\Mysql\GlobalFile;
use Aws\S3\S3Client;


class UspsFileTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usps:filetransfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'transfers files from ftp servers to s3 scalable';

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
        $sftp_01 = new \phpseclib\Net\SFTP('35.160.251.30');
        $sftp_01->login('usps', "Q%eX(te6{g!gmh{a7;Bpm,qunp?5N4c'");
        $sftp_02 = new \phpseclib\Net\SFTP('35.166.75.207');
        $sftp_02->login('usps', "FCKb,hrXtN+`cH{ck_LV.Xqb!}9qbutc");

        // upload to s3
        $s3_client = S3Client::factory(array(
            'credentials' => array(
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ),
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ));

        $connections = [$sftp_01, $sftp_02];
        
        foreach  ($connections as $key => $connection) {

            // Package Attributes
            $package_attributes_directory = '/home/usps/exports/package-attributes';
            $files = $connection->nlist($package_attributes_directory);
            $file_index = 0;
            $file_count = count($files);
            foreach ($files as $file) {

                if ($file == '.' || $file == '..') continue;
                $full_file_path = $package_attributes_directory . '/' . $file;
                

                $type_parts = explode('_', $file);
                if (count($type_parts) != 6) continue;

                $type = implode('_', ['USPS', $type_parts[2], $type_parts[3], $type_parts[4]]);

                echo 'Connection: ' . $key . ' - Directory: Package Attributes - File: ' . ++$file_index . '/' . $file_count . ' ' . $file;
                
                // download file contents
                $file_contents = $connection->get($full_file_path);

                // save object to s3 storage
                echo ' - uploading to s3';
                $s3_client->putObject(array(
                    'Bucket'            => 'goasolutions-files',
                    'Key'               => 'usps/' . $file,
                    'Body'              => $file_contents
                ));
                
                // create database row
                echo ' checking database';
                $global_file = GlobalFile::where([['filename', '=', $file], ['type', '=', $type]])->limit(1)->get()->first();
                if (!isset($global_file)) {
                    echo ' - creating row in database';

                    $global_file = new GlobalFile;
                    $global_file->filename = $file;
                    $global_file->type = $type;
                    $global_file->processed = 0;
                    $global_file->save();
                }
                else echo ' - found row in database';

                // we need to delete file from usps ftp server
                echo ' - deleting file';
                $connection->delete($full_file_path);

                echo "\n";
            }

        }

    }

}
