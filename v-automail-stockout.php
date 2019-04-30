#!/usr/local/bin/php -q
<?php
// Include PHPMailer.php

// DB Connection here

$qry = "SELECT DISTINCT
    CONCAT(
        locations.geo_level_id,
        '-',
        locations.pk_id
    ) AS emailkey,
    users.user_name,
    users.login_id,
    users.email
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
    $array_users[$row['emailkey']] = array(
        'email' => $row['email'],
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
  alerts.email_address,
  alerts.stkid,
  alerts.prov_id
FROM
  alerts";

$res_email = mysql_query($emails_qry);
while ($rowe = mysql_fetch_assoc($res_email)) {
    $email_list[$rowe['stkid'] . "-" . $rowe['prov_id']][] = $rowe['email_address'];
}

$mail = new PHPMailer();
$mail->IsSMTP(); // telling the class to use SMTP
$mail->Host = "mail.lmis.gov.pk";
$mail->SMTPAuth = true;                  // enable SMTP authentication
$mail->SMTPKeepAlive = true;                  // SMTP connection will not close after each email sent
$mail->Port = 25;                    // set the SMTP port for the GMAIL server
$mail->Username = "feedback@lmis.gov.pk"; // SMTP account username
$mail->Password = "N3#eiY5aK#i2";        // SMTP account password
$mail->SetFrom('feedback@lmis.gov.pk', 'LMIS Feedback');
$mail->AddReplyTo('feedback@lmis.gov.pk', 'LMIS Feedback');

$array_new = array();
    foreach ($email_to_users as $key => $product_id) {
        
            $array_new[$array_users[$key]['login']][$key] = $product_id;
        //}
    }

    $count = 1;
    foreach ($array_new as $login => $keydata) {

$key = key($keydata);

        if ($count % 15 == 0) {
            $mail->SmtpClose();

            $mail = new PHPMailer();
            $mail->IsSMTP(); // telling the class to use SMTP
            $mail->Host = "mail.lmis.gov.pk";
            $mail->SMTPAuth = true;                  // enable SMTP authentication
            $mail->SMTPKeepAlive = true;                  // SMTP connection will not close after each email sent
            $mail->Port = 25;                    // set the SMTP port for the GMAIL server
            $mail->Username = "feedback@lmis.gov.pk"; // SMTP account username
            $mail->Password = "N3#eiY5aK#i2";        // SMTP account password
            $mail->SetFrom('feedback@lmis.gov.pk', 'LMIS Feedback');
            $mail->AddReplyTo('feedback@lmis.gov.pk', 'LMIS Feedback');
        }

        $to = $array_users[$key]['email'];
        if (!empty($to)) {
            $subject = "Stock out alert at " . $array_users[$key]['login']; 
            $message = "<html>
              <body>Dear " . $array_users[$key]['username'] . ", <br />LMIS has identified various stock outs in following number of EPI stores. <br /> <h4>Stockout Table for the month of ".$my."</h4><table border=1 cellspacing=0 cellpadding=4><tr><th>Product</th><th>Number of EPI stores</th></tr>";
foreach($keydata as $product_id){
            foreach ($product_id as $name => $total) {
                $message .= "<tr><td>" . $name . "</td><td>" . $total . "</td></tr>";
                //$cc_list = array_merge($cc_list, $email_list[$product['stkid']."-".$product['prov_id']."-PSM"]);
            }
}

            $message .= "</table><br /> Your username is: <b>" . $array_users[$key]['login'] . "</b> <br /><br />For more detail, please login at http://lmis.gov.pk and click on stock out alert link.<br/><br/>In case of any query please email at support@lmis.gov.pk<br /><br /><i style=\"color:#3a3838; font-size:12px\">This is an auto generated email</i></body>
        </html>";

            $mail->Subject = $subject;
            $mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
            $mail->MsgHTML($message);

        list($s1,$p1) = explode("-", $key);
        $to_list_sum = $cc_list_sum = array();
        $to_list_sum = array($array_users[$key]['email']);
        

if (strpos($key, '2-') !== false) {
    $cc_list_sum = $email_list['all-10'];
        if (is_array($email_list["$s1-$p1"])) {
            $to_list_sum = array_merge($to_list_sum, $email_list["$s1-$p1"]);
        }
        if (is_array($email_list["all-$p1"])) {
            $to_list_sum = array_merge($to_list_sum, $email_list["all-$p1"]);
        }
    } else {
        $cc_list_sum = array("alert@lmis.gov.pk");
    }
        $to_addresses = array_unique($to_list_sum);
        $cc_addresses = array_unique($cc_list_sum);


            //$cc = "alert@lmis.gov.pk";
        foreach ($to_addresses as $to) {
            $mail->AddAddress($to, '');
        }
            

            //if (strpos($key, '4-') !== false || strpos($key, '2-') !== false) {
              //  echo $array_users[$key]['username']."<br>";
            //}
foreach ($cc_addresses as $cc) {
            $mail->AddCC($cc, '');
        }
            
//               if (!$mail->Send()) {
//                   $response = "Mailer Error (" . $mail->ErrorInfo . ")";
//               } else {
//                   $response = "Email Sent";
//               }
            // Clear all addresses and attachments for next loop
            
            $mail->ClearAddresses();
            $mail->ClearCCs();

            $to_e = implode(',', $to_addresses);
            $cc_e = implode(',', $cc_addresses);

        mysql_query("INSERT INTO alerts_log (
            alerts_log.`to`,
            alerts_log.`cc`,
            alerts_log.`subject`, 
            alerts_log.`body`,
            alerts_log.`response`,
            alerts_log.`type`,
            alerts_log.`interface`) VALUES ('".$to_e."','".$cc_e."','".$subject."','".$message."','".$response."','Email','CronvLMIS')") or die("Error".mysql_error()); 

        $count++;
        sleep(2);

        }

            
        
    
    //

}