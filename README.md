
# Imap

A simple PHP wrapper class for PHP's IMAP-related email handling functions.

## Usage

Connect to an IMAP account by creating a new Imap object with the required
parameters:

```php
$mailbox = new Imap($host, $user, $pass, $port, $ssl, $folder);
```

Get an array of message counts (recent, unread, and total):

```php
$mailbox->getInboxInformation();
```

Get an associative array of message ids and subjects:

```php
$mailbox->getMessageIds();
```

Load details for a message by id.

```php
$id = 2;
$mailbox->getDetailedMessageInfo($id);
```

Delete a message by id.

```php
$id = 2;
$mailbox->deleteMessage($id);
```

More methods and documentation can be found in the Imap.php class file.

## License

Imap is licensed under the MIT (Expat) license. See included LICENSE.md.
