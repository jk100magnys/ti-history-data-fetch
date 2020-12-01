<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use http\Cookie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use phpDocumentor\Reflection\Types\Collection;

class ParseHistoricalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artem:parse {balance=1000} {sizeOfFall=7.5} {stoploss=0.98} {ticker=AAL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Path to filder with files
     * @var string
     */
    protected $dataPath = "data";

    /**
     * List of files with Data
     * @var Collection
     */
    protected $dataFiles;

    /**
     * Here is the data
     * @var Collection
     */
    protected $lowestAtDay;

    /**
     * Start Day
     * @var Carbon
     */
    protected $startDay;

    /**
     * End Day
     * @var Carbon
     */
    protected $endDay;

    /**
     * Size if dayly fall
     * @var int/float
     */
    protected $sizeOfFall = -7.5;

    /**
     * Here is the deals
     * @var Collection
     */
    protected $deals;

    /**
     * @var Storage
     */
    protected $conn;

    /**
     * Test balance
     * @var float
     */
    protected $balance = 1000.00;

    /**
     * Value of stoploss
     * @var float
     */
    protected $stoploss = 0.98;

    /**
     * Ticker
     * @var string
     */
    protected $ticker = "AAL";

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
        $this->conn = Storage::disk('storage'); //configured in the file filesystems.php
        $this->lowestAtDay = collect([]);
        $this->deals = collect([]);

        $this->sizeOfFall = -1 * abs(floatval(str_replace(",", ".", $this->argument('sizeOfFall'))));
        $this->balance = $this->argument('balance');
        $this->stoploss = floatval(str_replace(",", ".", $this->argument('stoploss')));
        $this->ticker = $this->argument('ticker');

        //$this->endDay = Carbon::parse("30.09.2019");

        $this->info('Start! Let\'s go!');

        $this->info('Scanning folder with Data...');
        $this->scanDataFolder();

        // @SK
        $isFounded = false;
        foreach($this->dataFiles as $key => $file)
        {
            if(strstr($file, $this->ticker . ".csv") !== false)
            {
                $this->info($file, "AAL.csv", strstr($file, "AAL.csv"));

                $isFounded = true;
                $this->parseFile($this->dataFiles->get($key));
                break;
            }
        }

        if(!$isFounded)
        {
            $this->error("Ticker Not Found");
        }

