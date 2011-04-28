<?
/**
 * Copyright (C) 2011 by Josh Grochowski (josh[dot]kastang[at]gmail[dot]com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The Imap Class provides a wrapper for commonly used PHP imap functions. 
 *
 * Please read the ImapExample.php file for function details and examples. 
 *
 * @required:
 *      *php5-imap library
 *      *PHP 5.3+ (older versions may work, tested with PHP 5.3). 
 *
 * @tested:
 *      *Tested with Gmail SSL imap server. 
 *
 * @author Josh Grochowski (josh[at]kastang[dot]com)
 *
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
     * This constructor is called when the Imap object is created. 
     *
     * An example of the constructor fields are listed below:
     *
     * Sample complete address:
     * {imap.gmail.com:993/imap/ssl}INBOX
     *
     * @param $host = "imap.gmail.com"
     * @param $port = 933
     * @param $ssl = true (false if ssl isn't being used).
     * @param $folder = "INBOX"
     *
     * @param $user -
     *      Case 1: username@gmail.com
     *      Cast 2: username
     *
     *      Case 1 is the usual $user, depending on the imap provider,
     *      Case 2 may be used. Please consult with your imap provider to see
     *      which username string to use. 
     * 
     * @param $pass - Account Password. 
     *
     * @return None. 
     *
     */
    public function __construct($host, $user, $pass, $port, $ssl=true, $folder='INBOX') {

        if((!isset($host)) || (!isset($user)) || (!isset($pass)) || (!isset($port)) ||
            (!isset($ssl)) || (!isset($folder))) {
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
     * This function will change imap folders and reconnect to the existing server. 
     *
     * @return None. 
     */
    public function changeFolder($folderName) {

        if($this->ssl) {
            $m = '{'.$this->host.':'.$this->port.'/imap/ssl}'.$folderName;
        } else {
            $m = '{'.$this->host.':'.$this->port.'/imap}'.$folderName;
        }

        $this->address = $m;
        $this->reconnect();
    }

    /**
     * This function will log in to a different imap server. 
     *
     * @params - Please see __constructor function for parameter details. 
     *
     * @return none. 
     */
    public function changeLoginInfo($host, $user, $pass, $port, $ssl, $folder) {

        if($ssl) {
            $m = '{'.$host.':'.$port.'/imap/ssl}'.$folder;
        } else {
            $m = '{'.$host.':'.$port.'/imap}'.$folder;
        }

        $this->address = $m;

        $mailbox = imap_open($m, $user, $pass) or die("Error: ".imap_last_error());
        $this->mailbox=$mailbox;

    }

    /**
     * This function will return an associative array containing detailed information 
     * about a given $msgId. 
     *
     * @return An associative array containing to, from, cc, bss, reply, sender, datesent, 
     * subject, deleted, answered, draft, and body status for a given $msgId. 
     */
    public function getDetailedMessageInfo($msgId) {

        if(!$this->isConnectionAlive()) {
            $this->reconnect();
        }

        $details = imap_headerinfo($this->mailbox,$msgId);

        if($details) {

            if($details->Deleted == 'D') {
                $deleted = true;
            } else {
                $deleted = false;
            }

            if($details->Answered == 'A') {
                $answered = true;
            } else {
                $answered = false;
            }

            if($details->Draft == 'X') {
                $draft = true;
            } else {
                $draft = false;
            }

            $body = imap_fetchbody($this->mailbox,$msgId, 1.2);
            if(!strlen($body)>0) {
                $body = imap_fetchbody($this->mailbox,$msgId, 1);
            }

            $detailArray = array(
                "to" => $details->toaddress,
                "from" => $details->fromaddress,
                "cc" => $details->ccaddress,
                "bcc" => $details->bbcaddress,
                "reply" => $details->reply_toaddress,
                "sender" => $details->senderaddress,
                "datesent" => $details->date,
                "subject" => $details->subject,
                "deleted" => (int)$deleted,
                "answered" => (int)$answered,
                "draft" => (int)$draft,
                "body" => $body
            );
        }

        return $detailArray;
    }

    /**
     * This function will return an associative array containing the subject of every
     * email in the $folder along with its associated message id. 
     *
     * The purpose of this function is to help associate Subjects with Message Ids. 
     */
    public function getMessageIds() {

        if(!$this->isConnectionAlive()) {
            $this->reconnect();
        }

        $overview = imap_fetch_overview($this->mailbox, "1:".imap_num_msg($this->mailbox), 0);
        $messageArray = array();

        foreach($overview as $o) {

            $tmp = array(
                "id" => $o->msgno,
                "subject" => $o->subject."\n"
            );

            array_push($messageArray, $tmp);
        }

        return $messageArray;
    }

    /**
     * This function will return an associative array containing the number of
     * recent, unread, and total messages.
     */
    public function getInboxInformation() {

        if(!$this->isConnectionAlive()) {
            $this->reconnect();
        }

        $info = imap_status($this->mailbox, $this->address, SA_ALL);

        $mailInfo = array(
            "unread" => $info->unseen,
            "recent" => $info->recent,
            "total" => $info->messages
        );

        return $mailInfo;
    }


    /**
     * Deletes an email matching the specified $msgId.
     */
    public function deleteMessage($msgId) {
        
        if(!$this->isConnectionAlive()) {
            $this->reconnect();
        }

        imap_delete($this->mailbox, 2) or die("Error in deleteMessage: ". imap_last_error());
    }

    /**
     * @param $text - Base64 encoded Text. 
     *
     * @return - Decoded text. 
     */
    public function decodeBase64($text) {

        if(!$this->isConnectionAlive()) {
            $this->reconnect();
        }

        return imap_base64($text);
    }

    /**
     * Takes in a string of Email Addresses and returns an Array of
     * addresses. 
     *
     * Example:
     * "craigslist.org" <noreply@craigslist.org>
     *
     * will return:
     * Array
     * (   
     *     [0] => stdClass Object
     *          (   
     *              [mailbox] => noreply
     *              [host] => craigslist.org
     *              [personal] => craigslist.org
     *          )
     *
     *)
     *
     * Note: More then one Email Address can be entered as a parameter. An 
     * array containing N array entries (where N is the number of emails entered) 
     * will be returned.
     *
     */
    public function parseAddresses($adr) {
        $adrArray = imap_rfc822_parse_adrlist($adr, "#");
        return $adrArray;
    }

    /**
     * This function will create an Email Address to RFC822 specifications. 
     *
     * @param $username - name before the @ sign in an email  address. 
     * @param $host - address after the @ sign in an email address.
     * @param $name - name of the person
     *
     * @return Email Address in the following format:
     *  FirstName LastName <username@host.com>
     */
    public function createAddress($username, $host, $name) {
        $adr = imap_rfc822_write_address($username, $host, $name);
        return $adr;
    }

    /**
     * @return: The structure returned by imap_fetchstructure in an unmodified
     * form. 
     *
     * @see imap_fetchstructure (http://www.php.net/manual/en/function.imap-fetchstructure.php)
     */
    public function getStructure($msgId) {

        $structure = imap_fetchstructure($this->mailbox, $msgId);
        return $structure;
    }

    /**
     * This function will return the Primary Body Time of a given $msgId. 
     *
     * @param $num: 
     *      -true = The Numerical representation of the primary body type
     *      will be returned.
     *      -false(default) = A Word representation of the primary body type
     *      will be returned.
     */
    public function getBodyType($msgId, $num=false) {

        $typeArray = array(
            0 => "Text", 1 => "Multipart",
            2 => "Message", 3 => "Application",
            4 => "Audio", 5 => "Image",
            6 => "Video", 7 => "Other"
        );

        $struct = $this->getStructure($msgId);

        if($num) {
            return $struct->type;
        } else {
            return $typeArray[$struct->type];
        }
    }

    /**
     * This function will return the encoding type of a given $msgId.
     *
     * @param $num: 
     *      -true = The Numerical representation of the primary encoding type
     *      will be returned.
     *      -false(default) = A word representation of the primary encoding type
     *      will be returned.
     */
    public function getEncodingType($msgId, $num=false) {

        $encodingArray = array(
            0 => "7BIT", 1 => "8BIT",
            2 => "BINARY", 3 => "BASE64",
            4 => "QUOTED-PRINTABLE", 5 => "OTHER"
        );

        $struct = $this->getStructure($msgId);

        if($num) {
            return $struct->encoding;
        } else {
            return $encodingArray[$struct->encoding];
        }
    }

    /**
     * Closes an acitve imap connection.
     */
    public function disconnect() {
        imap_close($this->mailbox);
    }

    /*
     * The below functions are used internally, if you see a need to use the below functions - remove
     * 'private' from the functions. 
     */

    /**
     * If the connection to the imap server was lost, reconnect.
     */
    private function reconnect() {
        $this->mailbox = imap_open($this->address, $this->user, $this->pass) or die("Reconnection Failure: 
            ".imap_last_error());
    }

    /**
     * Checks to see if the connection to the imap server is still alive. 
     */
    private function isConnectionAlive() {
        $conn = imap_ping($this->mailbox);

        if($conn) {
            return true;
        } else {
            return false;
        }
    }
}

?>
