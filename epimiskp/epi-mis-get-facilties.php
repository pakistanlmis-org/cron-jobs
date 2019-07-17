#!/usr/local/bin/ea-php56 -q
<?php
set_time_limit(0);

$dbconfig = 'YOUR DB CONNECTION HERE';
$token = 'YOURTOKENHERE';

$url = "http://epimis.cres.pk/API/communication/getFacilities?token=$token";

$ch = curl_init();
// Disable SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
curl_setopt($ch, CURLOPT_URL, $url);
// Execute
$result = curl_exec($ch);
// Closing
curl_close($ch);


$decoded = json_decode($result, true);


$i = 0;
//print_r($decoded);
foreach ($decoded['data'] as $key => $row0) {
    $facility_code = $row0['facility_code'];
    $facility_name = $row0['facility_name'];
    $district = $row0['district'];
    $tehsil_name = $row0['tehsil_name'];

    $uc_name = $row0['uc_name'];
    $epi_centers = $row0['epi_centers'];
    $warehouse_type_name = $row0['warehouse_type_name'];
    $dhis_code = $row0['dhis_code'];
    $population = $row0['population'];
    $target = $row0['target'];

    $qry = "SELECT pk_id FROM warehouses WHERE dhis_code= '$dhis_code'";
    $res = $conn->query($qry);
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $yy = date("Y");
        $wh_id = $row['pk_id'];
        $conn->query("DELETE FROM warehouse_population
WHERE
	warehouse_population.warehouse_id = $wh_id
AND YEAR (
	warehouse_population.estimation_year
) = $yy ");

        $live_birth = $population * 0.0353;
        $preg_women = $population * 0.0410;
        $child_bearing = $population * 0.2200;

        $conn->query("INSERT INTO warehouse_population (warehouse_population.facility_total_pouplation,
warehouse_population.live_births_per_year,
warehouse_population.pregnant_women_per_year,
warehouse_population.women_of_child_bearing_age,
warehouse_population.estimation_year,
warehouse_population.warehouse_id) VALUES ('$population', '$live_birth', '$preg_women', '$child_bearing', '$yy-01-01', $wh_id)");
    } else {
        $data[] = $row0;
    }
}

mail("emailaddresshere", "Missing Facilities", "Reporting data has been updated".var_dump($data));
echo date("d/m/Y h:i:s") . "<br>";
echo "Executed Successfully!";
?>