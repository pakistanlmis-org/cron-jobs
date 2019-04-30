#!/usr/local/bin/php -q
<?php
// Db connection here

$qry = "SELECT
        tbl_warehouse.stkid,
        tbl_warehouse.dist_id,
        tbl_warehouse.wh_id,
        sysuser_tab.sysusr_cell,
        sysuser_tab.sysusr_name,
        sysuser_tab.usrlogin_id
    FROM
        tbl_warehouse
    INNER JOIN stakeholder ON tbl_warehouse.stkofficeid = stakeholder.stkid
    INNER JOIN wh_user ON tbl_warehouse.wh_id = wh_user.wh_id
    INNER JOIN sysuser_tab ON sysuser_tab.UserID = wh_user.sysusrrec_id
    WHERE
        stakeholder.lvl = 3
    AND sysuser_tab.sysusr_type <> 23
    GROUP BY
        tbl_warehouse.stkid,
        tbl_warehouse.dist_id
    ORDER BY
        tbl_warehouse.dist_id ASC,
        tbl_warehouse.stkid ASC";
$result = mysql_query($qry);
$array_users = array();
while ($row = mysql_fetch_array($result)) {
    $array_users[$row['stkid'] . "-" . $row['dist_id']] = array(
        'wh_id' => $row['wh_id'],
        'cell' => $row['sysusr_cell'],
        'username' => $row['sysusr_name'],
        'login' => $row['usrlogin_id']
    );
}

$qryextra = "SELECT
	alerts.email_address,
	alerts.cell_number,
	alerts.stkid,
	alerts.prov_id,
	alerts.dist_id,
        alerts.person_name
FROM
	alerts
WHERE
	alerts.dist_id IS NOT NULL";

$resultext = mysql_query($qryextra);
$array_usersext = array();
while ($rowext = mysql_fetch_array($resultext)) {
    $array_usersext[$rowext['stkid'] . "-" . $rowext['dist_id']] = array(
        'email' => $rowext['email_address'],
        'cell' => $rowext['cell_number'],
        'username' => $rowext['person_name']
    );
}

$stk_name_array = array(
    1
);
$day = date("d");
if ($day == '01') {
    $today = date("Y-m-01", strtotime("-2 months"));
    $my = date("M-Y", strtotime("-2 months"));
} else {
    $today = date("Y-m-01", strtotime("-1 months"));
    $my = date("M-Y", strtotime("-1 months"));
}

$qry2 = "SELECT
    tbl_warehouse.prov_id,
    tbl_warehouse.dist_id,
    tbl_warehouse.stkid,
    tbl_hf_data.item_id,
    itminfo_tab.itm_name,
    count(DISTINCT tbl_hf_data.pk_id) AS stock_outs
FROM
    tbl_warehouse
INNER JOIN stakeholder ON stakeholder.stkid = tbl_warehouse.stkofficeid
INNER JOIN tbl_hf_type_rank ON tbl_warehouse.hf_type_id = tbl_hf_type_rank.hf_type_id
INNER JOIN tbl_hf_data ON tbl_warehouse.wh_id = tbl_hf_data.warehouse_id
INNER JOIN tbl_locations ON tbl_warehouse.dist_id = tbl_locations.PkLocID
INNER JOIN tbl_hf_type ON tbl_warehouse.hf_type_id = tbl_hf_type.pk_id
INNER JOIN itminfo_tab ON tbl_hf_data.item_id = itminfo_tab.itm_id
WHERE
    stakeholder.lvl = 7
AND tbl_warehouse.wh_id NOT IN (
    SELECT
        warehouse_status_history.warehouse_id
    FROM
        warehouse_status_history
    INNER JOIN tbl_warehouse ON warehouse_status_history.warehouse_id = tbl_warehouse.wh_id
    WHERE
        warehouse_status_history.reporting_month = '$today'
    AND warehouse_status_history.`status` = 0
)
AND tbl_hf_data.reporting_date = '$today'
AND tbl_hf_type.pk_id NOT IN (5, 2, 3, 9, 6, 7, 8, 12, 10, 11)
AND IFNULL(
    ROUND(
        (
            tbl_hf_data.closing_balance
        ),
        2
    ),
    0
) = 0
AND itminfo_tab.itm_category = 1
AND itminfo_tab.itm_id NOT IN (2,3,4, 6, 10, 33, 30, 34)
AND tbl_warehouse.stkid IN (1)
GROUP BY
    tbl_warehouse.prov_id,
    tbl_warehouse.dist_id,
    tbl_warehouse.stkid,
    tbl_hf_data.item_id
