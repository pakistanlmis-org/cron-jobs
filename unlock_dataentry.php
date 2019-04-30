#!/usr/local/bin/php -q
<?php
// DB Connection here

$get_all_wh = "SELECT
	tbl_warehouse.wh_id,
	tbl_warehouse.is_lock_data_entry,
	Max(tbl_wh_data.RptDate) AS lastReported,
	TIMESTAMPDIFF(
		MONTH,
		Max(tbl_wh_data.RptDate),
		CURDATE()
	) AS diff1
FROM
	tbl_warehouse
INNER JOIN wh_user ON tbl_warehouse.wh_id = wh_user.wh_id
INNER JOIN stakeholder ON tbl_warehouse.stkofficeid = stakeholder.stkid
LEFT JOIN tbl_wh_data ON tbl_warehouse.wh_id = tbl_wh_data.wh_id
WHERE
	stakeholder.lvl = 3
AND tbl_warehouse.prov_id = 2
AND tbl_warehouse.stkid = 1
AND is_lock_data_entry = 1
AND tbl_warehouse.is_active = 1
GROUP BY
	tbl_warehouse.wh_id
UNION
	SELECT
		tbl_warehouse.wh_id,
		tbl_warehouse.is_lock_data_entry,
		Max(tbl_hf_data.reporting_date) AS lastReported,
		TIMESTAMPDIFF(
			MONTH,
			Max(tbl_hf_data.reporting_date),
			CURDATE()
		) AS diff1
	FROM
		tbl_warehouse
	INNER JOIN wh_user ON tbl_warehouse.wh_id = wh_user.wh_id
	INNER JOIN stakeholder ON tbl_warehouse.stkofficeid = stakeholder.stkid
	LEFT JOIN tbl_hf_data ON tbl_warehouse.wh_id = tbl_hf_data.warehouse_id
	WHERE
		stakeholder.lvl = 7
	AND tbl_warehouse.prov_id = 2
	AND tbl_warehouse.stkid = 1
	AND is_lock_data_entry = 1
	AND tbl_warehouse.is_active = 1
	GROUP BY
		tbl_warehouse.wh_id";
$get_all_wh_res = mysql_query($get_all_wh);
$wh_ids = array();
while ($row = mysql_fetch_array($get_all_wh_res))
{
	//if($row['diff1'] == 1){
		$wh_ids[] = $row['wh_id'];
	//}	
}

if(count($wh_ids) > 0) {
	$qry = "UPDATE tbl_warehouse
			SET
				is_lock_data_entry = 0
			WHERE
				tbl_warehouse.wh_id IN (" . explode(",", $wh_ids).")";
		mysql_query($qry);	
}

$msg = "Data Entry has been unlocked for PWD Sindh";
mail("email","Unlocked Dataentry",$msg);