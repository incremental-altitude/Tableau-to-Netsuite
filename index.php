<?

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//Server Path
require ('/var/www/vhosts/naturalskincare.com/httpdocs/vendor/autoload.php');
//Web Path
require ('../../vendor/autoload.php');

use Shuchkin\SimpleXLSX;

// Load Up Creds
require_once (dirname(__DIR__).'/tableau-to-netsuite/credentials/credentials.php');

// API Call to Grab Tableau Authentication Token. 

$input_xml ='<tsRequest>
	<credentials name="'.$username.'" password="'.$password.'">
		<site contentUrl="'.$site_url.'" />
	</credentials>
</tsRequest>';

$ch = curl_init();
  curl_setopt( $ch, CURLOPT_URL, $host );
  curl_setopt( $ch, CURLOPT_POST, true );
  curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch, CURLOPT_POSTFIELDS, '<tsRequest>
	<credentials name="'.$username.'" password="'.$password.'">
		<site contentUrl="'.$site_url.'" />
	</credentials>
</tsRequest>' );
  $result = curl_exec($ch);
  curl_close($ch);

        //convert the XML result into array
        $array_data = json_decode(json_encode(simplexml_load_string($result)), true);

      print_r('<pre>');
      print_r($array_data['credentials']['@attributes']['token']);
      print_r('</pre>');

$token =  $array_data['credentials']['@attributes']['token'];



$host2 = 'https://cosmeticsolutionsfl-bieozqsgyuggf5miftz4c3v2mk9yssagqbj.rz-ops.com/api/3.9/sites/aa0b49a6-1ff2-4057-9330-60cdfda28b45/views/88df37cb-43c5-410f-87b0-a1c4545ae2ff/crosstab/excel';


$fileName = 'data.xlsx'; // renaming image
$path = '';  // your saving path
$ch2 = curl_init($host2);
$fp = fopen($path . $fileName, 'wb');
curl_setopt($ch2, CURLOPT_FILE, $fp);
curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
    'X-Tableau-Auth: '.$token));
curl_setopt($ch2, CURLOPT_HEADER, 0);
$result = curl_exec($ch2);
curl_close($ch2);
fclose($fp);


// STEP 3 PARSE XLSX


//echo $start_date;

$file_source = "data.xlsx";

if ( $xlsx = SimpleXLSX::parse($file_source)) {
	// Produce array keys from the array values of 1st array element
	$header_values = $rows = [];
	foreach ( $xlsx->rows() as $k => $r ) {
		if ( $k === 0 ) {
			$header_values = $r;
			continue;
		}
		$rows[] = array_combine( $header_values, $r );
	}
	print_r('<pre>');
	print_r( $rows );
	print_r('</pre>'); 
}

// STAGE 4
// LOOP THROUGH THE ROWS AND PASS THE DATA TO NETSUITE

foreach ($rows as $row){
	
	$internal_id = $row['Custom Reference'];
	$location_name = $row['Location Name'];
	$man_hours = $row['True Man Hours'];
	$crew_size = $row['Avg. Average Crew Size'];
	
	// PUSH to Netsuite
	
	// Include a Add to Netsuite Search, or do we Exlude
	
	require (dirname(__DIR__).'/tableau-to-netsuite/netsuite-update-work-order.php');	
	

}





?>