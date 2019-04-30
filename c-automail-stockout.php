#!/usr/local/bin/php -q
<?php
// Include db connection here

$qry = "SELECT
		tbl_warehouse.stkid,
                tbl_warehouse.prov_id,
		tbl_warehouse.dist_id,
		tbl_warehouse.wh_id,
		sysuser_tab.sysusr_email,
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
    $array_users[$row['stkid'] . "-" . $row['prov_id'] . "-" . $row['dist_id']] = array(
        'wh_id' => $row['wh_id'],
        'email' => $row['sysusr_email'],
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
    $array_usersext[$rowext['stkid'] . "-" . $rowext['prov_id'] . "-" . $rowext['dist_id']] = array(
        'email' => $rowext['email_address'],
        'phone' => $rowext['cell_number'],
        'username' => $rowext['person_name']
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

$qry2 = "SELECT
	tbl_warehouse.prov_id,
	tbl_warehouse.dist_id,
	tbl_warehouse.stkid,
        prov.LocName Province,
        stk.stkname Stakeholder,
	tbl_locations.LocName District,
	tbl_hf_data.item_id,
	itminfo_tab.itm_name,
	count(DISTINCT tbl_hf_data.pk_id) AS stock_outs
FROM
	tbl_warehouse
INNER JOIN stakeholder ON stakeholder.stkid = tbl_warehouse.stkofficeid
INNER JOIN stakeholder stk ON stk.stkid = tbl_warehouse.stkid
INNER JOIN tbl_hf_type_rank ON tbl_warehouse.hf_type_id = tbl_hf_type_rank.hf_type_id
INNER JOIN tbl_hf_data ON tbl_warehouse.wh_id = tbl_hf_data.warehouse_id
INNER JOIN tbl_locations ON tbl_warehouse.dist_id = tbl_locations.PkLocID
INNER JOIN tbl_locations prov ON tbl_warehouse.prov_id = prov.PkLocID
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
AND tbl_warehouse.stkid IN (1,2,7,9,73)
GROUP BY
	tbl_warehouse.prov_id,	
	tbl_warehouse.stkid,
        tbl_warehouse.dist_id,
	tbl_hf_data.item_id
ORDER BY
	tbl_warehouse.prov_id,
        tbl_warehouse.stkid,
	tbl_warehouse.dist_id,	
	tbl_hf_data.item_id";
$result2 = mysql_query($qry2);
//Detail
$email_to_users = array();
//Summary
$email_to_dir = array();
$all_products = array();
while ($row2 = mysql_fetch_assoc($result2)) {
    //Detail
    if(in_array($row2['stkid'], array(7,73)) && $row2['item_id'] == 13) {
        continue;
    }
    $email_to_users[$row2['stkid'] . "-" . $row2['prov_id'] . "-" . $row2['dist_id']][$row2['item_id']] = $row2;
    //Summary
    $email_to_dir[$row2['Province']][$row2['Stakeholder']][$row2['District']][$row2['itm_name']] = $row2['stock_outs'];
    $email_to_dir[$row2['Province']][$row2['Stakeholder']]['Total'][$row2['itm_name']] += $row2['stock_outs'];
    $all_products[$row2['Province']][$row2['Stakeholder']][] = $row2['itm_name'];
}

$emails_qry = "SELECT
	alerts.email_address,
	alerts.stkid,
	alerts.prov_id
FROM
	alerts WHERE level='All'";

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
$mail->Username = ""; // SMTP account username
$mail->Password = "";        // SMTP account password
$mail->SetFrom('', '');
$mail->AddReplyTo('', '');
?>
<table width="100%" cellpadding="4" cellspacing="0" border="1">
    <tr>
        <td>To</td>
        <td>CC</td>
        <td>Subject</td>
        <td>Message</td>
    </tr>
<?php
$count = 1;
foreach ($email_to_users as $stk => $product_id) {

    if ($count % 15 == 0) {
        $mail->SmtpClose();

        $mail = new PHPMailer();
        $mail->IsSMTP(); // telling the class to use SMTP
        $mail->Host = "";
        $mail->SMTPAuth = true;   // enable SMTP authentication
        $mail->SMTPKeepAlive = true;  // SMTP connection will not close after each email sent
        $mail->Port = 25;            // set the SMTP port for the GMAIL server
        $mail->Username = "";       // SMTP account username
        $mail->Password = "";        // SMTP account password
        $mail->SetFrom('', '');
        $mail->AddReplyTo('', '');
    }

    $to = $array_users[$stk]['email'];
    $cc = implode(', ', array_unique($email_list['all-10']));

    $toarr = array();
    if (!empty($to)) {
        $subject = 'Stock out alert at ' . str_replace("_", " ", $array_users[$stk]['login']);
        $message = '<html>
	            <head>
	              <title>' . $subject . '</title>
	            </head>
	            <body>Dear ' . $array_users[$stk]['username'] . ', <br />LMIS has identified various stock outs in following number of health facilities/workers. <br /> <h4>Stockout Table for the month of ' . $my . '</h4><table border=1 cellspacing=0 cellpadding=4><tr><th>S.No</th><th>Product</th><th>Number of SDPs</th></tr>';
        $sno1 = 1;
        foreach ($product_id as $product) {
            $message .= '<tr><td>' . $sno1 . '</td><td>' . $product['itm_name'] . '</td><td>' . $product['stock_outs'] . '</td></tr>';
            $sno1++;
        }
        $message .= '</table> Your username is: <b>' . $array_users[$stk]['login'] . '</b> <br />For more detail, please login to your account and click on Stock out alert link or visit <a href=http://c.lmis.gov.pk/application/reports/field_availibility.php>Field availability report</a><br />In case of any query please email us at support@lmis.gov.pk.<br /><br /><i style="color:#3a3838; font-size:12px">This message was sent by LMIS</i> </body>
        </html>';

//        list($s1, $p1, $d1) = explode("-", $stk);
//
//        $cc_list = $email_list['all-10'];
//        if (is_array($email_list["$s1-$p1"])) {
//            $cc_list = array_merge($cc_list, $email_list["$s1-$p1"]);
//        }
//        if (is_array($email_list["all-$p1"])) {
//            $cc_list = array_merge($cc_list, $email_list["all-$p1"]);
//        }
//        $cc = implode(', ', array_unique($cc_list));

        $mail->Subject = $subject;
        $mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
        $mail->MsgHTML($message);

        $toarr[] = $to;        
        if (array_key_exists($stk, $array_usersext)) {
            if($array_usersext[$stk]['email'] != 'abc@abc.com') {
                $toarr[] = $array_usersext[$stk]['email'];
            }            
        }        
        $to = array_unique($toarr);
        foreach ($to as $toemail) {
            $mail->AddAddress($toemail, '');
        }
        $mail->AddCC($cc, 'LMIS Alert');

//        if (!$mail->Send()) {
//            $response = "Mailer Error (" . $mail->ErrorInfo . ")";
//        } else {
//            $response = "Email Sent";
//        }
        // Clear all addresses and attachments for next loop
        $mail->ClearAddresses();
        $mail->ClearCCs();
        
        $toto = implode(', ', array_unique($to));
        ?>
    <tr>
        <td><?php echo $toto; ?></td>
        <td><?php echo $cc; ?></td>
        <td><?php echo $subject; ?></td>
        <td><?php echo $message; ?></td>
    </tr>
<?php
        $count++;
        //sleep(2);
//
//        if ($count == 20)
//            break;
    }
}
?>
    </table>
<?php

$currentstk = '';
$currentdist = '';
$currecntprov = '';
$countsum = 1;
$mail->SmtpClose();

$mail = new PHPMailer();
$mail->IsSMTP(); // telling the class to use SMTP
$mail->Host = "mail.lmis.gov.pk";
$mail->SMTPAuth = true;                  // enable SMTP authentication
$mail->SMTPKeepAlive = true;                  // SMTP connection will not close after each email sent
$mail->Port = 25;                    // set the SMTP port for the GMAIL server
$mail->Username = ""; // SMTP account username
$mail->Password = "";        // SMTP account password
$mail->SetFrom('', '');
$mail->AddReplyTo('', '');

?>
<table width="100%" cellpadding="4" cellspacing="0" border="1">
    <tr>
        <td>To</td>
        <td>CC</td>
        <td>Subject</td>
        <td>Message</td>
    </tr>
<?php

foreach ($email_to_dir as $province => $stkdata) {
    foreach ($stkdata as $stkholder => $distdata) {

        if ($countsum % 15 == 0) {
            $mail->SmtpClose();

            $mail = new PHPMailer();
            $mail->IsSMTP(); // telling the class to use SMTP
            $mail->Host = "mail.lmis.gov.pk";
            $mail->SMTPAuth = true;                  // enable SMTP authentication
            $mail->SMTPKeepAlive = true;                  // SMTP connection will not close after each email sent
            $mail->Port = 25;                    // set the SMTP port for the GMAIL server
            $mail->Username = ""; // SMTP account username
            $mail->Password = "";        // SMTP account password
            $mail->SetFrom('', '');
            $mail->AddReplyTo('', '');
        }

        $subject = "Stock out alert at $province - $stkholder";
        $message = '<html>
	            <head>
	              <title>' . $subject . '</title>
	            </head>
	            <body>LMIS has identified various stock outs in following number of health facilities/CMWs. <br /> <h4>District wise stockout table for the month of ' . $my . '</h4>';

        $message .= "$province - $stkholder";
        $message .= "<table border=1 cellpadding=4 cellspacing=0>";
        $message .= "<thead>";
        $message .= "<tr><th>S.No</th><th>Districts</th>";
        $cntpro = 1;
        foreach (array_unique($all_products[$province][$stkholder]) as $item) {
            $message .= "<th>$item</th>";
            ++$cntpro;
        }
        //$cntpro = $cntpro-1;
        $message .= "</tr>";
        $message .= "</thead>";
        $message .= "<tbody>";
        //echo "<tr><td colspan=$cntpro>Province: " . $province . " and Stakeholder: " . $stkholder . "</td></tr>";
        $sno = 1;
        foreach ($distdata as $district => $itemdata) {
            if ($district != 'Total') {
                $message .= "<tr><td>$sno</td><td>$district</td>";
                foreach (array_unique($all_products[$province][$stkholder]) as $item) {
                    $message .= "<td align=right>" . (!empty($itemdata[$item]) ? $itemdata[$item] : '-') . "</td>";
                }
                $message .= "</tr>";
                $sno++;
            }
        }
        $message .= "</tbody>";

        foreach ($distdata as $district => $itemdata) {
            if ($district == 'Total') {
                $message .= "<tfoot>";
                $message .= "<tr><th colspan=2>$district</th>";
                foreach (array_unique($all_products[$province][$stkholder]) as $item) {
                    $message .= "<th align=right>" . (!empty($itemdata[$item]) ? $itemdata[$item] : '-') . "</th>";
                }
                $message .= "</tr>";
                $message .= "</tfoot>";
            }
        }

        $message .= "</table>";

        $message .= 'In case of any query please email us at support@lmis.gov.pk.<br /><br /><i style="color:#3a3838; font-size:12px">This message was sent by LMIS</i> </body>
        </html>';

        $emails_qry_sum = "SELECT
	alerts.email_address,
	alerts.stkid,
	alerts.prov_id,
	IFNULL(stakeholder.stkname, 'all') stkname,
	tbl_locations.LocName prov
FROM
	alerts
LEFT JOIN stakeholder ON alerts.stkid = stakeholder.stkid
INNER JOIN tbl_locations ON alerts.prov_id = tbl_locations.PkLocID
WHERE alerts.`level` <> 'District'";

        $res_email_sum = mysql_query($emails_qry_sum);
        while ($rowe_sum = mysql_fetch_assoc($res_email_sum)) {
            $email_list_sum[$rowe_sum['stkname'] . "-" . $rowe_sum['prov']][] = $rowe_sum['email_address'];
        }
        $s1 = $stkholder;
        $p1 = $province;
        $to_list_sum = $cc_list_sum = array();
        $cc_list_sum = $email_list_sum['all-National'];

        if (is_array($email_list_sum["$s1-$p1"])) {
            $to_list_sum = array_merge($to_list_sum, $email_list_sum["$s1-$p1"]);
        }
        if (is_array($email_list_sum["all-$p1"])) {
            $to_list_sum = array_merge($to_list_sum, $email_list_sum["all-$p1"]);
        }
        $to = array_unique($to_list_sum);
        $cc = array_unique($cc_list_sum);

        $mail->Subject = $subject;
        $mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
        $mail->MsgHTML($message);

        foreach ($to as $toemail) {
            $mail->AddAddress($toemail, '');
        }
        foreach ($cc as $ccemail) {
            $mail->AddCC($ccemail, '');
        }

//        if (!$mail->Send()) {
//            $response = "Mailer Error (" . $mail->ErrorInfo . ")";
//        } else {
//            $response = "Email Sent";
//        }
        // Clear all addresses and attachments for next loop
        $mail->ClearAddresses();
        $mail->ClearCCs();

        $tos = implode(', ', array_unique($to));
        $ccs = implode(', ', array_unique($cc));

                ?>
    <tr>
        <td><?php echo $tos; ?></td>
        <td><?php echo $ccs; ?></td>
        <td><?php echo $subject; ?></td>
        <td><?php echo $message; ?></td>
    </tr>
<?php

//        mysql_query("INSERT INTO alerts_log (
//alerts_log.`to`,
//alerts_log.cc,
//alerts_log.`subject`,
//alerts_log.body,
//alerts_log.response,alerts_log.type,alerts_log.interface ) VALUES ('$tos','$ccs','$subject','$message','$response','Email','CroncLMIS')");

        $countsum++;
        //sleep(2);
    }
} 
?>
</table> 