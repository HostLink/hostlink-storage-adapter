## Config
```php


use HL\Storage\Adapter;
use League\Flysystem\Filesystem;

require_once("vendor/autoload.php");

$key = "token from hostlink";
$adapter = new Adapter($key, "https://storage.hostlink.com.hk");

$fs = new Filesystem($adapter);
//....

```
