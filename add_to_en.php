<?php
	/* ADD TO EVERNOTE
	 * v1.1
	 * https://github.com/m00min/AddToEvernote
	 * 
	 * Copyright (c) Di Turner (www.diturner.co.uk)
	 * Licensed under the MIT License. Read a copy of the license in the LICENSE.txt
	 */
	 
	// Link to settings.
	require 'settings.php';

	// Link to Emogrifier.
	include('emogrifier.php');
	
	// Get the vars from IFTTT.
	$from_ifttt = json_decode(file_get_contents('php://input'), true);
	
	// Check for a URL sent as either a GET (so we can use the script 
	// as a browser bookmarklet), or POST (what is sent from IFTTT).
	if ($from_ifttt['title']) {
		$url = str_replace('\/', '/', $from_ifttt['title']);
		add_to_en($url, $en_email, $en_project, $salt, $token);
		
	} else {
		if ( (isset($_GET['url'])) && ($_GET['url'] != '') ) {
			add_to_en($_GET['url'], $en_email, $en_project, $salt, $token);
		}
		else{
			die('No URL specified in GET request');
		}
	}
	
	function add_to_en($url, $en_email, $en_project, $salt, $token) {
		
		// Check the salt is correct, otherwise do nothing.
		if ($_GET['salt'] == $salt) {
		
			// Very basic check on the URL.
			if (substr($url, 0, 4) == 'http') {
		
				// Run the URL through Readability's anonymous mobiliser script.
				$readability = 'http://readability.com//api/content/v1/parser?url='.$url.'&token='.$token;
				$ch = curl_init();
				$timeout = 10;
				curl_setopt($ch, CURLOPT_URL, $readability);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				$page = curl_exec($ch);
				curl_close($ch);
		
				if ($page) {
		
					$json = json_decode($page);
					$page = $json->content;
					// Swap the Readability CSS with some that's more suited to Evernote.
					include 'css.php';	
					$page.="<style>$css</style>";

					// Convert <style> CSS to inline CSS.
					$emogrifier = new Emogrifier($page);
					$page_final = $emogrifier->emogrify();
		
					$title = $json->title;
        
        			// Add the Evernote project if one was specified.
					if ($en_project != '') {
        				$title .= ' @'.$en_project;
        			}
        
        			// Set email headers to allow sending of HTML email.
        			$headers = 'MIME-Version: 1.0'."\r\n"
        				.'Content-type: text/html; charset=iso-8859-1'."\r\n";
		
					// Send the email.
					if (mail($en_email, $title, $page_final, $headers)) {
						echo 'Mail to evernote Sent!';
					} else {
						echo $page_final;
						echo 'Mail not sent to evernote!';
					}
					
				}	// If $page.
				else{
					die("No response from readability");
				}
			}		// If http URL check.
			else{
				die("Invalid URL");
			}
		}			// If salt.
		else{
			die("Invalid Salt");
		}
	}				// Close function.
?>