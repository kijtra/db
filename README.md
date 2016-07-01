# Kijtra\DB

Simple PDO wrapper

-----

## Usage

```php
use \Kijtra\DB;

$db = new DB([
    'host' => 'localhost',
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'password'
]);

$name = $db->query("SELECT DATABASE()")->fetchColumn();
```

or use function (singleton)

```php
use function \Kijtra\DB as db;

db([
    'host' => 'localhost',
    'database' => 'my_database',
    'username' => 'root',
    'password' => 'password'
]);

db()->query("SELECT DATABASE()")->fetchColumn();
```

## Methods

Get Query History (newest high)

```php
$history = $db->history();

var_dump($history);
/*
array(2) {
  [0]=>
  array(2) {
    ["sql"]=>
    string(17) "SELECT DATABASE()"
    ["bind"]=>
    array(0) {
    }
  }
}
*/
```


Get Recent Errors (newest high)

```php
$error = $db->error();

var_dump($error);
/*
array(2) {
  [0]=> [PDOException object]
  [1]=> [PDOException object]
}
*/
```

_This software is released under the MIT License. See [License File](LICENSE.md) for more information._
