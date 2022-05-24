<?php

namespace App\Console\Commands;

use App\Modules\ScrapUrl;
use Exception;
use Illuminate\Console\Command;

class ScrapUrlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:url {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrap Data from Url';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = $this->argument('url');
        try{
            $scrap_url = new ScrapUrl();
            $data = $scrap_url->getUrldata($url);

            file_put_contents(time().'.txt',json_encode($data));
            echo "Success";
        }catch(Exception $err){
            print_r($err->getMessage());
        }
    }
}
