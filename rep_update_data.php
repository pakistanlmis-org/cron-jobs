#!/usr/local/bin/php -q
<?php
// DB Connection here

$year = date('Y');
$month = date('m');

$get_all_im_wh = "SELECT
					tbl_warehouse.wh_id,
					wh_user.sysusrrec_id
				FROM
					tbl_warehouse
				INNER JOIN wh_user ON tbl_warehouse.wh_id = wh_user.wh_id
				INNER JOIN sysuser_tab ON wh_user.sysusrrec_id = sysuser_tab.UserID
				WHERE
					tbl_warehouse.is_allowed_im = 1
				AND sysuser_tab.sysusr_type IN (4, 8, 12, 13)";
$get_all_im_wh_res = mysql_query($get_all_im_wh);
while ($row_im = mysql_fetch_array($get_all_im_wh_res))
{

	$user = $row_im['sysusrrec_id'];
	$wh_id = $row_im['wh_id'];
	$qry = "SELECT
				itminfo_tab.itmrec_id,
				itminfo_tab.itm_id
			FROM
				itminfo_tab
			INNER JOIN stakeholder_item ON itminfo_tab.itm_id = stakeholder_item.stk_item
			WHERE
				stakeholder_item.stkid = 1
			AND itminfo_tab.itm_category = 1
			AND itminfo_tab.itm_status = 1
			ORDER BY
				itminfo_tab.frmindex";
	$qryRes = mysql_query($qry);
	while ( $row = mysql_fetch_array($qryRes) )
	{
		$itm_id = $row['itm_id'];
		$itmrec_id = $row['itmrec_id'];
		
		$updateQry = "SELECT REPUpdateData($month, $year, $itm_id, $wh_id, $user, '$itmrec_id')";
		mysql_query($updateQry);
	}
}
$msg = "Carried the closing balances to the current Month";
// use wordwrap() if lines are longer than 70 characters
$msg = wordwrap($msg, 150);
// send email
mail("email","CWH Carried Balances",$msg);