<?
/**
 * This script provides a short example how to use the Imap Wrapper
 * class. 
 *
 * For detailed inforamtion of the below functions, please read
 * the comments above the functions in Imap.php
 *
 * To test the functionality of any of the below functions, 
 * remove the // comment lines. 
 *
 * This script has only been tested with Gmail SSL Imap servers.
 *
 * @see Copyright information in Imap.php
 *
 * @author Josh Grochowski (josh[at]kastang[dot]com)
 */

include("Imap.php");

$host = "imap.gmail.com";
$user = "__USERNAME__@gmail.com";
$pass = "__PASSWORD__";
$port = 993;
$ssl = true;
$folder = "INBOX";

$t = new Imap($host, $user, $pass, $port, $ssl, $folder);


/*
 * Returns an Associative Array containing the number of 
 * recent, unread, and total messages
 */
//print_r($t->getInboxInformation());

/*
 * Returns an Associative Array containing the subject of 
 * every email along with the Message Id of each individual 
 * email.
 */
//print_r($t->getMessageIds());

/*
 * Given a new $host, $user, $pass, $port, $ssl, $folder inputs, 
 * this function will disconnect from the old connection and open
 * a new connection to a specified server. 
 *
 * For the sake of the example, I am using the same information. 
 */
//$t->changeLoginInfo($host, $user, $pass, $port, $ssl, $folder);

/*
 * Returns an Associative Array containing detailed information
 * about a specific Message Id.
 */
//print_r($t->getDetailedMessageInfo(2));

/*
 * Parses a given Email address and returns an Array containing
 * the mailbox, host, and name of the given email address. 
 */
//$a = $t->getDetailedMessageInfo(2);
//print_r($t->parseAddresses($a["reply"]));

/*
 * Generate an email address to comply with RFC822 specifications. 
 */ 
//$u = "testusername";
//$h = "samplehost.com";
//$n = "TestFirst TestLastName";
//echo $t->createAddress($u, $h, $n);

/*
 * Deletes a message matching the given Message Id.
 */
//$t->deleteMessage(2);

/*
 * Returns an Array containing structural information about a given
 * Message Id. 
 * 
 * @see imap_fetchstructure (http://www.php.net/manual/en/function.imap-fetchstructure.php)
 */
//print_r($t->getStructure(2));

/*
 * Returns the body type of a given Message Id.
 */
//echo $t->getBodyType(2);

/*
 * Returns the encoding type of a given Message Id.
 */
//echo $t->getEncodingType(2);

/*
 * Given an encoded Base64 message, returns the decoded text. 
 *
 * Encoded string reads: "Testing One Two Three"
 */
//echo $t->decodeBase64("VGVzdGluZyBPbmUgVHdvIFRocmVl");

/*
 * Given a new folder, will disconnect and reconnect to the specified
 * folder name. 
 */
//$t->changeFolder("NEW_FOLDER_NAME");

/*
 * Disconnects an active imap connection
 */
//$t->disconnect();

?>
