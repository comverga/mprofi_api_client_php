# Usage

```php
<?php

require_once('connector.php');

$token = "34b039c7017e4ae886c350eb32XXXXXX";
$cli = new MprofiAPIConnector($token);

$cli->addMessage("500XXXXXX", "Test");
$res = $cli->send();

print_r($res);

?>
```
