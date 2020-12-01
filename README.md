# ti-history-data-fetch

Here is the Command for Laravel.
```
artem:parse {balance=1000} {sizeOfFall=7.5} {stoploss=0.98} {ticker=AAL}
```
Exmaple:
```
php artisan artem:parse 500 5 0.95 CCL
```


1) config/filesystems.php

```
        'storage' => [
            'driver' => 'local',
            'root' => storage_path(''),
        ],
```

2) put data files to "storage/data/*.csv"

3) Run!

Exmaple of output:

```
$ php artisan artem:parse 500 15 0.95 CCL

Start! Let's go!
Scanning folder with Data...
List of files (count: 1482 files)
data/CCL.csv
Start parse file data/CCL.csv
LF daly fall more than -15%
Start Balance 500$
05.03.2020: 32.43 => 27.82 => -16.57%
09.03.2020: 26.23 => 22.07 => -18.85%
12.03.2020: 22.16 => 15.37 => -44.18%
16.03.2020: 17.36 => 14.9 => -16.51%
17.03.2020: 14.89 => 12.85 => -15.88%
18.03.2020: 12.8 => 9.12 => -40.35%
27.03.2020: 18.13 => 14.43 => -25.64%
01.04.2020: 13.21 => 9.11 => -45.01%
10.11.2020: 19.2 => 16.69 => -15.04%
New deal!
05.03.2020 : 23:59 => 27.82
06.03.2020 : 16:37 => 27.11
Profit: -2.62%
New deal!
09.03.2020 : 23:56 => 22.07
10.03.2020 : 10:47 => 24.53
Profit: 10.03%
New deal!
12.03.2020 : 23:59 => 15.37
13.03.2020 : 11:22 => 16.32
Profit: 5.82%
New deal!
16.03.2020 : 23:59 => 14.9
17.03.2020 : 11:09 => 15.84
Profit: 5.93%
New deal!
17.03.2020 : 23:59 => 12.85
18.03.2020 : 10:00 => 12.21
Profit: -5.24%
New deal!
18.03.2020 : 23:59 => 9.12
19.03.2020 : 01:33 => 9.2
Profit: 0.87%
New deal!
27.03.2020 : 23:59 => 14.43
30.03.2020 : 10:01 => 13.77
Profit: -4.79%
New deal!
01.04.2020 : 23:59 => 9.11
02.04.2020 : 01:07 => 8.84
Profit: -3.05%
New deal!
10.11.2020 : 23:59 => 16.69
11.11.2020 : 17:32 => 16.71
Profit: 0.12%
Final Balance 517.12$
```
