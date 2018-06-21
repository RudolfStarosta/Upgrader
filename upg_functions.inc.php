<?php
// Definitions used in upgrader.php
include_once 'upg_definitions.inc.php';

// Globals used in upgrader.php
include      'upg_globals.inc.php';

function up_exit(){
  
  // ------------------------------------------------------------------------------------
  //
  // Function:    up_exit()
  // Purpose:     Write HTML document footer information and
  //              exit Upgrader
  // Last change: 180620 by RST
  //
  // ------------------------------------------------------------------------------------
  
  echo <<< EOT

</body>
</html>
EOT;

  exit();
}

function up_header(){
  
  // ------------------------------------------------------------------------------------
  //
  // Function:    up_header()
  // Purpose:     Write HTML document header information
  // Last change: 180620 by RST
  //
  // ------------------------------------------------------------------------------------
  
  echo <<< EOT
<!DOCTYPE html>

<!--

  Main file of the Upgrader application
  
-->

<html lang="en">
<head>
<title>Upgrader
EOT;
  
  echo VERSION . "</title>";
  
  echo <<< EOT
  
</head>
<body>

EOT;
  
}

function up_initialize(){
  
  // ------------------------------------------------------------------------------------
  //
  // Function:    up_initialize()
  // Purpose:     Read yaml file and set global variables
  //              accordingly
  // Last change: 180620 by RST
  //
  // ------------------------------------------------------------------------------------
  
  
  include 'upg_globals.inc.php';
  // Function to read steering YAML file
  // Read steerung data from yaml
  
  $ini_data = yaml_parse_file('ini_upgrader.yml');
  
  if (isset($ini_data['DebugLevel'])){
    $debug = $ini_data['DebugLevel'];
    up_tell(__FUNCTION__, __FILE__, __LINE__, 'Information: DebugLevel found in yaml file.');
  } else {
    $debug = MAX_DEBUG_LEVEL;
    up_tell(__FUNCTION__, __FILE__, __LINE__, 'Warning: No DebugLevel found in yaml file.');
  }

  if ($debug > 1) {
    echo "<pre>";
    var_dump($ini_data);
    echo "</pre>";
    
    foreach($ini_data['Sites'] as $og){
      echo "<pre>";
      echo "Found URL:      " . $og['URL'] . "<br/>";
      echo "      User:     " . $og['User'] . "<br/>";
      echo "      Password: " . $og['Password'] . "<br/>";
      echo "</pre>";
    }
  }  // end debug output

  if ($debug > 5) {
    // First thing to check if strange things happen
    phpinfo();
  }
  
  if ($debug > 2) {
    // Check if yaml is available
    if (extension_loaded('yaml'))
      echo "Upgrader: yaml loaded :) <br/>";
    else
      echo "something is wrong :( <br/>";
  }
  
}

function up_tell($src_function, $src_file, $src_line, $text){
  
  // ------------------------------------------------------------------------------------
  //
  // Function:    up_tell()
  // Purpose:     Write messages enhanced with default information
  // Last change: 180620 by RST
  //
  // ------------------------------------------------------------------------------------
  
  include 'upg_globals.inc.php';

  echo "<pre>";
  echo 'Upgrader / DebugLevel: ' . $debug . '<br/>';
  echo 'Function: ' . $src_function . '<br/>';
  echo 'File:     ' . $src_file . '<br/>';
  echo 'Line:     ' . $src_line . '<br/>';
  echo 'Message:  ' . $text . '<br/>';
  echo "</pre>";

}

