# mprofi_api_client

Prosta implementacja biblioteki PHP dla mprofi API.

## Instalacja

Skopiować plik connector.php do katalogu z projektem.
Do poprawnego działania wymagany jest pakiet php-curl oraz PHP w wersji >=8.1.

## Przykłady użycia

### Wysyłanie wiadomości i sprawdzania statusu
```php
<?php
require_once('connector.php');

$token = "34b039c7017e4ae886c350eb32XXXXXX";
$client = new MprofiAPIConnector($token);

# zwykła wiadomość
$client->add_message("48500XXXXXX", "Test");

# wiadomość zawiarająca polskie znaki diakrytyczne
$options = array("encoding" => "utf-8");
$client->add_message("48500XXXXXX", "Wiadomość testowa", $options);

# możemy ustawić powiązać z wiadomością własne ID i ustawieniem planowanej daty wysyłki
$options = array("reference" => "my-msg-id-1", "date" => '2024-01-02 14:00:00');
$client->add_message("48500XXXXXX", "Test sms z id klienta", $options);

# metoda send zwraca tablicę identyfikatorów wiadomości
$messageIds = $client->send();

foreach ($messageIds as $msgId) {
  echo "msgId: " . $msgId . "\n";
}

?>
```

### Sprawdzenie statusu
```php
<?php
# UWAGA!
# W kodzie produkcyjnym nie jest zalecane aby sprawdzać status wiadomości zaraz po wysłaniu.
# Sprawdzenie nie powiedzie się jeśli zrobimy to zbyt szybko po wysłaniu (mprofi nie zdąży przetworzyć
# i wysłać wiadomości) albo dostaniemy informację, że wiadomość jest wysłana bo nie ma jeszcze informacji
# od operatora co się stało z wiadomoscią. Taka informacja może wrócić nawet 72h po wysłaniu.
# Pierwsze odpytanie zalecamy po kilku lub kilkunastu minutach. Jeśli nie uzyskamy finalnego statusu,
# powtarzamy wydłużając czas między kolejnymi sprawdzeniami. Jeśli po upływie 72h nadal brak statusu
# finalnego, można odczekać 2h, dalsze odpytyanie nie ma sensu.
#

require_once('connector.php');

$token = "34b039c7017e4ae886c350eb32XXXXXX";
$client = new MprofiAPIConnector($token);

$messageId = 1234;
$status = $client->get_status($messageId);
/*
  zwraca tablicę w postaci:
  array(
    "id" => 1234,
    "status" => "delivered",
    "reference" => "2015-01-01",
    "ts": "2015-01-01T09:51:32.431000+01:00"
  );
*/

?>
```

### Pobieranie wiadomości
```php
<?php
require_once('connector.php');

# UWAGA!
# W mprofi API Key stworzony do wysyłania wiadomości SMS nie może być użyty do odbierania wiadomości przychodzących.
# Do tego celu trzeba stworzyć osobny API Key oraz osobną instancję klasy MprofiAPIConnector

$token_to_receive_sms = "77b039c7017e4ae886c350eb32XXXXXX";
$client = new MprofiAPIConnector($token_to_receive_sms);

$from_date = "2023-02-01 0:00:00";
$to_date = "2023-12-31 23:59:59";
$incoming_messages = $client->get_incoming($from_date, $to_date);
/*
  zwraca tablicę w postaci:
  array(
    0 => array(
          "message" => "treść wiadomości",
          "sender" => "48123456789",
          "recipient" => "664400100",
          "ts" => "2015-02-16 10:24:40"
        ),

  );
*/
foreach ($incoming_messages as $message) {
    echo "Id: " . $message['id'] . ", czas: " . $message['ts'] . ", od: " . $message['sender'] . ", do: " . $message['recipient'] . ", treść: " . $message['message'] . "\n";
}

?>
```

## Wyjątki

Próba utworzenia obiektu klasy MprofiAPIConnector rzuci wyjątek klasy Exception. Medoty send, get_status i get_incoming zwrócić wyjątek klasy Exception
w przypadku błędu autoryzacji oraz innych problemem w komunikacji z API.
Metody add_message i get_incoming mogą rzucić wyjątek klasy InvalidArgumentException.
