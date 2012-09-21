
# Imap

A simple PHP wrapper class for frequently used php-imap functions.

## Usage

Connect to an IMAP account by creating a new Imap object with the required
parameters:

```php
<?php
$mailbox = new Imap($host, $user, $pass, $port, $ssl, $folder);
?>
```

Get an array of message counts (recent, unread, and total):

```php
<?php
$mailbox->getInboxInformation();
?>
```

Get an associative array of message ids and subjects:

```php
<?php
$mailbox->getMessageIds();
?>
```

Load details for a message by id.

```php
<?php
$id = 2;
$mailbox->getDetailedMessageInfo($id);
?>
```

Delete a message by id.

```php
<?php
$id = 2;
$mailbox->deleteMessage($id);
?>
```

More examples and information can be found in ImapExample.php.

## License

Imap is licensed under the MIT (Expat) license. See included LICENSE.md.
