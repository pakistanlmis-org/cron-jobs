<?php

// DB Connection here

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

$today = date("Y-m-01", strtotime("-1 months"));

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
			tbl_hf_data.closing_balance / tbl_hf_data.avg_consumption
		),
		2
	),
	0
) = 0
AND itminfo_tab.itm_category = 1
AND itminfo_tab.itm_id NOT IN (4, 6, 10, 33, 30, 34)
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
	email_persons_list.cell_number,
	email_persons_list.stkid,
	email_persons_list.prov_id,
	email_persons_list.office_name
FROM
	email_persons_list";

$res_email = mysql_query($emails_qry);
while ($rowe = mysql_fetch_assoc($res_email)) {
    $email_list[$rowe['stkid'] . "-" . $rowe['prov_id'] . "-" . $rowe['office_name']][] = $rowe['cell_number'];
}

foreach ($email_list as $list => $list_emails) {
    if ($list == 'all-10-PSM') {
        $cc_list = $list_emails;
    }
}

//shuffle($email_to_users);
//echo "<pre>";
//print_r($email_to_users);
//exit;
$url = '';//"http://cbs.zong.com.pk/reachcwsv2/corporatesms.svc?wsdl";
$client = new SoapClient($url, array("trace" => 1, "exception" => 0));
$username = '923125154792';
$password = '38917466';
//923331519987
$phone_cc = array("923331519984");

$count = 1;
echo "<pre>";
foreach ($email_to_users as $stk => $product_id) {

    //$to      = 'IKhan@ghsc-psm.org, ZJamil@ghsc-psm.org, WMirza@ghsc-psm.org, sikhan@ghsc-psm.org, ASaib@ghsc-psm.org, muhahmed@ghsc-psm.org, AHussain@ghsc-psm.org,aafzaal@ghsc-psm.org,imalik@ghsc-psm.org';
    $to = $array_users[$stk]['cell'];
    list($nn, $dd) = explode("_", $array_users[$stk]['login']);

    $message = "Dear " . $nn . ", Following number of SDPs are stock out in $dd:\r\n";

    foreach ($product_id as $product) {
        //$link = "http://c.lmis.gov.pk/popups/stockout_alerts.php?type=1";
        $message .= $product['itm_name'] . ":" . $product['stock_outs'] . ", ";
        $cc_list = array_merge($cc_list, $email_list[$product['stkid'] . "-" . $product['prov_id'] . "-PSM"]);
    }

    $message .= 'Plz take action. \r\n[auto generated SMS]';

    if (!empty($to)) {
        $resultQuick = $client->QuickSMS(
                array('obj_QuickSMS' =>
                    array('loginId' => $username,
                        'loginPassword' => $password,
                        'Destination' => '923331512197',
                        'Mask' => 'LMIS Alert',
                        'Message' => $message,
                        'UniCode' => 0,
                        'ShortCodePrefered' => 'n'
                    )
                )
        );
        
        print_r($resultQuick);
        

        foreach ($phone_cc as $cc) {
            $resultQuick = $client->QuickSMS(
                    array('obj_QuickSMS' =>
                        array('loginId' => $username,
                            'loginPassword' => $password,
                            'Destination' => $cc,
                            'Mask' => 'LMIS Alert',
                            'Message' => $message,
                            'UniCode' => 0,
                            'ShortCodePrefered' => 'n'
                        )
                    )
            );
        }

        $count++;
    }
    
    if($count == 2){
        break;
    }
}

echo $count;
?>