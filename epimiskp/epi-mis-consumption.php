#!/usr/local/bin/ea-php56 -q
<?php
set_time_limit(0);

$dbconfig = 'YOUR DB CONNECTION HERE';
$token = 'YOURTOKENHERE';

$qry_select_dis = "SELECT
            locations.pk_id, 
            locations.geo_level_id,
            locations.dhis_code
            FROM
            locations
            WHERE
            locations.geo_level_id = 4 AND
            locations.province_id = 3 ";
$row_select_dis = $conn->query($qry_select_dis);

while ($res_select_dis = $row_select_dis->fetch_assoc()) {
    $date = date("Y-m-d", strtotime("-1 day"));
    $dhis_code = $res_select_dis['dhis_code'];

    $url = "http://epimis.cres.pk/API/communication/consumption?date=$date&token=$token&district_code=$dhis_code";
    //echo $url;

    $ch = curl_init();
// Disable SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Will return the response, if false it print the response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Set the url
    curl_setopt($ch, CURLOPT_URL, $url);
// Execute
    $result = curl_exec($ch);
// Closing
    curl_close($ch);


    $decoded = json_decode($result, true);


    $i = 0;
    //print_r($decoded);
    foreach ($decoded['data'] as $key1 => $row1) {

        foreach ($row1 as $key => $row0) {
            $hf_code = $row0['hf_code'];
            $item_code = $row0['item_code'];
            $opening = $row0['opening'];
            $received = $row0['received'];
            $issue = json_encode($row0['issue']);
            $closing = $row0['closing'];
            $data_entry_month = $row0['reporting_month_year'];
            $last_update = $row0['last_update'];
            $created_date = $row0['created_date'];

            $values = "'$url','$data_entry_month','$hf_code','$item_code','$opening','$received','$issue','$closing','$created_date','$last_update'";
            $hash = md5($values);

            $insert = "INSERT INTO epi_mis_kp_consumption_interfacing_data (url,reporting_month_year,hf_code,item_code,opening,received,issue,closing,created_date,last_update,`hash`,is_processed) VALUES ($values,'$hash','0')";

            $conn->query($insert);
        }
    }

    sleep(1);
}

$qry_select_dis = "SELECT
    epi_mis_kp_consumption_interfacing_data.pk_id,
	epi_mis_kp_consumption_interfacing_data.reporting_month_year,
	epi_mis_kp_consumption_interfacing_data.hf_code,
	epi_mis_kp_consumption_interfacing_data.item_code,
	epi_mis_kp_consumption_interfacing_data.opening,
	epi_mis_kp_consumption_interfacing_data.received,
	epi_mis_kp_consumption_interfacing_data.issue,
	epi_mis_kp_consumption_interfacing_data.closing,
	epi_mis_kp_consumption_interfacing_data.created_date,
	epi_mis_kp_consumption_interfacing_data.last_update
FROM
	epi_mis_kp_consumption_interfacing_data
WHERE
	epi_mis_kp_consumption_interfacing_data.is_processed = 0
        ";
//AND DATE_FORMAT(epi_mis_kp_consumption_interfacing_data.created_date,'%Y-%m-%d') = '$date'
$row_select_dis = $conn->query($qry_select_dis);

