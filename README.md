<img src="http://github.com/geerlingguy/Imap/raw/1.x/Resources/Imap-Logo.png" alt="IMAP for PHP Logo" />

# Imap

A PHP wrapper class for PHP's IMAP-related email handling functions.

This class includes many convenience methods to help take the headache out of
dealing with emails in PHP. For example, email handling method names make more
sense (e.g. `getMessage`, `deleteMessage`, and `moveMessage` along with a
message id, rather than passing around IMAP streams, using many
difficult-to-remember `imap_*` functions).

Also, this class adds some convenient helpful information to emails, like the
full message header (in `raw_header`), and whether or not the email was sent by
an autoresponder (see `detectAutoresponder` for details).

If you have any issues or feature suggestions, please post a new issue on
GitHub.

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

Disconnect from the server (necessary after deleting or moving messages):

```php
$mailbox->disconnect();
```

More methods and documentation can be found in the Imap.php class file.

## License

Imap is licensed under the MIT (Expat) license. See included LICENSE.md.
