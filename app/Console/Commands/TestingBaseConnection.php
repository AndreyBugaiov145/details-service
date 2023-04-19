<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestingBaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bc';

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
        $start = now();
        $this->comment('Processing');
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Connection' => 'close',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                'Origin' => 'https://www.rockauto.com',
            ],
            'Connection' => 'close',
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
        ]);
        $url = 'https://www.rockauto.com/catalog/catalogapi.php';
        $resault = $client->post(
            $url,
            [
                'timeout' => 5,
                'connect_timeout' => 10,
                'form_params' => [
                    'func' => 'getbuyersguide',
                    'scbeenloaded' => true,
                    'api_json_request' => 1,
                ],
                'headers' => [

                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
                ],
                'allow_redirects' => false
            ]);

        $this->info('statusCode = ' . $resault->getStatusCode());
        $time = $start->diffInSeconds(now());
        $this->comment("Processed in $time seconds");

        return 0;
    }
}
