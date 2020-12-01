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
