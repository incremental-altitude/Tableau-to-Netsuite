<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require (dirname(__DIR__).'../../vendor/autoload.php');

use Shuchkin\SimpleXLSX;
use NetSuite\NetSuiteService;
use NetSuite\Classes\UpdateRequest;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\WorkOrder;
use NetSuite\Classes\StringCustomField;
use NetSuite\Classes\StringCustomFieldRef;
use NetSuite\Classes\TransactionSearchBasic;
use NetSuite\Classes\SearchStringField;
use NetSuite\Classes\SearchRequest;


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

/*print_r('<pre>');print_r($array_data['credentials']['@attributes']['token']);print_r('</pre>'); */

$token =  $array_data['credentials']['@attributes']['token'];

/*
$host2 = 'https://tableau.rz-ops.com/api/3.9/sites/aa0b49a6-1ff2-4057-9330-60cdfda28b45/views/88df37cb-43c5-410f-87b0-a1c4545ae2ff/crosstab/excel?maxAge=1';*/

/*Using Tableau Netsuite Itegration Workbook, with Product Alalysis View -Last Updated  01-30-2024 */
$host2 = 'https://tableau.rz-ops.com/api/3.9/sites/'.$tableau_site_id.'/views/'.$tableau_view_id.'/crosstab/excel?maxAge=1';


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

$file_source = "data.xlsx";
$rows = array();

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
	echo 'TABLEAU DATA';
	print_r( $rows );
	print_r('</pre>');
}

function searchWorkOrder($service, $params){
extract($params);	
	
/*echo "MISSING INTERNAL ID CHECK";
	echo '<pre>';
	var_dump($params);
	echo '</pre>';	*/
	
$missing_internal_id_count++;	
	
$tran_id = $params['tran_id'];
	
$search = new TransactionSearchBasic();
$needle = new SearchStringField();
$needle->operator = "is";
$needle->searchValue = $tran_id;
$search->tranId = $needle;
$request = new SearchRequest();
$request->searchRecord = $search;
$searchResponse = $service->search($request);
	
$result = $searchResponse->searchResult;
$count = $result->totalRecords;	
	
if ($count != 0){
$records = $result->recordList;	
$record = $searchResponse->searchResult->recordList->record;
$lastRecordKey = end($record);
$internal_id = $lastRecordKey->internalId;
//echo'<pre>';var_dump($searchResponse);echo'</pre>';
}	
	
if (!$searchResponse->searchResult->status->isSuccess) {

    echo "SEARCH WO ERROR";
	echo '<pre>';
	var_dump($searchResponse);
	echo '</pre>';
	
	echo '<br/>'.$internal_id.'<br/>';
	
} else {
	
	$search_success_count++;

    //echo "SEARCH WO FOUND";
	/*echo '<pre>';
	var_dump($searchResponse);
	echo '</pre>'; */ 
	
	//echo '<br/>'.$internal_id.'<br/>';
	$search_result = array(
	'internal_id' => $internal_id,
	'missing_internal_id_count' => $missing_internal_id_count,
	'search_success_count' =>$search_success_count,	
	'tran_id' => $tran_id	
	);
	
	return $search_result;	
}
}