ORDER BY
    tbl_warehouse.prov_id,
    tbl_warehouse.dist_id,
    tbl_warehouse.stkid,
    tbl_hf_data.item_id";

$result2 = mysql_query($qry2);
$email_to_users = array();
while ($row2 = mysql_fetch_assoc($result2)) {
    $email_to_users[$row2['stkid'] . "-" . $row2['dist_id']][$row2['item_id']] = $row2;
}

$emails_qry = "SELECT
  alerts.cell_number,
  alerts.stkid,
  alerts.prov_id
FROM
  alerts";

$res_email = mysql_query($emails_qry);
while ($rowe = mysql_fetch_assoc($res_email)) {
    $email_list[$rowe['stkid'] . "-" . $rowe['prov_id']][] = $rowe['cell_number'];
}

//$url = ''; //"http://cbs.zong.com.pk/reachcwsv2/corporatesms.svc?wsdl";
//$client = new SoapClient($url, array("trace" => 1, "exception" => 0));
$username = '';
$password = '';
//923331519987
//$phone_cc = array("923331512216");
?>
<table width="100%" cellpadding="4" cellspacing="0" border="1">
    <tr>
        <td>To</td>
        <td>Message</td>
    </tr>
    <?php
    $count = 1;
    foreach ($email_to_users as $stk => $product_id) {

        //$to      = 'IKhan@ghsc-psm.org, ZJamil@ghsc-psm.org, WMirza@ghsc-psm.org, sikhan@ghsc-psm.org, ASaib@ghsc-psm.org, muhahmed@ghsc-psm.org, AHussain@ghsc-psm.org,aafzaal@ghsc-psm.org,imalik@ghsc-psm.org';
        $to = $array_users[$stk]['cell'];
        list($nn, $dd) = explode("_", $array_users[$stk]['login']);

        $message = "Dear " . $nn . ", Following number of SDPs are stock out in $dd:\r\n";

        foreach ($product_id as $product) {
            //$link = "http://c.lmis.gov.pk/popups/stockout_alerts.php?type=1";
            $message .= $product['itm_name'] . ":" . $product['stock_outs'] . ", ";
            //$cc_list = array_merge($cc_list, $email_list[$product['stkid'] . "-" . $product['prov_id'] . "-PSM"]);
            $s1 = $product['stkid'];
            $p1 = $product['prov_id'];
        }

        $message .= 'Plz take action. \r\n[auto generated SMS]';

        if (!empty($to)) {

            if (array_key_exists($stk, $array_usersext)) {
                ?>
        <tr>
            <td>extra<?php echo $array_usersext[$stk]['cell']; ?></td>
            <td><?php echo $message; ?></td>
        </tr>
    <?php
            }
            
            ?>
        <tr>
            <td>NOrmal<?php echo $to; ?></td>
            <td><?php echo $message; ?></td>
        </tr>
    <?php
            $cc_list_sum = $email_list['all-10'];
            if (is_array($email_list["$s1-$p1"])) {
                $cc_list_sum = array_merge($cc_list_sum, $email_list["$s1-$p1"]);
            }
            if (is_array($email_list["all-$p1"])) {
                $cc_list_sum = array_merge($cc_list_sum, $email_list["all-$p1"]);
            }

            $phone_cc = array_unique($cc_list_sum);

            foreach ($phone_cc as $cc) {
                //if ($count % 20 == 0) {
                     ?>
        <tr>
            <td>CC<?php echo $cc; ?></td>
            <td><?php echo $message; ?></td>
        </tr>
    <?php
    

//            
                }
            //}

            $count++;
        }
        
}
?>
</table>