<?php
/*
LocalSettings.php for Miraheze.
Authors of initial version: John Lewis, Southparkfan, Orain contributors
*/

# Load PrivateSettings (e.g. wgDBpassword)
require_once( "/srv/mediawiki/config/PrivateSettings.php" );

# Load global skins and extensions
require_once( "/srv/mediawiki/config/GlobalSkins.php" );
require_once( "/srv/mediawiki/config/GlobalExtensions.php" );

# Don't allow web access.
if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
}

# Initialize $wgConf
$wgConf = new SiteConfiguration;
$wgConf->suffixes = array( 'wiki' );
$wgLocalVirtualHosts = array( '185.52.1.77' );

$wmgHostname = ( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null;

$wgConf->settings = array(
    // Database
    'wgDBtype' => array(
        'default' => 'mysql',
    ),
    'wgDBserver' => array(
        'default' => '185.52.1.77',
    ),
    'wgDBuser' => array(
        'default' => 'mediawiki',
    ),
    'wgDBadminuser' => array(
        'default' => 'wikiadmin',
    ),
    
    // Files
    'wgEnableUploads' => array(
        'default' => true,
    ),
    'wgFileExtensions' => array(
        'default' => array( 'gif', 'ico', 'jpeg', 'jpg', 'ogg', 'png', 'svg', 'pdf' ),
    ),
    'wgUseInstantCommons' => array(
        'default' => true,
    ),
    'wgUploadDirectory' => array(
        'default' => "/srv/mediawiki-static/$wmgHostname",
    ),
    'wgUploadPath' => array(
        'default' => "https://static.miraheze.org/$wmgHostname",
    ),

    // ImageMagick
    'wgUseImageMagick' => array(
        'default' => true,
    ),
    'wgImageMagickCommand' => array(
        'default' => '/usr/bin/convert',
    ),

    // Job Queue
    'wgJobRunRate' => array(
        'default' => 0,
    ),

    // Language
    'wgLanguageCode' => array( // Hardcode "en"
        'default' => 'en',
    ),

    // Mail
    'wgEnableEmail' => array(
        'default' => true,
    ),
    'wgPasswordSender' => array(
        'default' => 'noreply@miraheze.org',
    ),
    'wgSMTP' => array(
        'default' => array(
            'host' => '185.52.1.76',
            'port' => 25,
            'IDHost' => 'miraheze.org',
            'auth' => true,
            'username' => 'noreply',
            'password' => $wmgSMTPPassword,
        ),
    ),

    // Misc stuff
    'wgSitename' => array(
        'default' => 'No sitename set!',
    ),

    // Server
    'wgArticlePath' => array(
        'default' => '/wiki/$1',
    ),
    'wgDisableOutputCompression' => array(
        'default' => true,
    ),
    'wgScriptExtension' => array(
        'default' => '.php',
    ),
    'wgScriptPath' => array(
        'default' => '/w',
    ),
    'wgServer' => array(
        'default' => 'https://$lang.miraheze.org',
    ),
    'wgShowHostnames' => array(
        'default' => true,
    ),
    'wgUsePathInfo' => array(
        'default' => true,
    ),

    // Style
    'wgDefaultSkin' => array(
        'default' => 'vector',
    ),

    // Empty arrays (do not touch unless you know what you're doing)
    'wmgClosedWiki' => array(
        'default' => false,
    ),
    'wmgPrivateWiki' => array(
        'default' => false,
    ),
);

function efGetSiteParams( $conf, $wiki ) {
    $site = null;
    $lang = null;
    foreach ( $conf->suffixes as $suffix ) {
        if ( substr( $wiki, -strlen( $suffix ) ) == $suffix ) {
            $site = $suffix;
            $lang = substr( $wiki, 0, -strlen( $suffix ) );
            break;
        }
    }
    return array(
        'suffix' => $site,
        'lang' => $lang,
        'params' => array(
            'lang' => $lang,
            'site' => $site,
            'wiki' => $wiki,
        ),
        'tags' => array(),
    );
}

$wgConf->siteParamsCallback = 'efGetSiteParams';

# The thing that determines the dbname
if ( defined( 'MW_DB' ) ) {
    $wgDBname = MW_DB;
} elseif ( $wmgHostname === 'meta.miraheze.org' ) {
    $wgDBname = 'metawiki';
} elseif ( preg_match( '/^www\.(.*)\.miraheze\.org$/', $wmgHostname, $matches ) ) {
    $wgDBname = $matches[1] . 'wiki';
} elseif ( preg_match( '/^(.*)\.miraheze\.org$/', $wmgHostname, $matches ) ) {
    $wgDBname = $matches[1] . 'wiki';
} elseif ( $search = array_search( 'http://' . $wmgHostname, $wgConf->settings['wgServer'] ) ) {
    $wgDBname = $search;
} elseif ( $search = array_search( 'https://' . $wmgHostname, $wgConf->settings['wgServer'] ) ) {
    $wgDBname = $search;
} else {
    $wgDBname = false;
}

# Initialize dblist (ensure metawiki is always up even in the
# case of a corrupt dblist)
$wgLocalDatabases = array( 'metawiki' );
$wmgDatabaseList = file( "$IP/all.dblist" );

foreach ( $wmgDatabaseList as $wikiLine ) {
    $wikiDB = explode( '|', $wikiLine, 4 );
    list( $DBname, $siteName, $siteLang, $wikiTagList ) = array_pad ( $wikiDB, 4, '' );
    $wgLocalDatabases[] = $DBname;
    $wgConf->settings['wgSitename'][$DBname] = $siteName;
    $wgConf->settings['wgLanguagecode'][$DBname] = $siteLang;

    if ( strpos( $wikiTagList, 'private' ) ) {
        $wgConf->settings['wmgPrivateWiki'][$DBname] = true;
    }
    if ( strpos( $wikiTagList, 'closed' ) ) {
        $wgConf->settings['wmgClosedWiki'][$DBname] = true;
    }
}

require_once( "/srv/mediawiki/config/GlobalLogging.php" );

$wgConf->wikis = $wgLocalDatabases;
$wgConf->extractAllGlobals( $wgDBname );

if ( !in_array( $wgDBname, $wgLocalDatabases ) ) {
    header( "HTTP/1.0 404 Not Found" );
    echo <<<EOF
    Wiki not found!
    <hr>
    nginx - MediaWiki
EOF;
    die( 1 );
}

// TODO: Rights for closed/private wikis