function updateWorkOrder($service, $params){	
	
extract($params);
	/*
echo "UPDATE WO CHECK";
echo '<pre>';
var_dump($params);
echo '</pre>'; */
	
$wo = new WorkOrder();
$wo->internalId = $internal_id;

// Set the CustomValues for Redzone Custom Fields
$man_hours = sprintf($man_hours);
$crew_size = sprintf($crew_size);

//echo $man_hours;
	
$wo_man_hours = new StringCustomFieldRef();
$wo_man_hours->scriptId = 'custbodywo_total_man_hours'; 

$wo_man_hours->value = $man_hours; // Some value from your application

$wo_crew_size = new StringCustomFieldRef();
$wo_crew_size->scriptId = 'custbodycs_wo_crew_size';
$wo_crew_size->value = $crew_size; // Some value from your application

$wo_location_name = new StringCustomFieldRef();
$wo_location_name->scriptId = 'custbodycs_wo_redzone_location_name';
$wo_location_name->value = $location_name; // Some value from your application
	
// Collect all custom fields into an array and add the list of fields to the
// order add request:
$customFields[] = $wo_man_hours;
$customFields[] = $wo_crew_size;
$customFields[] = $wo_location_name;
$wo->customFieldList = new CustomFieldList();
$wo->customFieldList->customField = $customFields;

// Submit the sales order create request
$request = new UpdateRequest();
$request->record = $wo;
$updateResponse = $service->update($request);

if (!$updateResponse->writeResponse->status->isSuccess) {
	
$initial_error_count++;
$initial_error_list[] = $tran_id;
$update_result = array(
    'initial_error_count' => $error_count,
    'initial_error_list' => $error_list);	

    /*echo "INITAL ERROR DETECTED";
	echo "INTIAL ERROR UPDATE RESPONSE";
	echo '<pre>';
	var_dump($updateResponse);
	echo '</pre>'; */

		// Set item Category (Item class - Bulk: Custom Formula = 21 in class list)
		$itemCategory = new RecordRef();
		$itemCategory->internalId = 2;
		$wo->class = $itemCategory;

		// Submit the sales order create request
		$request = new UpdateRequest();
		$request->record = $wo;
		$updateResponse = $service->update($request);

			if (!$updateResponse->writeResponse->status->isSuccess) {

			// Resubmit with Category setting
			$error_count++;
			$error_list[] = $tran_id;
			//echo "ADD ERROR UPDATE RESPONSE";
			echo '<pre>';
			var_dump($updateResponse);
			echo '</pre>'; 

			$update_result = array(
			'error_count' => $error_count,
			'error_list' => $error_list,
			'initial_error_count' =>$initial_error_count,
			'initial_error_list' => $initial_error_list);

			return $update_result;	
			}
			else{
			$success_count++;
			$update_result = array(
			'success_count' => $success_count);
			return $update_result;		
			}
} else {
	
	$success_count++;
	/*echo 'Success Ongoing Count '. $success_count. '<br/>';
	var_dump($wo_man_hours);
	echo "ADD SUCCESS Updated, id" . $updateResponse->writeResponse->baseRef->internalId;
	
	echo '<pre>';
	var_dump($updateResponse);
	echo '</pre>'; */
	$update_result = array(
    'success_count' => $success_count);
	return $update_result;	
}	
}

// STAGE 4
// LOOP THROUGH THE ROWS AND PASS THE DATA TO NETSUITE

$service = new NetSuiteService($config);

// Declare Counts

$count = 0;
$success_count = 0;
$initial_error_count = 0;
$wo_searched_count = 0;
$error_count = 0;
$missing_sync_reference = 0;
$has_internal_id_count = 0;
$missing_internal_id_count = 0;
$search_success_count = 0;
$initial_error_list = array();
$error_list = array();
$duplicate_check = array();

// First We need to fix WO conventions, so loop through array and 

$clean_rows = array();
foreach ($rows as $row){
	
	foreach ($row as $key => $val)
       {

      if ($key == 'Run Id'){
			// Check to make sure WO is not blank
			
			if ($val != ""){
			
			$tran_id = $val;
			//Run the Tran_ID trough String Cleaning Process
			
			$tran_id = strtoupper($tran_id);
			
				if (str_starts_with($tran_id, 'WO')) {
				$tran_id = str_replace(' ', '', $tran_id);
				$val = $tran_id;	
				$row[$key] = $val;
	
				}
				else{

				//echo "<br/>Not Clean : ".$tran_id;		
				$tran_id = str_replace(' ', '', $tran_id);	
				$tran_id = "WO".$tran_id;
				$val = $tran_id;
					

				$row[$key] = $val;

				// Prefix with WO
				}
			}
	  }
		
	//$params['tran_id'] = $row['tran_id'];
	
 }
		
$clean_rows[] = $row;	
	
}

