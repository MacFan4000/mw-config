<?php

if ( $wmgSiteNoticeOptOut ) {
	// only show important notices when optout
	$wi->config->settings['wgNoticeProject']['default'] = 'optout';
}

// Global SiteNotice
// Increment this version number whenever you change the site notice
// and don't comment it out
$wgMajorSiteNoticeID = 50;

// Write your SiteNotice below.  Comment out this section to disable.
if ( !$wmgSiteNoticeOptOut ) {
	$wgHooks['SiteNoticeAfter'][] = 'onSiteNoticeAfter';
	function onSiteNoticeAfter( &$siteNotice, $skin ) {
		global $wmgSiteNoticeOptOut, $snImportant;

		$siteNotice .= <<<EOF
				<table class="wikitable" style="text-align:center;"><tbody><tr>
				<td style="font-size:125%">Miraheze plans to upgrade to the latest version of MediaWiki (1.36) on Saturday (12 June, 2021) from 19:00 UTC time to approximately 21:00 UTC. Please save your edits 5 minutes before hand.</td>
				</tr></tbody></table>
EOF;

		return true;
	}
}

