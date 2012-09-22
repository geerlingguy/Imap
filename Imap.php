<?

/**
 * The Imap PHP class provides a wrapper for commonly used PHP IMAP functions.
 *
 * This class was originally written by Josh Grochowski, and was reformatted and
 * documented by Jeff Geerling.
 *
 * Usage examples can be found in the included README file, and all methods
 * should have adequate documentation to get you started.
 *
 * The php5-imap library must be present for this class to work, and it has been
 * tested with PHP 5.3+, and Gmail. Everything should work fine on slightly
 * older versions of PHP, and most IMAP-compliant email services should be fine.
 *
 * @version 1.0-beta1
 * @author Josh Grochowski (josh[at]kastang[dot]com).
 * @author Jeff Geerling (geerlingguy).
 */

class Imap {

    private $host;
    private $user;
    private $pass;
    private $port;
    private $folder;
    private $ssl;

    private $address;
    private $mailbox;

    /**
     * Called when the Imap object is created.
     *
     * Sample of a complete address: {imap.gmail.com:993/imap/ssl}INBOX
     *
     * @param $host (string)
     *   The IMAP hostname. Example: imap.gmail.com
     * @param $port (int)
     *   Example: 933
     * @param $ssl (bool)
     *   TRUE to use SSL, FALSE for no SSL.
     * @param $folder (string)
     *   IMAP Folder to open.
     * @param $user (string)
     *   Username used for connection. Gmail uses full username@gmail.com, but
     *   many providers simply use username.
     * @param $pass (string)
     *   Account password.
     *
     * @return (empty)
     */
    public function __construct($host, $user, $pass, $port, $ssl = true, $folder = 'INBOX') {
        if ((!isset($host)) || (!isset($user)) || (!isset($pass)) || (!isset($port))) {
            throw Exception("Error: All Constructor values require a non NULL input.");
        }

        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->folder = $folder;
        $this->ssl = $ssl;

        $this->changeLoginInfo($host, $user, $pass, $port, $ssl, $folder);
    }

    /**
     * Change IMAP folders and reconnect to the server.
     *
     * @param $folderName
     *   The name of the folder to change to.
     *
     * @return (empty)
     */
    public function changeFolder($folderName) {
        if ($this->ssl) {
            $address = '{' . $this->host . ':' . $this->port . '/imap/ssl}' . $folderName;
        } else {
            $address = '{' . $this->host . ':' . $this->port . '/imap}' . $folderName;
        }

        $this->address = $address;
        $this->reconnect();
    }

    /**
     * Log into an IMAP server.
     *
     * This method is called on the initialization of the class (see
     * __construct()), and whenever you need to log into a different account.
     *
     * Please see __construct() for parameter info.
     *
     * @return (empty)
     *
     * @throws Exception when IMAP can't connect.
     */
    public function changeLoginInfo($host, $user, $pass, $port, $ssl, $folder) {
        if ($ssl) {
            $address = '{' . $host . ':' . $port . '/imap/ssl}' . $folder;
        } else {
            $address = '{' . $host . ':' . $port . '/imap}' . $folder;
        }

        // Set the new address.
        $this->address = $address;

        // Open new IMAP connection
        if ($mailbox = imap_open($address, $user, $pass)) {
          $this->mailbox = $mailbox;
        } else {
          throw new Exception("Error: " . imap_last_error());
        }
    }

    /**
     * Returns an associative array with detailed information about a given
     * message.
     *
     * @param $msgId (int)
     *   Message id.
     *
     * @return Associative array with keys (strings unless otherwise noted):
     *   to
     *   from
     *   cc
     *   bcc
     *   reply_to
     *   sender
     *   datesent
     *   subject
     *   deleted (bool)
     *   answered (bool)
     *   draft (bool)
     *   body
     */
    public function getDetailedMessageInfo($msgId) {
        $this->tickle();

        // Get message details.
        $details = imap_headerinfo($this->mailbox, $msgId);
        if ($details) {
            // Get some basic variables.
            $deleted = ($details->Deleted == 'D');
            $answered = ($details->Answered == 'A');
            $draft = ($details->Draft == 'X');

            // Get the message body.
            $body = imap_fetchbody($this->mailbox, $msgId, 1.2);
            if (!strlen($body) > 0) {
                $body = imap_fetchbody($this->mailbox, $msgId, 1);
            }

            $msgArray = array(
                "to" => $details->toaddress,
                "from" => $details->fromaddress,
                "cc" => $details->ccaddress,
                "bcc" => $details->bbcaddress,
                "reply_to" => $details->reply_toaddress,
                "sender" => $details->senderaddress,
                "datesent" => $details->date,
                "subject" => $details->subject,
                "deleted" => $deleted,
                "answered" => $answered,
                "draft" => $draft,
                "body" => $body,
            );
        }

        return $msgArray;
    }

    /**
     * Returns an associative array with email subjects and message ids for all
     * messages in the active $folder.
     *
     * @return Associative array with message id as key and subject as value.
     */
    public function getMessageIds() {
        $this->tickle();

        // Fetch overview of mailbox.
        $overviews = imap_fetch_overview($this->mailbox, "1:" . imap_num_msg($this->mailbox), 0);
        $messageArray = array();

        // Loop through message overviews, build message array.
        foreach($overviews as $overview) {
            $messageArray[$overview->msgno] = $overview->subject;

            // Below is legacy code. After testing above, remove.
            // $tmp = array(
            //     "id" => $overview->msgno,
            //     "subject" => $overview->subject. "\n",
            // );
            // 
            // array_push($messageArray, $tmp);
        }

        return $messageArray;
    }