// Next we loop through clean rows to set WO's to lines missing both internal id and wo

$ultra_clean_rows = array();

foreach ($clean_rows as $row){
	
	foreach ($row as $key => $val){	
		
	if ($key == 'Run Id'){
		
	if (!empty($val)){	
		
	$stored_tran_id = $val;		
	//echo 'Setting STORED ID: '.$stored_tran_id.'</br>';		
	
	}
	
	else{
	
	$val = $stored_tran_id;
	$val = str_replace(' ', '', $val);
	$row[$key] = $val;		
	//echo 'MISSING ID! NEW ID IS :'.$stored_tran_id.'</br>';	
		}
	}	
	
	//$stored_tran_id = $val;	
		
	}
	
$ultra_clean_rows[] = $row;	

}

$i = 0;
$sepr = 0;
$sorted_rows = array();
foreach($ultra_clean_rows as $car) {
    $k = $i; $k = ($k > 0 ? $k-1 : 0);
    foreach($car as $key => $value) {
        if($key == "Run Id" && $value != $ultra_clean_rows[$k][$key]) {
            $sepr++;
        }
    }
    $sorted_rows[$sepr][] = $car;
    $i++;
	
}

/*echo 'TESTING SORTED ROWS <pre>';	
var_dump($sorted_rows);
echo '</pre>'; */

$no_duplicates= array();
$duplicates = array();

foreach ($sorted_rows as $sub_array) {
    $wo_count = count($sub_array);
	//echo '<br/>'.$wo_count;
	
	// Process Multiple line-runs
	
	if ($wo_count != 1 ){
	$consolidated_row = array();
		
		    $sum = 0;
		    $consolidated_true_man_hours = array();
	        $consolidated_crew_size = array();
		    $location_name = array();
		    $custom_reference = array();
			
			foreach ($sub_array as $sub_sub_array){
				

				$consolidated_row['Run Id']	= $sub_sub_array['Run Id'];
				// Sum Man Hours
				// Sum True Man Hours
				
				foreach($sub_sub_array as $num => $values) {
					
				if ($num == 'True Man Hours'){
					
				$consolidated_true_man_hours[] = $values;	
					
				}
				if ($num == 'Avg. Average Crew Size'){
					
				$consolidated_crew_size[] = $values;	
					
				}
				if ($num == 'Location Name'){
					
				$location_name[] = $values;	
					
				}
					
				if ($num == 'Custom Reference'){
					
				if ($values !="")
				{$custom_reference[] = $values;
				}
					
				}
				
    			//$consolidated_true_man_hours += intval($values['True Man Hours' ]);
				}
				
				/*$total_true_man_hours = array_sum($consolidated_true_man_hours);
				echo '<pre>';	
				var_dump($total_true_man_hours );
				echo '</pre>';*/

				
			}
		        /*echo 'Man Hours<pre>';	
				var_dump($consolidated_true_man_hours);
				echo '</pre>';
		        echo 'Crew Size<pre>';	
				var_dump($consolidated_crew_size);
				echo '</pre>'; */
		
		        $total_true_man_hours = array_sum($consolidated_true_man_hours);
		        $total_crew_size = array_sum($consolidated_crew_size);
		        $total_crew_size = $total_crew_size/$wo_count;
		        $location_name = reset($location_name);
		        if (!empty($custom_reference)){ 
					$custom_reference = reset($custom_reference);
				}
		
		        $consolidated_row['True Man Hours']	= $total_true_man_hours;
				$consolidated_row['Avg. Average Crew Size']	= $total_crew_size;
		        $consolidated_row['Location Name'] = $location_name;
		        $consolidated_row['Custom Reference'] = $custom_reference;
		
		        /*echo 'TEST MH <pre>';	
				var_dump($total_true_man_hours);
				echo '</pre>';
		        echo 'TEST CS <pre>';	
				var_dump($total_crew_size);
				echo '</pre>';*/
		        
			
			
			//echo 'TEST DUPLICATES: <pre>';print_r($sub_array);echo'<pre>';		
			
		   $duplicates[] = $consolidated_row;
		
				
			} 
	
	else
	// Send to No Duplicates Array
		
	{
	
	foreach ($sub_array as $sub_sub_array)
		
	{
	$no_duplicates[] = $sub_sub_array;	
		
	}	   	
}
}

