<?php

class NewsletterBounceTask extends BuildTask {

	private static $email = '';

	private static $password = '';

	protected $title = 'Mark bounced newsletter emails';

	protected $description = "Opens up a g-mail inbox and looks for .";

	protected $debug = true;

	function run($request) {
		$server = '{imap.gmail.com:993/imap/ssl}';
		$mailbox = imap_open($server, Config::inst()->get("NewsletterBounceTask", "email"), Config::inst()->get("NewsletterBounceTask", "password"));
		if($mailbox) {
			$emails = imap_search($mailbox, 'UNFLAGGED', SE_UID);
			if($emails) {
				foreach($emails as $emailID) {
					if($this->debug) {
						echo "<hr /><hr /><hr /><hr />$emailID<hr /><pre>";
					}
					$bounce = false;
					$to = "";
					$headers = imap_body($mailbox, $emailID, FT_UID);
					$headers = explode("\n", $headers);
					foreach($headers as $header) {
						$header = explode(':', $header);
						if(count($header) == 2) {
							list($name, $value) = $header;
							if($this->debug) {
								echo "<hr />$name<br />$value";
							}
							if($name == "bounce") {
								$bounce = true;
							}
							if($name == "To") {
								$to = Convert::raw2sql($to);
							}
						}
					}
					if($bounce && $to) {
						$member = Member::get()
							->filter(array("Email" => $to))
							->first();
						if($member) {
							$member->BlacklistedEmail = true;
							$member->write();

							$SQL_bounceTime = Convert::raw2sql("$date $time");

							$duplicateBounce = NewsletterEmailBounceRecord::get()
							->filter(array("BounceEmail" => $to))->first();
							if(!$duplicateBounce) {
								$record = new NewsletterEmailBounceRecord();
								$record->BounceEmail = $to;
								$record->BounceMessage = $error;
								$record->MemberID = $member->ID;
								$record->write();
							}
						}
					}
					if(1 == 1) {
						//imap_setflag_full($mailbox, $emailID, '\Seen', ST_UID);
					}
					if(1 == 1) {
						//imap_mail_move($mailbox, $emailID, '[Gmail]/Bin', CP_UID);
					}
					else {
						//imap_setflag_full($mailbox, $emailID, '\Flagged', ST_UID);
					}
					if($this->debug) {
						echo "</pre>";
					}
				}
			}
			imap_close($mailbox);
		}
		else {
			user_error("Can not find mailbox", E_USER_NOTICE);
		}
	}



}

