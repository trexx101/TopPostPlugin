<?php

require_once '../../../src/Google_Client.php';
require_once '../../../src/contrib/Google_AnalyticsService.php';
require_once 'storage.php';
require_once 'authHelper.php';

// These must be set with values YOU obtains from the APIs console.
// See the Usage section above for details.
const REDIRECT_URL = 'http://localhost:8080/google-api-php-client/examples/analytics/demo/index.php';
const CLIENT_ID = '920779528356.apps.googleusercontent.com';
const CLIENT_SECRET = 'toYHSG0m1k5OZNWJJEzvcV_a';

// The file name of this page. Used to create various query parameters to
// control script execution.
const THIS_PAGE = 'index.php';

const APP_NAME = 'Google Analytics Sample Application';
const ANALYTICS_SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';


$demoErrors = null;

$authUrl = THIS_PAGE . '?action=auth';
$revokeUrl = THIS_PAGE . '?action=revoke';

$helloAnalyticsDemoUrl = THIS_PAGE . '?demo=hello';
$mgmtApiDemoUrl = THIS_PAGE . '?demo=mgmt';
$coreReportingDemoUrl = THIS_PAGE . '?demo=reporting';

// Build a new client object to work with authorization.
$client = new Google_Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setRedirectUri(REDIRECT_URL);
$client->setApplicationName(APP_NAME);
$client->setScopes(
    array(ANALYTICS_SCOPE));

// Magic. Returns objects from the Analytics Service
// instead of associative arrays.
$client->setUseObjects(true);


// Build a new storage object to handle and store tokens in sessions.
// Create a new storage object to persist the tokens across sessions.
$storage = new apiSessionStorage();


$authHelper = new AuthHelper($client, $storage, THIS_PAGE);

// Main controller logic.

if ($_GET['action'] == 'revoke') {
  $authHelper->revokeToken();

} else if ($_GET['action'] == 'auth' || $_GET['code']) {
  $authHelper->authenticate();

} else {
  $authHelper->setTokenFromStorage();

  if ($authHelper->isAuthorized()) {
    $analytics = new Google_AnalyticsService($client);

    if ($_GET['demo'] == 'hello') {

      // Hello Analytics API Demo.
      require_once 'helloAnalyticsApi.php';

      $demo = new HelloAnalyticsApi($analytics);
      $htmlOutput = $demo->getHtmlOutput();
      $demoError = $demo->getError();

    } else if ($_GET['demo'] == 'mgmt') {

      // Management API Reference Demo.
      require_once 'managementApiReference.php';

      $demo = new ManagementApiReference($analytics);
      $htmlOutput = $demo->getHtmlOutput();
      $demoError = $demo->getError();

    } else if ($_GET['demo'] == 'reporting') {

      // Core Reporting API Reference Demo.
      require_once 'CoreReportingApiReference.php';

      $demo = new coreReportingApiReference($analytics, THIS_PAGE);
      $htmlOutput = $demo->getHtmlOutput($_GET['tableId']);
      $demoError = $demo->getError();
    }
  }

  // The PHP library will try to update the access token
  // (via the refresh token) when an API request is made.
  // So the actual token in apiClient can be different after
  // a require through Google_AnalyticsService is made. Here we
  // make sure whatever the valid token in $service is also
  // persisted into storage.
  $storage->set($client->getAccessToken());
}

// Consolidate errors and make sure they are safe to write.
$errors = $demoError ? $demoError : $authHelper->getError();
$errors = htmlspecialchars($errors, ENT_NOQUOTES);
?>


<!DOCTYPE>
<html>
  <head>
    <title>Google Analytics API v3 Sample Application</title>
  </head>
  <body>
    <h1>Google Analytics API v3 Sample Application</h1>
    <p>This is a sample PHP application that demonstrates how to use the
       Google Analytics API. This sample application contains various
       demonstrations using the Google Analytics
       <a href="http://code.google.com/apis/analytics/docs/mgmt/v3/mgmtGettingStarted.html">
           Management API</a> and
       <a href="http://code.google.com/apis/analytics/docs/gdata/v3/gdataGettingStarted.html">
           Core Reporting API</a>.</p>

    <p>To begin, you must first grant this application access to your
       Google Analytics data.</p>
    <hr>

<?php
  // Print out authorization URL.
  if ($authHelper->isAuthorized()) {
    print "<p><a href='$revokeUrl'>Revoke access</a></p>";
  } else {
    print "<p><a href='$authUrl'>Grant access to Google Analytics data</a></p>";
  }
?>
    <hr>
    <p>Next click which demo you'd like to run.</p>
    <ul>
      <li><a href="<?=$helloAnalyticsDemoUrl?>">Hello Analytics API</a> &ndash;
          Traverse through the
          <a href="http://code.google.com/apis/analytics/docs/mgmt/v3/mgmtGettingStarted.html">
             Management API</a> to get the first profile ID.
          The use this ID with the
          <a href="http://code.google.com/apis/analytics/docs/gdata/v3/gdataGettingStarted.html">
             Core Reporting API</a> to print the top 25
          organic search terms.</li>

      <li><a href="<?=$mgmtApiDemoUrl?>">Management API Reference</a> &ndash;
          Traverse through the
          <a href="http://code.google.com/apis/analytics/docs/mgmt/v3/mgmtGettingStarted.html">
             Management API</a> and print all the important
          information returned from the API for each of the first entities.</li>

      <li><a href="<?=$coreReportingDemoUrl?>">Core Reporting API Reference</a> &ndash;
          Query the <a href="http://code.google.com/apis/analytics/docs/gdata/v3/gdataGettingStarted.html">
             Core Reporting API</a> and print out all the important information
          returned from the API.</li>
    </ul>
    <hr>
<?php
  // Print out errors or results.
  if ($errors) {
    print "<div>There was an error: <br> $errors</div>";
  } else if ($authHelper->isAuthorized()) {
    print "<div>$htmlOutput</div>";
  } 
?>

  </body>
</html>

