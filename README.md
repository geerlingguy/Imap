
# Imap

A simple PHP wrapper class for PHP's IMAP-related email handling functions.

## Usage

Connect to an IMAP account by creating a new Imap object with the required
parameters:

```php
$host = 'imap.example.com';
$user = 'johndoe';
$pass = '12345';
$port = 993;
$ssl = true;
$folder = 'INBOX';
$mailbox = new Imap($host, $user, $pass, $port, $ssl, $folder);
```

Get a list of all mailboxes:

```php
$mailbox->getMailboxInfo();
```

Get an array of message counts (recent, unread, and total):

```php
$mailbox->getCurrentMailboxInfo();
```

Get an associative array of message ids and subjects:

```php
$mailbox->getMessageIds();
```

Load details for a message by id.

```php
$id = 2;
$mailbox->getMessage($id);
```

Delete a message by id.

```php
$id = 2;
$mailbox->deleteMessage($id);
```

More methods and documentation can be found in the Imap.php class file.

## License

Imap is licensed under the MIT (Expat) license. See included LICENSE.md.
