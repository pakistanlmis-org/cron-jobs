#!/usr/local/bin/php -q
<?php
// DB Connection here
$connc = mysqli_connect($hostnamec, $usernamec, $passwordc, $dbc);

// Get all districts
$qry = "SELECT
	tbl_hf_data.warehouse_id,
	tbl_hf_data.item_id,
	tbl_hf_data.reporting_date
FROM
	tbl_hf_data
WHERE
tbl_hf_data.created_date >= DATE_SUB(NOW(), INTERVAL 61 MINUTE)
OR tbl_hf_data.last_update >= DATE_SUB(NOW(), INTERVAL 61 MINUTE)";
$qryRes = mysql_query($qry);

$summary = '';
while ($row = mysql_fetch_array($qryRes)) {
    $warehouse_id = $row['warehouse_id'];
    $item_id = $row['item_id'];
    $reporting_date = $row['reporting_date'];

    $summary .= "CALL REPUpdateHFTypeFromHF('".$warehouse_id."', '".$item_id."', '".$reporting_date."');
    CALL REPUpdateHFData('".$warehouse_id."', '".$item_id."', '".$reporting_date."');
    CALL REPUpdateDistrictStock('".$warehouse_id."', '".$item_id."', '".$reporting_date."');";
}

if (!$connc->multi_query($summary)) {
    echo "Multi query failed: (" . $connc->errno . ") " . $connc->error;
}

mail("email", 'Summary tables has been updated for last one hour health facility data', 'Summary tables are updated!');