//echo 'NO DUPES: <pre>';print_r($no_duplicates);echo'<pre>';

//echo 'DUPES: <pre>';print_r($duplicates);echo'<pre>';

$final_rows = array_merge($no_duplicates, $duplicates);

//echo 'TESTING FINAL MERGED ARRAY : <pre>';print_r($final_rows);echo'<pre>'



// Set tran_id check, for those missing both tran_id and missing internal id

// START PROGRAM
foreach ($final_rows as $row) {
	
	
	$params = array(
	'internal_id' => $row['Custom Reference'],
	'location_name' => $row['Location Name'],
	'man_hours' => $row['True Man Hours'],
	'crew_size' => $row['Avg. Average Crew Size'],
	'tran_id' => $row['Run Id'],
	'store_tran_id' => $row['Run Id'],	
	'success_count' => $success_count,
	'initial_error_count' => $initial_error_count,	
	'error_count' => $error_count,
	'missing_internal_id_count' => $missing_internal_id_count,
	'wo_searched_count' => $wo_searched_count,	
	'search_success_count' => $search_success_count,	
	'intial_error_list' => $error_list,	
	'error_list' => $error_list
	);
	
	
if ( empty($row['Custom Reference'] ) || ( $row['Custom Reference'] == "")){
	  

// PUSH to Netsuite
	
// Include a Add to Netsuite Search, or do we Exlude
	
// First check if there is an internal id

//echo 'Custom Reference Check:'. $row['Custom Reference'] .'<br/ >';		
	
	
$wo_searched_count++;	
//echo 'TESTING SEARCH FUCTION - '.$row['Custom Reference'].'<br/>';
	
	
$search_result = searchWorkOrder($service, $params);

extract($search_result);
	
//echo "SEARCH ARRAY TEST:". $search_success_count. '</br>';	

//echo 'Begin Update After Search Success';
$params['internal_id'] = $internal_id;
$update_result = updateWorkOrder($service, $params);
extract($update_result);
	
//echo $success_count;
	

}
else{
	
 //echo 'No Search Needed - '.$row['Custom Reference'].'<br/>';
	
//Search For WO by tranid, so we can get the internal ID to post WO data to.
 $has_internal_id_count++;	
 $update_result = updateWorkOrder($service, $params); 
 extract ($update_result);
 //var_dump($update_result);	
 
}

	$count++;
			
	//include (dirname(__DIR__).'/tableau-to-netsuite/netsuite-update-work-order.php');	
}


echo "Final Sync Count: ".$count.'<br/>';
echo "Missing Internal Id Count: ".$missing_internal_id_count.'<br/>';
echo "Work Order's Searched Count: ".$wo_searched_count. '<br/>';
echo "Search Success Count: ".$search_success_count.'<br/>';
echo "Has Internal Id Count: ".$has_internal_id_count.'<br/>';
echo "Final Success Count: :".$success_count.'<br/>';
echo "Initial Error Count: ".$initial_error_count.'<br/>';
echo "Final Error Count: ".$error_count.'<br/>';
echo "Missing Both WO and Interal ID: ".$missing_sync_reference.'<br/>';


if (!empty($initial_error_list)){
	
echo "Intial Error List <br>";
echo '<table>';
foreach ($initial_error_list as $error){
echo '<tr><td>'.$error.'</td></tr>';
}
echo '</table>';	
}else{
	echo 'No Initial Errors <br/>';
}

if (!empty($error_list)){
	
echo "Error List <br>";
echo '<table>';
foreach ($error_list as $error){
echo '<tr><td>'.$error.'</td></tr>';
}
echo '</table>';	
}else{
	echo 'No Errors </br>';
}




?>