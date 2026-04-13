<?php

use Illuminate\Support\Str;
use Pdo\Mysql;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => 'null',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Tüm veritabanı bağlantıları devre dışı bırakıldı.
    | Uygulamanız veritabanı kullanmıyorsa bu ayarlar gereksizdir.
    |
    */

    'connections' => [

        // Tüm veritabanı bağlantıları devre dışı bırakıldı.
        // Uygulamanız veritabanı kullanmıyorsa bu ayarlar gereksizdir.

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    // Redis yapılandırması devre dışı bırakıldı.
    // Uygulamanız Redis kullanmıyorsa bu ayarlar gereksizdir.

    'redis' => [],

];
