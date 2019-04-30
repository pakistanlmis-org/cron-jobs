#!/usr/local/bin/php -q
<?php
// DB COnnection here

for ($y = 2018; $y >= 2010; $y--) {
// Get all districts
    $qry = "(
	SELECT
		tbl_wh_data.wh_id,
		tbl_wh_data.item_id,
		tbl_wh_data.report_month,
		tbl_wh_data.report_year
	FROM
		tbl_wh_data
	WHERE
		tbl_wh_data.report_year = $y
	AND tbl_wh_data.wh_id <> 123
)
UNION
	(
		SELECT
			tbl_warehouse.wh_id,
			itminfo_tab.itmrec_id item_id,
			month_year.`month` report_month,
			month_year.`year` report_year
		FROM
			tbl_warehouse
		INNER JOIN stakeholder ON tbl_warehouse.stkofficeid = stakeholder.stkid
		INNER JOIN stakeholder_item ON tbl_warehouse.stkid = stakeholder_item.stkid
		INNER JOIN itminfo_tab ON stakeholder_item.stk_item = itminfo_tab.itm_id,
		month_year
	WHERE
		tbl_warehouse.is_active = 1
	AND stakeholder.lvl = 3
	AND itminfo_tab.itm_category IN (1, 2, 3, 4)
        AND month_year.`year` = $y
	)";
    $qryRes = mysql_query($qry);

    while ($row = mysql_fetch_array($qryRes)) {
        $whId = $row['wh_id'];
        $itmId = $row['item_id'];
        $month = $row['report_month'];
        $year = $row['report_year'];

        $addSummary = "CALL REPUpdateSummaryDistrict($whId, '$itmId', $month, $year)";
        mysql_query($addSummary);
        sleep(1);
    }

    mysql_query("INSERT INTO cron_log_time SET last_run = NOW()");
    mail("email", 'Summary district yearly cron for '.$y, 'Summary district cron has been executed!');
}