        return 0;
    }

    private function parseFile($filePath)
    {
        $this->info('Start parse file ' . $filePath);
        $this->info('LF daly fall more than ' . $this->sizeOfFall . "%");
        $this->info('Start Balance ' . $this->balance . "$");


        $lines = 0;

        $stream = $this->conn->readStream($filePath);
        while (($line = fgets($stream, 4096)) !== false) {
            if(strstr($line, "Date") !== false){ continue; }
            $lines++;

            //@SK remove \r\n
            $line = str_replace(array("\n", "\r"), '', $line);
            $objLine = collect(explode(";", $line));

            $objLine->put(2, floatval(str_replace(",", ".", $objLine->get(2))));
            $objLine->put(3, floatval(str_replace(",", ".", $objLine->get(3))));
            $objLine->put(4, floatval(str_replace(",", ".", $objLine->get(4))));
            $objLine->put(5, floatval(str_replace(",", ".", $objLine->get(5))));


            if($this->skipByDate($objLine->get(0))){ continue; }

            $this->findLowestAtDay($objLine);

            if($this->isOpenDeal() !== false)
            {
                $key = $this->isOpenDeal();

                if($objLine->get(4) < $this->deals->get($key)->get('stoploss'))
                {
                    $this->deals->get($key)->put('sday', $objLine->get(0));
                    $this->deals->get($key)->put('stime', $objLine->get(1));
                    $this->deals->get($key)->put('sprice', $this->deals->get($key)->get('stoploss'));

                    $this->balance += $this->deals->get($key)->get('amount') * $this->deals->get($key)->get('stoploss');

                } else if(floatval(number_format(($objLine->get(5) * $this->stoploss), 2)) > $this->deals->get($key)->get('stoploss')){
                    $this->deals->get($key)->put('stoploss', floatval(number_format(($objLine->get(5) * $this->stoploss), 2)));
                }
            }
        }

        foreach($this->deals as $deal)
        {
            $this->info("New deal!");
            $this->info($deal->get('day') ." : " . $deal->get('btime') . " => " . $deal->get('bprice'));
            if(!empty($deal->get('sprice')))
            {
                $this->info($deal->get('sday') ." : " . $deal->get('stime') . " => " . $deal->get('sprice'));
                $this->info("Profit: " . number_format(((1 - $deal->get('bprice') / $deal->get('sprice')) * 100), 2) . "%");
            } else {
                $this->info("Still HODL!");
            }
        }

        $this->info('Final Balance ' . $this->balance . "$");


        $this->lowestAtDay->forget('prev');

        $this->info($lines);
    }

    private function checkDeals($objLine)
    {
        $deal = $this->deals->last();
    }

    private function isOpenDeal()
    {
        if($this->deals->last() && $this->deals->last()->get('sprice', 0) == 0)
        {
            return $this->deals->keys()->last();
        }

        return false;
    }

    private function buyDeal($day, $data)
    {
        // check on buy
        if($this->isOpenDeal() === false)
        {
            $this->info($day . ": " . $data->get('start_price', 0) . " => " . $data->get('stop_price', 0) . " => " . $data->get('diff', 0) . "%");

            $amount = $this->balance % $data->get('stop_price');
            $this->balance -= $amount * $data->get('stop_price');

            $this->deals->push(collect(
                [
                    'day' => $day,
                    'btime' => $data->get('stop_time'),
                    'bprice' => $data->get('stop_price'),
                    'stoploss' => floatval(number_format(($data->get('stop_price') * $this->stoploss), 2)),
                    'sday' => 0,
                    'stime' => 0,
                    'sprice' => 0,
                    'amount' => $amount
                ]
            ));
        }
    }

    private function skipByDate($day)
    {
        if(!$this->endDay && !$this->startDay)
        {
            return false;
        }

        if(Carbon::parse($day)->lte($this->endDay))
        {
            return false;
        }

        return true;
    }

    private function findLowestAtDay($data)
    {
        $prevLine = $this->lowestAtDay->get('prev', '');

        if(empty($prevLine) || empty($this->lowestAtDay->get($data->get(0), '')))
        {
            $_dayData = collect(
                [
                    'start_time' => $data->get(1),
                    'start_price' => $data->get(2),
                    'stop_time' => '',
                    'stop_price'  => 0,
                    'diff' => 0,
                ]
            );

            $this->lowestAtDay->put($data->get(0), $_dayData);
        }

        if(!empty($prevLine))
        {
            if(Carbon::parse($data->get(0))->day > Carbon::parse($prevLine->get(0))->day)
            {
                $_d = $this->lowestAtDay->get($prevLine->get(0));

                $_d->put('stop_time', $prevLine->get(1));
                $_d->put('stop_price', $prevLine->get(2));

                //  я не умею считать проценты
                //$_diff = (($_d->get('start_price') / $_d->get('stop_price')) - 1) * 100;
                $_diff = (1 - $_d->get('start_price') / $_d->get('stop_price')) * 100;

                $_d->put('diff', floatval(number_format($_diff, 2)));

                $this->lowestAtDay->put($prevLine->get(0), $_d);

                // HERE IS DETECTED LOWEST DAY
                // WE CAN OPEN A DEAL
                if($_d->get('diff', 0) < $this->sizeOfFall)
                {
                    $this->buyDeal($prevLine->get(0), $_d);
                }
            }
        }

        $this->lowestAtDay->put('prev', $data);
    }

    private function scanDataFolder()
    {
        try{
            $this->dataFiles = collect($this->conn->allFiles($this->dataPath));
        } catch (\Exception $e)
        {
            $this->error('Something wrong!');
            $this->error($e->getFile() . ":" . $e->getLine());
            $this->error($e->getMessage());
        }

        $this->info('List of files (count: ' . $this->dataFiles->count() . ' files)');
    }
}
