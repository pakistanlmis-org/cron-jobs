#!/usr/local/bin/php -q
<?php
// DB Connection here

// Get all districts
$qry = "SELECT
	summary_district.pk_id,
	summary_district.item_id,
	summary_district.stakeholder_id,
	summary_district.reporting_date,
	summary_district.province_id,
	summary_district.district_id,
	summary_district.consumption,
	summary_district.avg_consumption,
	summary_district.soh_district_store,
	summary_district.soh_district_lvl,
	summary_district.dist_reporting_rate,
	summary_district.field_reporting_rate,
	summary_district.reporting_rate,
	summary_district.total_health_facilities
FROM
	summary_district
WHERE
	YEAR (
		summary_district.reporting_date
	) = '2018'
AND summary_district.stakeholder_id = 73";
$qryRes = mysql_query($qry);

while ($row = mysql_fetch_array($qryRes)) {
    $prov_id = $row['province_id'];
    $itmId = $row['item_id'];
    list($year, $month, $dd) = explode("-",$row['reporting_date']);
    $stkid = $row['stakeholder_id'];
    $dist_id = $row['district_id'];

    $addSummary = "CALL REPUpdateSummaryDistrict2($prov_id,$dist_id,$stkid, '$itmId', $month, $year);";
    echo $addSummary . '<br>';
    //mysql_query($addSummary);
    //sleep(1);
}

mail("email", 'District Data has been updated', 'District Data has been updated');
