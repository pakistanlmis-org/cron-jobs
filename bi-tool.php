#!/usr/local/bin/php -q
<?php
$db_host = '';
$db_user = '';
$db_password = '';
$db_name = '';

$strLink = mysql_connect($db_host, $db_user, $db_password);
$strDB = mysql_select_db($db_name, $strLink);

$qrytruncate = "TRUNCATE TABLE bi_table";
mysql_query($qrytruncate);

$qry = "INSERT INTO bi_table(
bi_table.wh_name,
bi_table.hf_type_id,
bi_table.stkname,
bi_table.lvl,
bi_table.itm_name,
bi_table.itm_category,
bi_table.frmindex,
bi_table.wh_obl_a,
bi_table.wh_received,
bi_table.wh_issue_up,
bi_table.wh_adja,
bi_table.wh_adjb,
bi_table.wh_cbl_a,
bi_table.reporting_date,
bi_table.avg_consumption,
bi_table.wh_rank,
bi_table.hf_type_rank,
bi_table.district,
bi_table.PkLocID,
bi_table.province,
bi_table.new,
bi_table.old,
bi_table.MainStakeholder)
(
	SELECT
		`tbl_warehouse`.`wh_name` AS `wh_name`,
		`tbl_warehouse`.`hf_type_id` AS `hf_type_id`,
		`stakeholder`.`stkname` AS `stkname`,
		`stakeholder`.`lvl` AS `lvl`,
		`itminfo_tab`.`itm_name` AS `itm_name`,
		`itminfo_tab`.`itm_category` AS `itm_category`,
		`itminfo_tab`.`frmindex` AS `frmindex`,
		`tbl_wh_data`.`wh_obl_a` AS `wh_obl_a`,
		`tbl_wh_data`.`wh_received` AS `wh_received`,
		`tbl_wh_data`.`wh_issue_up` AS `wh_issue_up`,
		`tbl_wh_data`.`wh_adja` AS `wh_adja`,
		`tbl_wh_data`.`wh_adjb` AS `wh_adjb`,
		`tbl_wh_data`.`wh_cbl_a` AS `wh_cbl_a`,
		`tbl_wh_data`.`RptDate` AS `reporting_date`,
		COALESCE (
			`REPgetConsumptionAVG` (
				'WSPD',
				`tbl_wh_data`.`report_month`,
				`tbl_wh_data`.`report_year`,
				`itminfo_tab`.`itmrec_id`,
				`stakeholder`.`stkid`,
				0,
				`District`.`PkLocID`
			),
			NULL,
			0
		) AS `avg_consumption`,
		`tbl_warehouse`.`wh_rank` AS `wh_rank`,
		`tbl_hf_type_rank`.`hf_type_rank` AS `hf_type_rank`,
		`District`.`LocName` AS `district`,
		`Province`.`PkLocID` AS `PkLocID`,
		`Province`.`LocName` AS `province`,
		0 AS `new`,
		0 AS `old`,
		`main`.`stkname` AS `MainStakeholder`
	FROM
		(
			(
				(
					(
						(
							(
								(
									`tbl_wh_data`
									JOIN `itminfo_tab` ON (
										(
											`tbl_wh_data`.`item_id` = `itminfo_tab`.`itmrec_id`
										)
									)
								)
								JOIN `tbl_warehouse` ON (
									(
										`tbl_wh_data`.`wh_id` = `tbl_warehouse`.`wh_id`
									)
								)
							)
							LEFT JOIN `tbl_hf_type_rank` ON (
								(
									`tbl_warehouse`.`hf_type_id` = `tbl_hf_type_rank`.`hf_type_id`
								)
							)
						)
						JOIN `stakeholder` ON (
							(
								`tbl_warehouse`.`stkofficeid` = `stakeholder`.`stkid`
							)
						)
					)
					JOIN `tbl_locations` `District` ON (
						(
							`tbl_warehouse`.`dist_id` = `District`.`PkLocID`
						)
					)
				)
				JOIN `tbl_locations` `Province` ON (
					(
						`tbl_warehouse`.`prov_id` = `Province`.`PkLocID`
					)
				)
			)
			JOIN `stakeholder` `main` ON (
				(
					`tbl_warehouse`.`stkid` = `main`.`stkid`
				)
			)
		)
	WHERE
		(
			(`stakeholder`.`lvl` = 3)
			AND (
				`tbl_wh_data`.`report_year` IN (2015,2016,2017)
			)
		)
LIMIT 100
)
UNION ALL
	(
		SELECT
			`tbl_warehouse`.`wh_name` AS `wh_name`,
			`tbl_warehouse`.`hf_type_id` AS `hf_type_id`,
			`stakeholder`.`stkname` AS `stkname`,
			`stakeholder`.`lvl` AS `lvl`,
			`itminfo_tab`.`itm_name` AS `itm_name`,
			`itminfo_tab`.`itm_category` AS `itm_category`,
			`itminfo_tab`.`frmindex` AS `frmindex`,
			`tbl_hf_data`.`opening_balance` AS `wh_obl_a`,
			`tbl_hf_data`.`received_balance` AS `wh_received`,
			`tbl_hf_data`.`issue_balance` AS `wh_issue_up`,
			`tbl_hf_data`.`adjustment_positive` AS `wh_adja`,
			`tbl_hf_data`.`adjustment_negative` AS `wh_adjb`,
			`tbl_hf_data`.`closing_balance` AS `wh_cbl_a`,
			`tbl_hf_data`.`reporting_date` AS `reporting_date`,
			`tbl_hf_data`.`avg_consumption` AS `avg_consumption`,
			`tbl_warehouse`.`wh_rank` AS `wh_rank`,
			`tbl_hf_type_rank`.`hf_type_rank` AS `hf_type_rank`,
			`District`.`LocName` AS `district`,
			`Province`.`PkLocID` AS `PkLocID`,
			`Province`.`LocName` AS `province`,
			`tbl_hf_data`.`new` AS `new`,
			`tbl_hf_data`.`old` AS `old`,
			`main`.`stkname` AS `MainStakeholder`
		FROM
			(
				(
					(
						(
							(
								(
									(
										`tbl_hf_data`
										JOIN `itminfo_tab` ON (
											(
												`tbl_hf_data`.`item_id` = `itminfo_tab`.`itm_id`
											)
										)
									)
									JOIN `tbl_warehouse` ON (
										(
											`tbl_hf_data`.`warehouse_id` = `tbl_warehouse`.`wh_id`
										)
									)
								)
								JOIN `tbl_hf_type_rank` ON (
									(
										`tbl_warehouse`.`hf_type_id` = `tbl_hf_type_rank`.`hf_type_id`
									)
								)
							)
							JOIN `stakeholder` ON (
								(
									`tbl_warehouse`.`stkofficeid` = `stakeholder`.`stkid`
								)
							)
						)
						JOIN `tbl_locations` `District` ON (
							(
								`tbl_warehouse`.`dist_id` = `District`.`PkLocID`
							)
						)
					)
					JOIN `tbl_locations` `Province` ON (
						(
							`tbl_warehouse`.`prov_id` = `Province`.`PkLocID`
						)
					)
				)
				JOIN `stakeholder` `main` ON (
					(
						`tbl_warehouse`.`stkid` = `main`.`stkid`
					)
				)
			)
		WHERE
			(
				YEAR (
					`tbl_hf_data`.`reporting_date`
				) IN (2015,2016,2017)
			)
LIMIT 100
	)";
mysql_query($qry);

$msg = "Bi Tool Table has been updated!";
// use wordwrap() if lines are longer than 70 characters
$msg = wordwrap($msg, 150);
// send email
mail("ahussain@ghsc-psm.org,aafzaal@ghsc-psm.org", "BI Tool Table Update", $msg);