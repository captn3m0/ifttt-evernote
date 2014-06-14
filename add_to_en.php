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
		add_to_en($url, $en_email, $en_project, 'post', $salt);
		
	} else {
		if ( (isset($_GET['url'])) && ($_GET['url'] != '') ) {
			add_to_en($_GET['url'], $en_email, $en_project, 'get', $salt);
		}
	}
	
	function add_to_en($url, $en_email, $en_project, $method, $salt) {
		
		// Check the salt is correct, otherwise do nothing.
		if ($_GET['salt'] == $salt) {
		
			// Very basic check on the URL.
			if (substr($url, 0, 4) == 'http') {
		
				// Run the URL through Readability's anonymous mobiliser script.
				$readability = 'http://www.readability.com/m?url='.$url;
				$ch = curl_init();
				$timeout = 5;
				curl_setopt($ch, CURLOPT_URL, $readability);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				$page = curl_exec($ch);
				curl_close($ch);
		
				if ($page) {
		
					// Swap the Readability CSS with some that's more suited to Evernote.
					include 'css.php';	
					$page = str_replace('<link rel="stylesheet" href="/media/css/mobile/mobile.css">', '<style>'.$css.'</style>', $page);
		
					// Remove the Readability footer.
					$page2 = explode('<footer role="contentinfo">', $page);
					$page3 = $page2[0].'<p class="readability-link"><a href="http://www.readability.com"><em>powered by</em> Readability</a></p></body></html>';

					// Convert <style> CSS to inline CSS.
					$emogrifier = new Emogrifier($page3);
					$page_final = $emogrifier->emogrify();
		
					// Grab the <title> from the returned page, strip out the Readablility text. If we don't find a <title> use the URL.
					preg_match("/<title>(.*)<\/title>/siU", $page_final, $title_match);
					if (count($title_match) > 0) {
						$title = preg_replace('/\s+/', ' ', $title_match[1]);
						$title = str_replace('&mdash;', '--', $title);
						$title = str_replace(' -- Readability', '', $title);
						$title = html_entity_decode($title);
        				$title = trim($title);
					} else {
        				$title = $url;
					}
        
        			// Add the Evernote project if one was specified.
					if ($en_project != '') {
        				$title .= ' @'.$en_project;
        			}
        
        			// Set email headers to allow sending of HTML email.
        			$headers = 'MIME-Version: 1.0'."\r\n"
        				.'Content-type: text/html; charset=iso-8859-1'."\r\n";
		
					// Send the email.
					if (mail($en_email, $title, $page_final, $headers)) {
						if ($method == 'get') {
							echo 'Sent!';
						}
					} else {
						if ($method == 'get') {
							echo 'Not sent!';
						}
					}
					
				}	// If $page.
			}		// If http URL check.
		}			// If salt.
	}				// Close function.
?>