while ($row0 = $row_select_dis->fetch_assoc()) {

    $primary_id = $row0['pk_id'];
    $hf_code = $row0['hf_code'];
    $item_code = $row0['item_code'];
    $opening = $row0['opening'];
    $received = $row0['received'];

    $closing = $row0['closing'];
    $data_entry_month = $row0['reporting_month_year'];
    $last_update = $row0['last_update'];
    $created_date = $row0['created_date'];


    // item ID
    $qry_select2 = "SELECT
            item_pack_sizes.pk_id
            FROM
            item_mapping
            INNER JOIN item_pack_sizes ON item_pack_sizes.pk_id = item_mapping.item_pack_size_id
            WHERE
            item_mapping.epi_mis_item_id = '$item_code'";
    //echo $qry_select2 . "<br>";
    $row_select2 = $conn->query($qry_select2);

    $res_select2 = $row_select2->fetch_assoc();

    // item ID
    $item_id = $res_select2['pk_id'];
    // warehouse ID

    $str_qry_uc = "SELECT
                        warehouses.pk_id
                        FROM
                        warehouses
                        WHERE
                        warehouses.dhis_code = '$hf_code'";


    $row_uc = $conn->query($str_qry_uc);

    $res_uc = $row_uc->fetch_assoc();

    $warehouse_id = $res_uc['pk_id'];

    $qry_select = "SELECT
            hf_data_master.pk_id
            
            FROM
            hf_data_master
            WHERE
            hf_data_master.warehouse_id = '$warehouse_id'"
            . " AND hf_data_master.item_pack_size_id = '$item_id'"
            . "AND Date_Format(hf_data_master.reporting_start_date,'%Y-%m-%d') = '$data_entry_month-01'";

    $row_select = $conn->query($qry_select);
    $res_select = $row_select->fetch_assoc();

    if ($row_select->num_rows > 0) {
        $master_iid = $res_select['pk_id'];
        $conn->query("DELETE FROM hf_data_master WHERE hf_data_master.pk_id = " . $master_iid);
        $conn->query("DELETE FROM hf_data_detail WHERE hf_data_detail.hf_data_master_id = " . $master_iid);
        $res_select = '';
    }

    if (empty($res_select)) {
        if ($item_code == 2 || $item_code == 7 || $item_code == 9 || $item_code == 12 || $item_code == 13 || $item_code == 15 || $item_code == 16 || $item_code == 14) {
            $issue1 = json_decode($row0['issue']);
            $str_qry1 = "INSERT INTO hf_data_master
                    (hf_data_master.opening_balance,
                    hf_data_master.received_balance,
                    hf_data_master.issue_balance,
                    hf_data_master.closing_balance,
                    hf_data_master.reporting_start_date,
                    hf_data_master.item_pack_size_id,
                    hf_data_master.warehouse_id,
                    hf_data_master.created_by,
                    hf_data_master.created_date,
                    hf_data_master.modified_date,
                    hf_data_master.modified_by
                    )
                    VALUES ('$opening', '$received','$issue1','$closing','$data_entry_month-01','$item_id','$warehouse_id','1','$created_date','$last_update','1')";
            $conn->query($str_qry1);
            $last_id = $conn->insert_id;
            
        } else {
            $str_qry1 = "INSERT INTO hf_data_master
                    (hf_data_master.opening_balance,
                    hf_data_master.received_balance,
                    hf_data_master.closing_balance,
                    hf_data_master.reporting_start_date,
                    hf_data_master.item_pack_size_id,
                    hf_data_master.warehouse_id,
                    hf_data_master.created_by,
                    hf_data_master.created_date,
                    hf_data_master.modified_date,
                    hf_data_master.modified_by
                    )
                    VALUES ('$opening', '$received','$closing','$data_entry_month-01','$item_id','$warehouse_id','1','$created_date','$last_update','1')";


            $conn->query($str_qry1);

            $last_id = $conn->insert_id;
            $issue = json_decode($row0['issue']);
            $row2_1 = $issue;
            $issue_balance = 0;
            if ($item_code == 1) {
                $i_code = 'bcg';
                $dose_n = '1';
                $row2 = $row2_1[0];
                $fixed_0to11m_male = $row2[$i_code . '_fixed_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_fixed_0to11m_male'];
                $fixed_0to11m_female = $row2[$i_code . '_fixed_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_fixed_0to11m_female'];
                $fixed_12to23m_male = $row2[$i_code . '_fixed_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_fixed_12to23m_male'];
                $fixed_12to23m_female = $row2[$i_code . '_fixed_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_fixed_12to23m_female'];
                $fixed_2yearsabove_male = $row2[$i_code . '_fixed_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_fixed_2yearsabove_male'];
                $fixed_2yearsabove_female = $row2[$i_code . '_fixed_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_fixed_2yearsabove_female'];

                $outreach_0to11m_male = $row2[$i_code . '_outreach_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_outreach_0to11m_male'];
                $outreach_0to11m_female = $row2[$i_code . '_outreach_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_outreach_0to11m_female'];
                $outreach_12to23m_male = $row2[$i_code . '_outreach_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_outreach_12to23m_male'];

                $outreach_12to23m_female = $row2[$i_code . '_outreach_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_outreach_12to23m_female'];
                $outreach_2yearsabove_male = $row2[$i_code . '_outreach_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_outreach_2yearsabove_male'];
                $outreach_2yearsabove_female = $row2[$i_code . '_outreach_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_outreach_2yearsabove_female'];

                $mobile_0to11m_male = $row2[$i_code . '_mobile_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_mobile_0to11m_male'];
                $mobile_0to11m_female = $row2[$i_code . '_mobile_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_mobile_0to11m_female'];
                $mobile_12to23m_male = $row2[$i_code . '_mobile_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_mobile_12to23m_male'];


                $mobile_12to23m_female = $row2[$i_code . '_mobile_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_mobile_12to23m_female'];
                $mobile_2yearsabove_male = $row2[$i_code . '_mobile_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_mobile_2yearsabove_male'];
                $mobile_2yearsabove_female = $row2[$i_code . '_mobile_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_mobile_2yearsabove_female'];


                $healthhouse_0to11m_male = $row2[$i_code . '_healthhouse_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_healthhouse_0to11m_male'];
                $healthhouse_0to11m_female = $row2[$i_code . '_healthhouse_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_healthhouse_0to11m_female'];

                $healthhouse_12to23m_male = $row2[$i_code . '_healthhouse_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_healthhouse_12to23m_male'];
                $healthhouse_12to23m_female = $row2[$i_code . '_healthhouse_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_healthhouse_12to23m_female'];

                $healthhouse_2yearsabove_male = $row2[$i_code . '_healthhouse_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_healthhouse_2yearsabove_male'];
                $healthhouse_2yearsabove_female = $row2[$i_code . '_healthhouse_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_healthhouse_2yearsabove_female'];

                $dose_no = $row2['dose_no'];
                $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_0to11m_male', '$fixed_0to11m_female', '$outreach_0to11m_male','$outreach_0to11m_female','$mobile_0to11m_male','$mobile_0to11m_female','$healthhouse_0to11m_male','$healthhouse_0to11m_female',160,1,$last_id,'1','$created_date','1','$last_update')";

                $conn->query($str_qry2);

                $str_qry3 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_12to23m_male', '$fixed_12to23m_female', '$outreach_12to23m_male','$outreach_12to23m_female','$mobile_12to23m_male','$mobile_12to23m_female','$healthhouse_12to23m_male','$healthhouse_12to23m_female',161,1,$last_id,'1','$created_date','1','$last_update')";

                $conn->query($str_qry3);

                $str_qry4 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_2yearsabove_male', '$fixed_2yearsabove_female', '$outreach_2yearsabove_male','$outreach_2yearsabove_female','$mobile_2yearsabove_male','$mobile_2yearsabove_female','$healthhouse_2yearsabove_male','$healthhouse_2yearsabove_female',162,1,$last_id,'1','$created_date','1','$last_update')";

                $conn->query($str_qry4);
                $issue_balance = $fixed_0to11m_male + $fixed_0to11m_female +
                        $fixed_12to23m_male +
                        $fixed_12to23m_female +
                        $fixed_2yearsabove_male +
                        $fixed_2yearsabove_female +
                        $outreach_0to11m_male +
                        $outreach_0to11m_female +
                        $outreach_12to23m_male +
                        $outreach_12to23m_female +
                        $outreach_2yearsabove_male +
                        $outreach_2yearsabove_female +
                        $mobile_0to11m_male +
                        $mobile_0to11m_female +
                        $mobile_12to23m_male +
                        $mobile_12to23m_female +
                        $mobile_2yearsabove_male +
                        $mobile_2yearsabove_female +
                        $healthhouse_0to11m_male +
                        $healthhouse_0to11m_female +
                        $healthhouse_12to23m_male +
                        $healthhouse_12to23m_female +
                        $healthhouse_2yearsabove_male +
                        $healthhouse_2yearsabove_female;


                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balance' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            } else if ($item_code == 3) {

                $issue_balance1 = 0;
                $dose1 = array('0' => '0', '1' => '1', '2' => '2', '3' => '3');
                $i_code = 'opv';

                foreach ($dose1 as $val) {

                    $row2 = $row2_1[$val];
                    $fixed_0to11m_male = $row2[$i_code . $val . '_fixed_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_male'];

                    $fixed_0to11m_female = $row2[$i_code . $val . '_fixed_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_female'];
                    $fixed_12to23m_male = $row2[$i_code . $val . '_fixed_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_male'];
                    $fixed_12to23m_female = $row2[$i_code . $val . '_fixed_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_female'];
                    $fixed_2yearsabove_male = $row2[$i_code . $val . '_fixed_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_male'];
                    $fixed_2yearsabove_female = $row2[$i_code . $val . '_fixed_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_female'];

                    $outreach_0to11m_male = $row2[$i_code . $val . '_outreach_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_male'];
                    $outreach_0to11m_female = $row2[$i_code . $val . '_outreach_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_female'];
                    $outreach_12to23m_male = $row2[$i_code . $val . '_outreach_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_male'];

                    $outreach_12to23m_female = $row2[$i_code . $val . '_outreach_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_female'];
                    $outreach_2yearsabove_male = $row2[$i_code . $val . '_outreach_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_male'];
                    $outreach_2yearsabove_female = $row2[$i_code . $val . '_outreach_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_female'];

                    $mobile_0to11m_male = $row2[$i_code . $val . '_mobile_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_male'];
                    $mobile_0to11m_female = $row2[$i_code . $val . '_mobile_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_female'];
                    $mobile_12to23m_male = $row2[$i_code . $val . '_mobile_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_male'];


                    $mobile_12to23m_female = $row2[$i_code . $val . '_mobile_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_female'];
                    $mobile_2yearsabove_male = $row2[$i_code . $val . '_mobile_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_male'];
                    $mobile_2yearsabove_female = $row2[$i_code . $val . '_mobile_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_female'];


                    $healthhouse_0to11m_male = $row2[$i_code . $val . '_healthhouse_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_male'];
                    $healthhouse_0to11m_female = $row2[$i_code . $val . '_healthhouse_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_female'];

                    $healthhouse_12to23m_male = $row2[$i_code . $val . '_healthhouse_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_male'];
                    $healthhouse_12to23m_female = $row2[$i_code . $val . '_healthhouse_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_female'];

                    $healthhouse_2yearsabove_male = $row2[$i_code . $val . '_healthhouse_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_male'];
                    $healthhouse_2yearsabove_female = $row2[$i_code . $val . '_healthhouse_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_female'];

                    $dose_no = $row2['dose_no'];
                    $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_0to11m_male', '$fixed_0to11m_female', '$outreach_0to11m_male','$outreach_0to11m_female','$mobile_0to11m_male','$mobile_0to11m_female','$healthhouse_0to11m_male','$healthhouse_0to11m_female',160,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry2);

                    $str_qry3 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_12to23m_male', '$fixed_12to23m_female', '$outreach_12to23m_male','$outreach_12to23m_female','$mobile_12to23m_male','$mobile_12to23m_female','$healthhouse_12to23m_male','$healthhouse_12to23m_female',161,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry3);

                    $str_qry4 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            )
                            VALUES ('$fixed_2yearsabove_male', '$fixed_2yearsabove_female', '$outreach_2yearsabove_male','$outreach_2yearsabove_female','$mobile_2yearsabove_male','$mobile_2yearsabove_female','$healthhouse_2yearsabove_male','$healthhouse_2yearsabove_female',162,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry4);
                    $issue_balance1 += $fixed_0to11m_male + $fixed_0to11m_female +
                            $fixed_12to23m_male +
                            $fixed_12to23m_female +
                            $fixed_2yearsabove_male +
                            $fixed_2yearsabove_female +
                            $outreach_0to11m_male +
                            $outreach_0to11m_female +
                            $outreach_12to23m_male +
                            $outreach_12to23m_female +
                            $outreach_2yearsabove_male +
                            $outreach_2yearsabove_female +
                            $mobile_0to11m_male +
                            $mobile_0to11m_female +
                            $mobile_12to23m_male +
                            $mobile_12to23m_female +
                            $mobile_2yearsabove_male +
                            $mobile_2yearsabove_female +
                            $healthhouse_0to11m_male +
                            $healthhouse_0to11m_female +
                            $healthhouse_12to23m_male +
                            $healthhouse_12to23m_female +
                            $healthhouse_2yearsabove_male +
                            $healthhouse_2yearsabove_female;
                }
                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balance1' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            } else if ($item_code == 4) {
                $issue_balancep = 0;
                $dosep = array('0' => '1', '1' => '2', '2' => '3');
                $i_code = 'penta';
                foreach ($dosep as $key => $val) {
                    $row2 = $row2_1[$key];
                    $fixed_0to11m_male = $row2[$i_code . $val . '_fixed_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_male'];

                    $fixed_0to11m_female = $row2[$i_code . $val . '_fixed_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_female'];
                    $fixed_12to23m_male = $row2[$i_code . $val . '_fixed_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_male'];
                    $fixed_12to23m_female = $row2[$i_code . $val . '_fixed_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_female'];
                    $fixed_2yearsabove_male = $row2[$i_code . $val . '_fixed_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_male'];
                    $fixed_2yearsabove_female = $row2[$i_code . $val . '_fixed_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_female'];

                    $outreach_0to11m_male = $row2[$i_code . $val . '_outreach_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_male'];
                    $outreach_0to11m_female = $row2[$i_code . $val . '_outreach_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_female'];
                    $outreach_12to23m_male = $row2[$i_code . $val . '_outreach_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_male'];

                    $outreach_12to23m_female = $row2[$i_code . $val . '_outreach_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_female'];
                    $outreach_2yearsabove_male = $row2[$i_code . $val . '_outreach_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_male'];
                    $outreach_2yearsabove_female = $row2[$i_code . $val . '_outreach_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_female'];

                    $mobile_0to11m_male = $row2[$i_code . $val . '_mobile_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_male'];
                    $mobile_0to11m_female = $row2[$i_code . $val . '_mobile_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_female'];
                    $mobile_12to23m_male = $row2[$i_code . $val . '_mobile_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_male'];


                    $mobile_12to23m_female = $row2[$i_code . $val . '_mobile_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_female'];
                    $mobile_2yearsabove_male = $row2[$i_code . $val . '_mobile_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_male'];
                    $mobile_2yearsabove_female = $row2[$i_code . $val . '_mobile_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_female'];


                    $healthhouse_0to11m_male = $row2[$i_code . $val . '_healthhouse_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_male'];
                    $healthhouse_0to11m_female = $row2[$i_code . $val . '_healthhouse_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_female'];

                    $healthhouse_12to23m_male = $row2[$i_code . $val . '_healthhouse_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_male'];
                    $healthhouse_12to23m_female = $row2[$i_code . $val . '_healthhouse_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_female'];

                    $healthhouse_2yearsabove_male = $row2[$i_code . $val . '_healthhouse_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_male'];
                    $healthhouse_2yearsabove_female = $row2[$i_code . $val . '_healthhouse_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_female'];

                    $dose_no = $row2['dose_no'];
                    $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date)
                                VALUES ('$fixed_0to11m_male', '$fixed_0to11m_female', '$outreach_0to11m_male','$outreach_0to11m_female','$mobile_0to11m_male','$mobile_0to11m_female','$healthhouse_0to11m_male','$healthhouse_0to11m_female',160,$val,$last_id,'1','$created_date','1','$last_update')";
                    $conn->query($str_qry2);

                    $str_qry3 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_12to23m_male', '$fixed_12to23m_female', '$outreach_12to23m_male','$outreach_12to23m_female','$mobile_12to23m_male','$mobile_12to23m_female','$healthhouse_12to23m_male','$healthhouse_12to23m_female',161,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry3);

                    $str_qry4 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_2yearsabove_male', '$fixed_2yearsabove_female', '$outreach_2yearsabove_male','$outreach_2yearsabove_female','$mobile_2yearsabove_male','$mobile_2yearsabove_female','$healthhouse_2yearsabove_male','$healthhouse_2yearsabove_female',162,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry4);

                    $issue_balancep += $fixed_0to11m_male + $fixed_0to11m_female +
                            $fixed_12to23m_male +
                            $fixed_12to23m_female +
                            $fixed_2yearsabove_male +
                            $fixed_2yearsabove_female +
                            $outreach_0to11m_male +
                            $outreach_0to11m_female +
                            $outreach_12to23m_male +
                            $outreach_12to23m_female +
                            $outreach_2yearsabove_male +
                            $outreach_2yearsabove_female +
                            $mobile_0to11m_male +
                            $mobile_0to11m_female +
                            $mobile_12to23m_male +
                            $mobile_12to23m_female +
                            $mobile_2yearsabove_male +
                            $mobile_2yearsabove_female +
                            $healthhouse_0to11m_male +
                            $healthhouse_0to11m_female +
                            $healthhouse_12to23m_male +
                            $healthhouse_12to23m_female +
                            $healthhouse_2yearsabove_male +
                            $healthhouse_2yearsabove_female;
                }
                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balancep' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            } else if ($item_code == 5) {
                $issue_balance2 = 0;
                $dose2 = array('0' => '1', '1' => '2', '2' => '3');
                $i_code = 'pcv';
                foreach ($dose2 as $key => $val) {


                    $row2 = $row2_1[$key];

                    $fixed_0to11m_male = $row2[$i_code . $val . '_fixed_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_male'];

                    $fixed_0to11m_female = $row2[$i_code . $val . '_fixed_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_female'];
                    $fixed_12to23m_male = $row2[$i_code . $val . '_fixed_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_male'];
                    $fixed_12to23m_female = $row2[$i_code . $val . '_fixed_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_female'];
                    $fixed_2yearsabove_male = $row2[$i_code . $val . '_fixed_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_male'];
                    $fixed_2yearsabove_female = $row2[$i_code . $val . '_fixed_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_female'];

                    $outreach_0to11m_male = $row2[$i_code . $val . '_outreach_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_male'];
                    $outreach_0to11m_female = $row2[$i_code . $val . '_outreach_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_female'];
                    $outreach_12to23m_male = $row2[$i_code . $val . '_outreach_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_male'];

                    $outreach_12to23m_female = $row2[$i_code . $val . '_outreach_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_female'];
                    $outreach_2yearsabove_male = $row2[$i_code . $val . '_outreach_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_male'];
                    $outreach_2yearsabove_female = $row2[$i_code . $val . '_outreach_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_female'];

                    $mobile_0to11m_male = $row2[$i_code . $val . '_mobile_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_male'];
                    $mobile_0to11m_female = $row2[$i_code . $val . '_mobile_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_female'];
                    $mobile_12to23m_male = $row2[$i_code . $val . '_mobile_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_male'];


                    $mobile_12to23m_female = $row2[$i_code . $val . '_mobile_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_female'];
                    $mobile_2yearsabove_male = $row2[$i_code . $val . '_mobile_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_male'];
                    $mobile_2yearsabove_female = $row2[$i_code . $val . '_mobile_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_female'];


                    $healthhouse_0to11m_male = $row2[$i_code . $val . '_healthhouse_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_male'];
                    $healthhouse_0to11m_female = $row2[$i_code . $val . '_healthhouse_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_female'];

                    $healthhouse_12to23m_male = $row2[$i_code . $val . '_healthhouse_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_male'];
                    $healthhouse_12to23m_female = $row2[$i_code . $val . '_healthhouse_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_female'];

                    $healthhouse_2yearsabove_male = $row2[$i_code . $val . '_healthhouse_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_male'];
                    $healthhouse_2yearsabove_female = $row2[$i_code . $val . '_healthhouse_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_female'];

                    $dose_no = $row2['dose_no'];
                    $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_0to11m_male', '$fixed_0to11m_female', '$outreach_0to11m_male','$outreach_0to11m_female','$mobile_0to11m_male','$mobile_0to11m_female','$healthhouse_0to11m_male','$healthhouse_0to11m_female',160,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry2);

                    $str_qry3 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_12to23m_male', '$fixed_12to23m_female', '$outreach_12to23m_male','$outreach_12to23m_female','$mobile_12to23m_male','$mobile_12to23m_female','$healthhouse_12to23m_male','$healthhouse_12to23m_female',161,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry3);

                    $str_qry4 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_2yearsabove_male', '$fixed_2yearsabove_female', '$outreach_2yearsabove_male','$outreach_2yearsabove_female','$mobile_2yearsabove_male','$mobile_2yearsabove_female','$healthhouse_2yearsabove_male','$healthhouse_2yearsabove_female',162,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry4);
                    $issue_balance2 += $fixed_0to11m_male + $fixed_0to11m_female +
                            $fixed_12to23m_male +
                            $fixed_12to23m_female +
                            $fixed_2yearsabove_male +
                            $fixed_2yearsabove_female +
                            $outreach_0to11m_male +
                            $outreach_0to11m_female +
                            $outreach_12to23m_male +
                            $outreach_12to23m_female +
                            $outreach_2yearsabove_male +
                            $outreach_2yearsabove_female +
                            $mobile_0to11m_male +
                            $mobile_0to11m_female +
                            $mobile_12to23m_male +
                            $mobile_12to23m_female +
                            $mobile_2yearsabove_male +
                            $mobile_2yearsabove_female +
                            $healthhouse_0to11m_male +
                            $healthhouse_0to11m_female +
                            $healthhouse_12to23m_male +
                            $healthhouse_12to23m_female +
                            $healthhouse_2yearsabove_male +
                            $healthhouse_2yearsabove_female;
                }

                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balance2' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            } else if ($item_code == 6) {
                $issue_balance3 = 0;
                $dose3 = array('0' => '1', '1' => '2');
                $i_code = 'measles';
                foreach ($dose3 as $key => $val) {
                    $row2 = $row2_1[$key];
                    $fixed_0to11m_male = $row2[$i_code . $val . '_fixed_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_male'];

                    $fixed_0to11m_female = $row2[$i_code . $val . '_fixed_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_0to11m_female'];
                    $fixed_12to23m_male = $row2[$i_code . $val . '_fixed_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_male'];
                    $fixed_12to23m_female = $row2[$i_code . $val . '_fixed_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_12to23m_female'];
                    $fixed_2yearsabove_male = $row2[$i_code . $val . '_fixed_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_male'];
                    $fixed_2yearsabove_female = $row2[$i_code . $val . '_fixed_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_2yearsabove_female'];

                    $outreach_0to11m_male = $row2[$i_code . $val . '_outreach_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_male'];
                    $outreach_0to11m_female = $row2[$i_code . $val . '_outreach_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_0to11m_female'];
                    $outreach_12to23m_male = $row2[$i_code . $val . '_outreach_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_male'];

                    $outreach_12to23m_female = $row2[$i_code . $val . '_outreach_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_12to23m_female'];
                    $outreach_2yearsabove_male = $row2[$i_code . $val . '_outreach_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_male'];
                    $outreach_2yearsabove_female = $row2[$i_code . $val . '_outreach_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_2yearsabove_female'];

                    $mobile_0to11m_male = $row2[$i_code . $val . '_mobile_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_male'];
                    $mobile_0to11m_female = $row2[$i_code . $val . '_mobile_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_0to11m_female'];
                    $mobile_12to23m_male = $row2[$i_code . $val . '_mobile_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_male'];


                    $mobile_12to23m_female = $row2[$i_code . $val . '_mobile_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_12to23m_female'];
                    $mobile_2yearsabove_male = $row2[$i_code . $val . '_mobile_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_male'];
                    $mobile_2yearsabove_female = $row2[$i_code . $val . '_mobile_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_2yearsabove_female'];


                    $healthhouse_0to11m_male = $row2[$i_code . $val . '_healthhouse_0to11m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_male'];
                    $healthhouse_0to11m_female = $row2[$i_code . $val . '_healthhouse_0to11m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_0to11m_female'];

                    $healthhouse_12to23m_male = $row2[$i_code . $val . '_healthhouse_12to23m_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_male'];
                    $healthhouse_12to23m_female = $row2[$i_code . $val . '_healthhouse_12to23m_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_12to23m_female'];

                    $healthhouse_2yearsabove_male = $row2[$i_code . $val . '_healthhouse_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_male'];
                    $healthhouse_2yearsabove_female = $row2[$i_code . $val . '_healthhouse_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . $val . '_healthhouse_2yearsabove_female'];

                    $dose_no = $row2['dose_no'];
                    $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_0to11m_male', '$fixed_0to11m_female', '$outreach_0to11m_male','$outreach_0to11m_female','$mobile_0to11m_male','$mobile_0to11m_female','$healthhouse_0to11m_male','$healthhouse_0to11m_female',160,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry2);

                    $str_qry3 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_12to23m_male', '$fixed_12to23m_female', '$outreach_12to23m_male','$outreach_12to23m_female','$mobile_12to23m_male','$mobile_12to23m_female','$healthhouse_12to23m_male','$healthhouse_12to23m_female',161,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry3);

                    $str_qry4 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_2yearsabove_male', '$fixed_2yearsabove_female', '$outreach_2yearsabove_male','$outreach_2yearsabove_female','$mobile_2yearsabove_male','$mobile_2yearsabove_female','$healthhouse_2yearsabove_male','$healthhouse_2yearsabove_female',162,$val,$last_id,'1','$created_date','1','$last_update')";

                    $conn->query($str_qry4);
                    $issue_balance3 += $fixed_0to11m_male + $fixed_0to11m_female +
                            $fixed_12to23m_male +
                            $fixed_12to23m_female +
                            $fixed_2yearsabove_male +
                            $fixed_2yearsabove_female +
                            $outreach_0to11m_male +
                            $outreach_0to11m_female +
                            $outreach_12to23m_male +
                            $outreach_12to23m_female +
                            $outreach_2yearsabove_male +
                            $outreach_2yearsabove_female +
                            $mobile_0to11m_male +
                            $mobile_0to11m_female +
                            $mobile_12to23m_male +
                            $mobile_12to23m_female +
                            $mobile_2yearsabove_male +
                            $mobile_2yearsabove_female +
                            $healthhouse_0to11m_male +
                            $healthhouse_0to11m_female +
                            $healthhouse_12to23m_male +
                            $healthhouse_12to23m_female +
                            $healthhouse_2yearsabove_male +
                            $healthhouse_2yearsabove_female;
                }
                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balance3' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            } else if ($item_code == 8) {
                $issue_balance4 = 0;
                $dose4 = array('0' => '1', '1' => '2', '2' => '3', '3' => '4', '4' => '5');
                $i_code = 'tt';
                foreach ($dose4 as $key => $val) {
                    $row2 = $row2_1[$key];
                    $fixed_pragnentwomen = $row2[$i_code . $val . '_fixed_pragnentwomen'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_pragnentwomen'];
                    $fixed_nonpregnentwomen = $row2[$i_code . $val . '_fixed_nonpregnentwomen'] == NULL ? 0 : $row2[$i_code . $val . '_fixed_nonpregnentwomen'];
                    $outreach_pragnentwomen = $row2[$i_code . $val . '_outreach_pragnentwomen'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_pragnentwomen'];
                    $outreach_nonpregnentwomen = $row2[$i_code . $val . '_outreach_nonpregnentwomen'] == NULL ? 0 : $row2[$i_code . $val . '_outreach_nonpregnentwomen'];
                    $mobile_pragnentwomen = $row2[$i_code . $val . '_mobile_pragnentwomen'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_pragnentwomen'];
                    $mobile_nonpregnentwomen = $row2[$i_code . $val . '_mobile_nonpregnentwomen'] == NULL ? 0 : $row2[$i_code . $val . '_mobile_nonpregnentwomen'];

                    $healthhouse_pragnentwomen = $row2[$i_code . $val . '_healthhouse_pragnentwomen'];
                    $healthhouse_nonpregnentwomen = $row2[$i_code . $val . '_healthhouse_nonpregnentwomen'];

                    $total_pragnent = $fixed_pragnentwomen + $outreach_pragnentwomen + $mobile_pragnentwomen + $healthhouse_pragnentwomen;
                    $total_non_pragnent = $fixed_nonpregnentwomen + $outreach_nonpregnentwomen + $mobile_nonpregnentwomen + $healthhouse_nonpregnentwomen;

                    $dose_no = $row2['dose_no'];
                    $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.pregnant_women,
                            hf_data_detail.non_pregnant_women,
                            
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$total_pragnent', '$total_non_pragnent',$val,$last_id,'1',NOW(),'1',NOW())";

                    $conn->query($str_qry2);



                    $issue_balance4 += $total_pragnent + $total_non_pragnent;
                }
                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balance4' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            } else if ($item_code == 11) {
                $issue_balance5 = 0;
                $i_code = 'ipv';
                $dose_n = '1';
                $row2 = $row2_1[0];
                $fixed_0to11m_male = $row2[$i_code . '_fixed_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_fixed_0to11m_male'];
                $fixed_0to11m_female = $row2[$i_code . '_fixed_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_fixed_0to11m_female'];
                $fixed_12to23m_male = $row2[$i_code . '_fixed_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_fixed_12to23m_male'];
                $fixed_12to23m_female = $row2[$i_code . '_fixed_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_fixed_12to23m_female'];
                $fixed_2yearsabove_male = $row2[$i_code . '_fixed_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_fixed_2yearsabove_male'];
                $fixed_2yearsabove_female = $row2[$i_code . '_fixed_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_fixed_2yearsabove_female'];

                $outreach_0to11m_male = $row2[$i_code . '_outreach_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_outreach_0to11m_male'];
                $outreach_0to11m_female = $row2[$i_code . '_outreach_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_outreach_0to11m_female'];
                $outreach_12to23m_male = $row2[$i_code . '_outreach_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_outreach_12to23m_male'];

                $outreach_12to23m_female = $row2[$i_code . '_outreach_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_outreach_12to23m_female'];
                $outreach_2yearsabove_male = $row2[$i_code . '_outreach_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_outreach_2yearsabove_male'];
                $outreach_2yearsabove_female = $row2[$i_code . '_outreach_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_outreach_2yearsabove_female'];

                $mobile_0to11m_male = $row2[$i_code . '_mobile_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_mobile_0to11m_male'];
                $mobile_0to11m_female = $row2[$i_code . '_mobile_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_mobile_0to11m_female'];
                $mobile_12to23m_male = $row2[$i_code . '_mobile_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_mobile_12to23m_male'];


                $mobile_12to23m_female = $row2[$i_code . '_mobile_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_mobile_12to23m_female'];
                $mobile_2yearsabove_male = $row2[$i_code . '_mobile_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_mobile_2yearsabove_male'];
                $mobile_2yearsabove_female = $row2[$i_code . '_mobile_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_mobile_2yearsabove_female'];


                $healthhouse_0to11m_male = $row2[$i_code . '_healthhouse_0to11m_male'] == NULL ? 0 : $row2[$i_code . '_healthhouse_0to11m_male'];
                $healthhouse_0to11m_female = $row2[$i_code . '_healthhouse_0to11m_female'] == NULL ? 0 : $row2[$i_code . '_healthhouse_0to11m_female'];

                $healthhouse_12to23m_male = $row2[$i_code . '_healthhouse_12to23m_male'] == NULL ? 0 : $row2[$i_code . '_healthhouse_12to23m_male'];
                $healthhouse_12to23m_female = $row2[$i_code . '_healthhouse_12to23m_female'] == NULL ? 0 : $row2[$i_code . '_healthhouse_12to23m_female'];

                $healthhouse_2yearsabove_male = $row2[$i_code . '_healthhouse_2yearsabove_male'] == NULL ? 0 : $row2[$i_code . '_healthhouse_2yearsabove_male'];
                $healthhouse_2yearsabove_female = $row2[$i_code . '_healthhouse_2yearsabove_female'] == NULL ? 0 : $row2[$i_code . '_healthhouse_2yearsabove_female'];

                $dose_no = $row2['dose_no'];
                $str_qry2 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_0to11m_male', '$fixed_0to11m_female', '$outreach_0to11m_male','$outreach_0to11m_female','$mobile_0to11m_male','$mobile_0to11m_female','$healthhouse_0to11m_male','$healthhouse_0to11m_female',160,1,$last_id,'1','$created_date','1','$last_update')";

                $conn->query($str_qry2);

                $str_qry3 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            
                            
                            )
                            VALUES ('$fixed_12to23m_male', '$fixed_12to23m_female', '$outreach_12to23m_male','$outreach_12to23m_female','$mobile_12to23m_male','$mobile_12to23m_female','$healthhouse_12to23m_male','$healthhouse_12to23m_female',161,1,$last_id,'1','$created_date','1','$last_update')";

                $conn->query($str_qry3);

                $str_qry4 = "INSERT INTO hf_data_detail
                            (hf_data_detail.fixed_inside_uc_male,
                            hf_data_detail.fixed_inside_uc_female,
                            hf_data_detail.outreach_male,
                            hf_data_detail.outreach_female,
                            hf_data_detail.mobile_inside_male,
                            hf_data_detail.mobile_inside_female,
                            hf_data_detail.lhw_inside_male,
                            hf_data_detail.lhw_inside_female,
                            hf_data_detail.age_group_id,
                            hf_data_detail.vaccine_schedule_id,
                            hf_data_detail.hf_data_master_id,
                            hf_data_detail.created_by,
                            hf_data_detail.created_date,
                            hf_data_detail.modified_by,
                            hf_data_detail.modified_date
                            )
                            VALUES ('$fixed_2yearsabove_male', '$fixed_2yearsabove_female', '$outreach_2yearsabove_male','$outreach_2yearsabove_female','$mobile_2yearsabove_male','$mobile_2yearsabove_female','$healthhouse_2yearsabove_male','$healthhouse_2yearsabove_female',162,1,$last_id,'1','$created_date','1','$last_update')";

                $conn->query($str_qry4);
                $issue_balance5 = $fixed_0to11m_male + $fixed_0to11m_female +
                        $fixed_12to23m_male +
                        $fixed_12to23m_female +
                        $fixed_2yearsabove_male +
                        $fixed_2yearsabove_female +
                        $outreach_0to11m_male +
                        $outreach_0to11m_female +
                        $outreach_12to23m_male +
                        $outreach_12to23m_female +
                        $outreach_2yearsabove_male +
                        $outreach_2yearsabove_female +
                        $mobile_0to11m_male +
                        $mobile_0to11m_female +
                        $mobile_12to23m_male +
                        $mobile_12to23m_female +
                        $mobile_2yearsabove_male +
                        $mobile_2yearsabove_female +
                        $healthhouse_0to11m_male +
                        $healthhouse_0to11m_female +
                        $healthhouse_12to23m_male +
                        $healthhouse_12to23m_female +
                        $healthhouse_2yearsabove_male +
                        $healthhouse_2yearsabove_female;
                $str_qry1_u = "UPDATE hf_data_master SET hf_data_master.issue_balance='$issue_balance5' Where hf_data_master.pk_id = '$last_id'";

                $conn->query($str_qry1_u);
            }
        }

        //  $last_id = $conn->insert_id;
    } else {
        // $id = $res_select['pk_id'];
        //  $str_qry1 = "UPDATE hf_data_master SET hf_data_master.opening_balance='$opening',hf_data_master.received_balance='$received',hf_data_master.issue_balance='$issue',hf_data_master.closing_balance='$closing' Where hf_data_master.pk_id = '$id'";
        //   $conn->query($str_qry1);
    }
    $conn->query("UPDATE epi_mis_kp_consumption_interfacing_data SET is_processed = '$last_id' WHERE pk_id = '$primary_id'");

    // echo $uc_code . '-' . $reporting_month_date . '-' . $transaction_id . '-' . $transaction_date_time . '-' . $item_id . '-' . $item_qty;
    //}
    sleep(1);
}

mail("emailaddresshere", "Consumption Summary Updated ($month-$year)", "Reporting data has been updated");
echo date("d/m/Y h:i:s") . "<br>";
echo "Executed Successfully!";
?>