    /**
     * Return an associative array containing the number of recent, unread, and
     * total messages.
     *
     * @return Associative array with keys:
     *   unread
     *   recent
     *   total
     */
    public function getInboxInformation() {
        $this->tickle();

        // Get general mailbox information.
        $info = imap_status($this->mailbox, $this->address, SA_ALL);
        $mailInfo = array(
            "unread" => $info->unseen,
            "recent" => $info->recent,
            "total" => $info->messages,
        );
        return $mailInfo;
    }


    /**
     * Deletes an email matching the specified $msgId.
     *
     * @param $msgId (int)
     *   Message id.
     *
     * @return (empty)
     *
     * @throws Exception when message can't be deleted.
     */
    public function deleteMessage($msgId) {
        $this->tickle();

        // Attempt to delete message.
        if (!imap_delete($this->mailbox, 2)) {
          throw new Exception("Error in deleteMessage: " . imap_last_error());
        }
    }

    /**
     * Decodes Base64-encoded text.
     *
     * @param $text (string)
     *   Base64 encoded text to convert.
     *
     * @return (string)
     *   Decoded text.
     */
    public function decodeBase64($text) {
        $this->tickle();
        return imap_base64($text);
    }

    /**
     * Takes in a string of email addresses and returns an array of addresses
     * as objects. For example, passing in 'John Doe <johndoe@sample.com>'
     * returns the following array:
     *
     *     Array (   
     *       [0] => stdClass Object (
     *         [mailbox] => johndoe
     *         [host] => sample.com
     *         [personal] => John Doe
     *       )
     *     )
     *
     * You can pass in a string with as many addresses as you'd like, and each
     * address will be parsed into a new object in the returned array.
     *
     * @param $addresses (string)
     *   String of one or more email addresses to be parsed.
     *
     * @return (array)
     *   Array of parsed email addresses, as objects.
     *
     * @see imap_rfc822_parse_adrlist().
     */
    public function parseAddresses($addresses) {
        return imap_rfc822_parse_adrlist($addresses, "#");
    }

    /**
     * Create an email address to RFC822 specifications.
     *
     * @param $username (string)
     *   Name before the @ sign in an email address (example: 'johndoe').
     * @param $host (string)
     *   Address after the @ sign in an email address (example: 'sample.com').
     * @param $name (string)
     *   Name of the entity (example: 'John Doe').
     *
     * @return (string) Email Address in the following format:
     *  'John Doe <johndoe@sample.com>'
     */
    public function createAddress($username, $host, $name) {
        return imap_rfc822_write_address($username, $host, $name);
    }

    /**
     * Returns structured information for a given message id.
     *
     * @param $msgId
     *   Message id for which structure will be returned.
     *
     * @return (object)
     *   See imap_fetchstructure() return values for details.
     *
     * @see imap_fetchstructure().
     */
    public function getStructure($msgId) {
        return imap_fetchstructure($this->mailbox, $msgId);
    }

    /**
     * Returns the primary body type for a given message id.
     *
     * @param $msgId (int)
     *   Message id.
     * @param $numeric (bool)
     *   Set to true for a numerical body type.
     *
     * @return (mixed)
     *   Integer value of body type if numeric, string if not numeric.
     */
    public function getBodyType($msgId, $numeric = false) {
        // See imap_fetchstructure() documentation for explanation.
        $types = array(
            0 => "Text",
            1 => "Multipart",
            2 => "Message",
            3 => "Application",
            4 => "Audio",
            5 => "Image",
            6 => "Video",
            7 => "Other",
        );

        // Get the structure of the message.
        $structure = $this->getStructure($msgId);

        // Return a number or a string, depending on the $numeric value.
        if ($numeric) {
            return $structure->type;
        } else {
            return $types[$structure->type];
        }
    }

    /**
     * Returns the encoding type of a given $msgId.
     *
     * @param $msgId (int)
     *   Message id.
     * @param $numeric (bool)
     *   Set to true for a numerical encoding type.
     *
     * @return (mixed)
     *   Integer value of body type if numeric, string if not numeric.
     */
    public function getEncodingType($msgId, $numeric = false) {
        // See imap_fetchstructure() documentation for explanation.
        $encodings = array(
            0 => "7BIT",
            1 => "8BIT",
            2 => "BINARY",
            3 => "BASE64",
            4 => "QUOTED-PRINTABLE",
            5 => "OTHER",
        );

        // Get the structure of the message.
        $structure = $this->getStructure($msgId);

        // Return a number or a string, depending on the $numeric value.
        if ($numeric) {
            return $structure->encoding;
        } else {
            return $encodings[$structure->encoding];
        }
    }

    /**
     * Closes an active IMAP connection.
     *
     * @return (empty)
     */
    public function disconnect() {
        imap_close($this->mailbox);
    }

    /**
     * Reconnect to the IMAP server.
     *
     * @return (empty)
     *
     * @throws Exception when IMAP can't reconnect.
     */
    private function reconnect() {
      $this->mailbox = imap_open($this->address, $this->user, $this->pass);
      if (!$this->mailbox) {
        throw new Exception("Reconnection Failure: " . imap_last_error());
      }
    }

    /**
     * Checks to see if the connection is alive. If not, reconnects to server.
     *
     * @return (empty)
     */
    private function tickle() {
        if (!imap_ping($this->mailbox)) {
            $this->reconnect;
        }
    }
}