function doit(){

// --------------------------------------------------------
// Go through all Sites as given in the initialisation step
// --------------------------------------------------------

foreach($ini_data as $og){

    echo "<pre>";
    echo "Working on: " . $og['URL'] . "<br/>";
    echo "</pre>";

// ----------------
// Login to Joomla!
// ----------------

$url   = $og['URL'];
$uname = $og['User'];
$upswd = $og['Password'];

//
// Login to backend
//
// GET return & name token
//

// Prepare handle
$ch = curl_init();
// URL
curl_setopt($ch, CURLOPT_URL, $url . "/administrator/" );
// Return output of curl_exec() as a string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE );
// Initialize Cookies
curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
// Where cookies are save e.g. after closure of handle
curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
// Name of the file in which cookies are save e.g. after closure of handle
curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
// Exclude header from output
curl_setopt($ch, CURLOPT_HEADER, FALSE );

// Call to login page to get data which are needed to POST lateron
$output = curl_exec($ch);

if ($output <> FALSE)
{
 if ($debug > 0)
 {
   echo "<br/>" . "Got output" . "<br/>";
 }
}
else
{
 $error = curl_error($ch);
 $errno = curl_errno($ch);
 echo '<br/> Call failed :( <br/>' .
 	  'errno: ' . $errno . '<br/>' .
      'error: ' . $error . '<br/>';
 // close curl resource to free up system resources
 curl_close($ch);
 up_exit();
}

if ($debug > 2)
{
    echo "<pre>";
    var_dump($output);
    echo "</pre>";
}

// Retrieve POST data / values
//
// 1st return
//
preg_match_all("(<input type=\"hidden\" name=\"return\" value=\"(.*)\"/>)siU", $output, $matches);
// transform to URL compatible format
$return = urlencode($matches[1][0]);
//
// 2nd name
//
preg_match_all("(<input type=\"hidden\" name=\"(.*)\" value=\"1\" />(.*)</fieldset>)iU", $output, $matches);
$name = urlencode($matches[1][0]);

if ($debug > 2)
{
echo "<br/>";
 echo '$return = ' . $return;
 echo "<br/>";

 echo "<br/>";
 echo '$name = ' . $name;
 echo "<br/>";
 
 echo "<br/>" . "Output from Call to Login page" . "<br/>";
 var_dump($output);
}

//
// Call URL posting all required data to loginlogin
//

$postdata = "username=".urlencode($uname)      .
            "&passwd=" .urlencode($upswd)      .
            "&return=" .$return                .
            "&".$name  ."=1";
curl_setopt($ch, CURLOPT_URL,
		    $url .  "/administrator/index.php?option=com_login&task=login&lang=de-DE");
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);

// POST data to login / create session
$output = curl_exec($ch);

// Get output
curl_setopt($ch, CURLOPT_URL,
 		    $url . "/administrator/index.php");
curl_setopt($ch, CURLOPT_POST, FALSE);
$output = curl_exec($ch);

if ($output <> FALSE)
{
 if ($debug > 2)
 {
	echo "<br/>" . "Logged in succesfully :)" . "<br/>";
 }
}
else
{
 $error = curl_error($ch);
 $errno = curl_errno($ch);
 var_dump($output);
 echo  "<br/>" . "Login failed :(" . "<br/>" .
 	   'errno: '. $errno . ' error: ' . $error . "<br />";
 exit( "Bye.");
}

//
// Check if upgrade is necessary
//

curl_setopt($ch, CURLOPT_URL,
 		    $url . "/administrator/index.php?option=com_joomlaupdate");
$output = curl_exec($ch);

if ($debug > 2)
{
 echo "<br/>" . "Output of Update page" . "<br/>";
 var_dump($output); 
}

$upgrade = strpos($output, "Keine Aktualisierungen");
if ($upgrade)
{
 echo "<br/>";
 echo 'No Upgrade necessary :-) <br />';
 if ($debug > 1)
 {
 echo '$upgrade = ' . $upgrade;
 echo "<br/>";
 }
}
else
{
 echo "<br/>";
 echo 'Upgrade necessary !<br />';
 if ($debug > 1)
 {
 echo '$upgrade = ' . $upgrade;
 echo "<br/>";
 }
}
 
//
// Logout
//
// Retrieve Logout-Code
//
//
preg_match_all("(task=logout&amp;(.*)=1\">)siU", $output, $matches);
// transform to URL compatible format
$logout_code = urlencode($matches[1][0]);
if ($debug > 1)
{
 echo "<br/>";
 echo '$logout_code = ' . $logout_code;
 echo "<br/>";
}

curl_setopt($ch, CURLOPT_URL,
 		   $url . "/administrator/index.php?option=com_login&task=logout&" .
		    $logout_code . "=1");
$output = curl_exec($ch);

if ($debug > 1)
{
 echo "<br/>" . "Output after logout" . "<br/>";
 var_dump($output);
}

// close curl resource to free up system resources
curl_close($ch);

} // end foreach($ini_data as $og)

if ($debug > 0)
{
 echo "<br/>";
 echo 'Finished all sites!';
 echo "<br/>";
}
}
?>
