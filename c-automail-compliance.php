#!/usr/local/bin/php -q
<?php

// Include db connection here

$yy = '2018'; //date("Y");
$mm = '07'; //date("m");
$dd = '01'; //date("d");

$qry = "SELECT
	A.stkname,
	BITotalDistrict (A.prov_id, A.stkid, 'P') total_dist,
	BIReportedDistrict (
		A.prov_id,
		A.stkid,
		$mm,
		$yy,
		'P'
	) rptd_dist,
	BITotalSDPs (
		A.prov_id,
		A.stkid,
		'$yy-$mm-01',
		'P'
	) total_sdps,
	BIReportedSDPs (
		A.prov_id,
		A.stkid,
		'$yy-$mm-01',
		'P'
	) rptd_sdps
FROM
	(
		SELECT DISTINCT
			stakeholder.stkname,
			stakeholder.stkid,
			tbl_warehouse.prov_id
		FROM
			tbl_warehouse
		INNER JOIN stakeholder ON tbl_warehouse.stkid = stakeholder.stkid
		INNER JOIN stakeholder_type ON stakeholder.stk_type_id = stakeholder_type.stk_type_id
		WHERE
			tbl_warehouse.prov_id = 3
		AND stakeholder_type.stk_type_id = 0
		AND stakeholder.stkid <> 94
		ORDER BY
			stakeholder.stkname
	) A";

$result = mysql_query($qry);
$array_dist = array();
while ($row = mysql_fetch_array($result)) {
    $gtotal_dist += $row['total_dist'];
    $gtotal_rdist += $row['rptd_dist'];
    $gtotal_sdps += $row['total_sdps'];
    $gtotal_rsdps += $row['rptd_sdps'];
    
    $array_dist[$row['stkname']] = array(
        'total_dist' => $row['total_dist'],
        'rptd_dist' => $row['rptd_dist'],
        'total_sdps' => $row['total_sdps'],
        'rptd_sdps' => $row['rptd_sdps']
    );
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

    $to = $array_users[$stk]['email'];
    $cc = implode(', ', array_unique($email_list['all-10']));

    $toarr = array();
    $trr = ($gtotal_rdist+$gtotal_rsdps)/($gtotal_dist+$gtotal_sdps)*100;
    //if (!empty($to)) {
        $subject = 'Compliance rate Email format - Khyber Pakhtunkhwa team please review!';
        $message = '<html>
	            <head>
	              <title>' . $subject . '</title>
	            </head>
	            <body>Dear Sir/Madam, <br />The total '.($gtotal_rdist+$gtotal_rsdps).' out of '.($gtotal_dist+$gtotal_sdps).' reporting points in KP has reported for the month of July-2018. The overall reporting rate is '.round($trr).'%. The details are as follows.<br /> <br /><table border=1 cellspacing=0 cellpadding=4><tr><th>S.No</th><th>Stakeholder</th><th>Total districts</th><th>Reported districts</th><th>Districts Compliance</th><th>Total SDPs</th><th>Reported SDPs</th><th>SDPs Compliance</th></tr>';
        $sno1 = 1;
        foreach ($array_dist as $stk=>$dist) {
            $message .= '<tr><td>' . $sno1 . '</td><td>' . $stk . '</td><td>' . $dist['total_dist'] . '</td><td>' . $dist['rptd_dist'] . '</td><td>' . round($dist['rptd_dist']/$dist['total_dist']*100) . '%</td><td>' . $dist['total_sdps'] . '</td><td>' . $dist['rptd_sdps'] . '</td><td>' . round($dist['rptd_sdps']/$dist['total_sdps']*100) . '%</td></tr>';
            $sno1++;
        }
        $message .= '</table> <br />For more detail, please visit http://lmis.gov.pk or click on the following link. <a href=http://c.lmis.gov.pk/application/reports/non_report.php>Non/Reported districts</a><br />In case of any query please email us at support@lmis.gov.pk.<br /><br /><i style="color:#3a3838; font-size:12px">This message was sent by LMIS</i> </body>
        </html>';

        $mail->Subject = $subject;
        $mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
        $mail->MsgHTML($message);
     
        $mail->AddAddress('email', '');        
        $mail->AddCC('email', '');
        

        if (!$mail->Send()) {
            $response = "Mailer Error (" . $mail->ErrorInfo . ")";
        } else {
            $response = "Email Sent";
        }
        // Clear all addresses and attachments for next loop
        $mail->ClearAddresses();
        $mail->ClearCCs();
        
        $toto = implode(', ', array_unique($to));

        $count++;
    //}