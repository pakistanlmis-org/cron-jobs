#!/usr/local/bin/php -q
<?php
// DB Connection here

$day = date("Y-m-d");

$qry = "INSERT INTO national_stock (
    national_stock.stk_id,
    national_stock.prov_id,
    national_stock.item_id,
    national_stock.tr_date,
    national_stock.quantity,
    national_stock.ref
) SELECT
    tbl_warehouse.stkid,
    tbl_locations.PkLocID,
    itminfo_tab.itm_id,
    DATE_FORMAT(
        tbl_stock_master.TranDate,
        '%Y-%m-%d'
    ) TranDate,
    SUM(tbl_stock_detail.Qty) Qty,
    'issue'
FROM
    tbl_stock_master
INNER JOIN tbl_stock_detail ON tbl_stock_master.PkStockID = tbl_stock_detail.fkStockID
INNER JOIN tbl_warehouse ON tbl_stock_master.WHIDTo = tbl_warehouse.wh_id
INNER JOIN stock_batch ON tbl_stock_detail.BatchID = stock_batch.batch_id
LEFT JOIN tbl_warehouse AS fundingSource ON stock_batch.funding_source = fundingSource.wh_id
INNER JOIN itminfo_tab ON stock_batch.item_id = itminfo_tab.itm_id
INNER JOIN tbl_itemunits ON itminfo_tab.itm_type = tbl_itemunits.UnitType
LEFT JOIN stakeholder_item ON stock_batch.manufacturer = stakeholder_item.stk_id
LEFT JOIN stakeholder ON stakeholder_item.stkid = stakeholder.stkid
LEFT JOIN stakeholder AS stk_ofc ON tbl_warehouse.stkofficeid = stk_ofc.stkid
LEFT JOIN stakeholder AS stk ON tbl_warehouse.stkid = stk.stkid
LEFT JOIN tbl_locations ON tbl_warehouse.prov_id = tbl_locations.PkLocID
WHERE
    DATE_FORMAT(
        tbl_stock_master.TranDate,
        '%Y-%m-%d'
    ) = '$day'
AND stock_batch.funding_source = 6891
AND tbl_stock_master.TranTypeID = 2
AND stock_batch.wh_id = 123
AND tbl_stock_detail.temp = 0
GROUP BY
    tbl_warehouse.stkid,
    tbl_locations.PkLocID,
    itminfo_tab.itm_id,
    DATE_FORMAT(
        tbl_stock_master.TranDate,
        '%Y-%m-%d'
    )
ORDER BY
    TranDate";

$result = mysql_query($qry);

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";
$headers .= "From: feedback@lmis.gov.pk" . "\r\n" .
"Reply-To: feedback@lmis.gov.pk" . "\r\n" .
"X-Mailer: PHP/" . phpversion();

mail("email", "National Stock has been updated", "Query for $day has been executed.",  $headers);