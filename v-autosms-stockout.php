#!/usr/local/bin/php -q
<?php
// DB COnnection here

$qry = "SELECT DISTINCT
    CONCAT(
        locations.geo_level_id,
        '-',
        locations.pk_id
    ) AS cellkey,
    users.user_name,
    users.login_id,
    users.cell_number
FROM
    users
INNER JOIN warehouse_users ON warehouse_users.user_id = users.pk_id
INNER JOIN warehouses ON warehouse_users.warehouse_id = warehouses.pk_id
INNER JOIN locations ON warehouses.location_id = locations.pk_id
WHERE
    users.role_id IN (3, 4, 5, 6, 7, 8)
GROUP BY
    locations.geo_level_id,
    locations.pk_id,
    users.role_id";

$result = mysql_query($qry);
$array_users = array();
while ($row = mysql_fetch_array($result)) {
    $array_users[$row['cellkey']] = array(
        'cell' => $row['cell_number'],
        'username' => $row['user_name'],
        'login' => $row['login_id']
    );
}

$day = date("d");
if ($day == '01') {
    $today = date("Y-m-01", strtotime("-2 months"));
    $my = date("M-Y", strtotime("-2 months"));
} else {
    $today = date("Y-m-01", strtotime("-1 months"));
    $my = date("M-Y", strtotime("-1 months"));
}


$qry2 = "SELECT DISTINCT
  prov.location_name AS Province,
  dist.location_name AS District,
  locations.location_name AS UC,
  CONCAT(
    prov.geo_level_id,
    '-',
    prov.pk_id
  ) AS keyprov,
  CONCAT(
    dist.geo_level_id,
    '-',
    dist.pk_id
  ) AS keydist,
  CONCAT(
    locations.geo_level_id,
    '-',
    locations.pk_id
  ) AS keyuc,
  COUNT(
    DISTINCT warehouse_users.warehouse_id
  ) AS total,
  item_pack_sizes.item_name
FROM
  hf_data_master
INNER JOIN warehouse_users ON hf_data_master.warehouse_id = warehouse_users.warehouse_id
INNER JOIN users ON warehouse_users.user_id = users.pk_id
INNER JOIN warehouses ON hf_data_master.warehouse_id = warehouses.pk_id
INNER JOIN stakeholders ON warehouses.stakeholder_office_id = stakeholders.pk_id
INNER JOIN locations ON warehouses.location_id = locations.pk_id
INNER JOIN locations AS dist ON locations.district_id = dist.pk_id
INNER JOIN locations AS prov ON locations.province_id = prov.pk_id
INNER JOIN item_pack_sizes ON hf_data_master.item_pack_size_id = item_pack_sizes.pk_id
INNER JOIN item_activities ON item_activities.item_pack_size_id = item_pack_sizes.pk_id
WHERE
  DATE_FORMAT(
    hf_data_master.reporting_start_date,
    '%Y-%m-%d'
  ) = '$today'
AND hf_data_master.closing_balance = 0
AND stakeholders.geo_level_id = 6
AND item_pack_sizes.item_category_id = 1
AND item_activities.stakeholder_activity_id = 1
AND item_activities.item_pack_size_id <> 46
GROUP BY
  hf_data_master.item_pack_size_id,
  warehouse_users.user_id
ORDER BY
  hf_data_master.item_pack_size_id, 
  warehouse_users.user_id";

$result2 = mysql_query($qry2);
$email_to_users = array();

while ($row2 = mysql_fetch_assoc($result2)) {
    $email_to_users[$row2['keyuc']][$row2['item_name']] += $row2['total'];
    $email_to_users[$row2['keydist']][$row2['item_name']] += $row2['total'];
    $email_to_users[$row2['keyprov']][$row2['item_name']] += $row2['total'];
}

$emails_qry = "SELECT
  alerts.cell_number,
  alerts.stkid,
  alerts.prov_id
FROM
  alerts";

$res_email = mysql_query($emails_qry);
while ($rowe = mysql_fetch_assoc($res_email)) {
    if (!empty($rowe['cell_number'])) {
        $email_list[$rowe['stkid'] . "-" . $rowe['prov_id']][] = $rowe['cell_number'];
    }
}

$url = '';//"http://cbs.zong.com.pk/reachcwsv2/corporatesms.svc?wsdl";
$client = new SoapClient($url, array("trace" => 1, "exception" => 0));
$username = '923125154792';
$password = '38917466';

$array_new = array();
foreach ($email_to_users as $key => $product_id) {

    $array_new[$array_users[$key]['login']][$key] = $product_id;
    //}
}

$count = 1;
foreach ($array_new as $login => $keydata) {

    $key = key($keydata);



    $to = $array_users[$key]['cell'];
    if (!empty($to)) {
        $message = "Dear " . $array_users[$key]['username'] . ", Following number of EPI stores are stock out\r\n";
        foreach ($keydata as $product_id) {
            foreach ($product_id as $name => $total) {
                $message .= $name . ":" . $total . ", ";
                //$cc_list = array_merge($cc_list, $email_list[$product['stkid']."-".$product['prov_id']."-PSM"]);
            }
        }

        $message .= '. Plz take action. \r\n[auto generated SMS]';


        list($s1, $p1) = explode("-", $key);
        $to_list_sum = $cc_list_sum = array();
        $to_list_sum = array($array_users[$key]['cell']);


        if (strpos($key, '2-') !== false) {
            $cc_list_sum = $email_list['all-10'];
            if (is_array($email_list["$s1-$p1"])) {
                $to_list_sum = array_merge($to_list_sum, $email_list["$s1-$p1"]);
            }
            if (is_array($email_list["all-$p1"])) {
                $to_list_sum = array_merge($to_list_sum, $email_list["all-$p1"]);
            }
        } else {
            $cc_list_sum = array("923331512197");
        }
        $to_addresses = array_unique($to_list_sum);
        $cc_addresses = array_unique($cc_list_sum);

        foreach ($to_addresses as $to) {
            //$to = '923331512197';
            $resultQuick = $client->QuickSMS(
                    array('obj_QuickSMS' =>
                        array('loginId' => $username,
                            'loginPassword' => $password,
                            'Destination' => $to,
                            'Mask' => 'LMIS Alert',
                            'Message' => $message,
                            'UniCode' => 0,
                            'ShortCodePrefered' => 'n'
                        )
                    )
            );

            $response = $resultQuick->QuickSMSResult;

            mysql_query("INSERT INTO alerts_log (
alerts_log.`to`,
alerts_log.`subject`,
alerts_log.body,
alerts_log.response, alerts_log.type, alerts_log.interface) VALUES ('$to','LMIS Alert','$message','$response','SMS','CronvLMIS')");
        }

        foreach ($cc_addresses as $cc) {
            if ($count % 20 == 0) {
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

                $response = $resultQuick->QuickSMSResult;

                mysql_query("INSERT INTO alerts_log (
alerts_log.`to`,
alerts_log.`subject`,
alerts_log.body,
alerts_log.response, alerts_log.type, alerts_log.interface) VALUES ('$cc','LMIS Alert','$message','$response','SMS','CronvLMIS')");
            }
        }
        // Clear all addresses and attachments for next loop

        $count++;
    }
}