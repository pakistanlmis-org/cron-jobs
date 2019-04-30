<?php
// DB Conenction here

$qry = "SELECT
			tbl_hf_data.pk_id,
			tbl_hf_data.warehouse_id,
			tbl_hf_data.item_id,
			tbl_hf_data.reporting_date
		FROM
			tbl_hf_data
		WHERE
			tbl_hf_data.is_amc_calculated = 0";
$qryRes = mysql_query($qry);
while ($row = mysql_fetch_array($qryRes)) {
	$pk_id = $row['pk_id'];
	$warehouse_id = $row['warehouse_id'];
	$itm_id = $row['item_id'];
	$reporting_date = $row['reporting_date'];
	
	$updateQry = "UPDATE tbl_hf_data
					SET tbl_hf_data.avg_consumption = REPgetHFAMC (
						'$reporting_date',
						$itm_id,
						$warehouse_id
					),
					tbl_hf_data.is_amc_calculated = 1 
				WHERE
					tbl_hf_data.pk_id = $pk_id ";
	mysql_query($updateQry);
}

$msg = "AMC is Updated";
// use wordwrap() if lines are longer than 70 characters
$msg = wordwrap($msg, 150);
// send email
mail("email","Set health facilities AMC",$msg);