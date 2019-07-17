#!/usr/local/bin/ea-php56 -q
<?php
set_time_limit(0);

$dbconfig = 'YOUR DB CONNECTION HERE';
$token = 'YOURTOKENHERE';

$no_of_vouchers = $no_of_transactions = $no_of_proc_vouchers = $no_of_proc_transactions = array();

$level = array(0 => "province", 1 => "district");

$date = date("Y-m-d", strtotime("-1 day"));

$purpose_arr = array(
    '1' => '1',
    '2' => '2',
    '3' => '3',
    '4' => '4',
    '5' => '6'
);

foreach ($level as $lev) {
    $url = "http://epimis.cres.pk/API/communication/getStockReceiving?date=$date&level=$lev&token=$token";
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


    $dd = json_decode($result, true);
    $i = 0;

    foreach ($dd['data'] as $key => $row0) {

        if (!empty($row0)) {
            //Count Arrays
            $no_of_vouchers[$row0['transaction_number']] = $row0['transaction_number'];
            $no_of_transactions[$row0['transaction_id']] = $row0['transaction_id'];

            $epimistransactionid = $row0['transaction_id'];
            $transactionid = $row0['transaction_number'];
            $transactiondate = $row0['transactiondate'];
            $transaction_type_id = $row0['transaction_type_id'];
            $transaction_type_name = $row0['transaction_type_name'];
            $transactionref = (isset($row0['vLMISReference']) ? $row0['vLMISReference'] : '');
            $purpose = (isset($row0['purpose']) ? $purpose_arr[$row0['purpose']] : 1);
            $issuedby = $row0['from_facility'];
            $issuedbyid = $row0['from_facility_id'];
            $issuedtoid = $row0['to_facility_id'];
            $issuedto = $row0['to_facility'];
            $batchnumber = $row0['batch_number'];
            $batch_unit_price = $row0['batch_unit_price'];
            $manufacturer = (!empty($row0['manufacturer']) ? $row0['manufacturer'] : '');
            $expirydate = $row0['batch_expiry'];
            $productiondate = $row0['batch_production'];
            $itemid = $row0['product_id'];
            $itemname = $row0['product'];
            $vvmstage = (isset($row0['vvm_stage']) ? $row0['vvm_stage'] : '0');
            $issuedqty = ($row0['quantity'] == NULL ? 0 : $row0['quantity']);
            $created_date = $row0['created_date'];
            $modified_date = $row0['modified_date'];
            $values = "'$epimistransactionid','$transactiondate','$transactionid','$purpose','$transaction_type_id','$issuedbyid','$issuedby','$issuedtoid','$issuedto','$issuedqty','$vvmstage','$transaction_type_name','$batchnumber','$expirydate','$batch_unit_price','$productiondate','$itemname','$itemid','$transactionref','$url','$created_date','$modified_date'";
            $hash = md5($values);

            $insert = "INSERT INTO epi_mis_kp_im_interfacing_data (transaction_id,transactiondate,transaction_number,purpose,transaction_type_id,from_facility_id,from_facility,to_facility_id,to_facility,quantity,vvm_stage,transaction_type_name,batch_number,batch_expiry,batch_unit_price,batch_production,product,product_id,transaction_reference,url,created_date,modified_date,`hash`,is_processed) VALUES ($values,'$hash','0')";
            $conn->query($insert);
        }
    }
}

$qry_select = "SELECT
	epi_mis_kp_im_interfacing_data.transaction_id,
	epi_mis_kp_im_interfacing_data.transactiondate,
	epi_mis_kp_im_interfacing_data.transaction_number,
	epi_mis_kp_im_interfacing_data.purpose,
	epi_mis_kp_im_interfacing_data.transaction_type_id,
	epi_mis_kp_im_interfacing_data.from_facility_id,
	epi_mis_kp_im_interfacing_data.from_facility,
	epi_mis_kp_im_interfacing_data.to_facility_id,
	epi_mis_kp_im_interfacing_data.to_facility,
	epi_mis_kp_im_interfacing_data.quantity,
	epi_mis_kp_im_interfacing_data.vvm_stage,
	epi_mis_kp_im_interfacing_data.transaction_type_name,
	epi_mis_kp_im_interfacing_data.batch_number,
	epi_mis_kp_im_interfacing_data.batch_expiry,
	epi_mis_kp_im_interfacing_data.batch_unit_price,
	epi_mis_kp_im_interfacing_data.batch_production,
	epi_mis_kp_im_interfacing_data.product,
	epi_mis_kp_im_interfacing_data.product_id,
	epi_mis_kp_im_interfacing_data.transaction_reference,
	epi_mis_kp_im_interfacing_data.url,
	epi_mis_kp_im_interfacing_data.`hash`,
	epi_mis_kp_im_interfacing_data.created_date,
	epi_mis_kp_im_interfacing_data.modified_date,
	epi_mis_kp_im_interfacing_data.is_processed
FROM
	epi_mis_kp_im_interfacing_data
WHERE
	epi_mis_kp_im_interfacing_data.is_processed = 0
AND epi_mis_kp_im_interfacing_data.transaction_type_id = 1";

/*
 * AND DATE_FORMAT(
  epi_mis_kp_im_interfacing_data.modified_date,
  '%Y-%m-%d'
  ) = '$date'
 */
$row_select123 = $conn->query($qry_select);

while ($row0 = $row_select123->fetch_assoc()) {
    
    $epimistransactionid = $row0['transaction_id'];
    $transactionid = $row0['transaction_number'];
    $transactiondate = $row0['transactiondate'];
    $transactionref = (isset($row0['vLMISReference']) ? $row0['vLMISReference'] : '');
    $purpose = $row0['purpose'];
    $issuedby = $row0['from_facility'];
    $issuedbyid = $row0['from_facility_id'];
    $issuedtoid = $row0['to_facility_id'];
    $batchnumber = $row0['batch_number'];
    $manufacturer = (!empty($row0['manufacturer']) ? $row0['manufacturer'] : '');
    $expirydate = $row0['batch_expiry'];
    $productiondate = $row0['batch_production'];
    $itemid = $row0['product_id'];
    $itemname = $row0['product'];
    $vvmstage = (isset($row0['vvm_stage']) ? $row0['vvm_stage'] : '0');

    $issuedqty = ($row0['quantity'] == NULL ? 0 : $row0['quantity']);

    $created_date = $row0['created_date'];
    $modified_date = $row0['modified_date'];

    $qry_select2 = "SELECT
                item_pack_sizes.pk_id,
                item_pack_sizes.item_unit_id,
                item_pack_sizes.item_category_id
                FROM
                item_mapping
                INNER JOIN item_pack_sizes ON item_pack_sizes.pk_id = item_mapping.item_pack_size_id
                WHERE
                item_mapping.epi_mis_item_id = $itemid";

    $row_select2 = $conn->query($qry_select2);

    if ($row_select2->num_rows > 0) {
        $res_select2 = $row_select2->fetch_assoc();
    } else {
        $qry_select2 = "SELECT
	stakeholder_item_pack_sizes.item_pack_size_id pk_id,
	item_pack_sizes.item_unit_id,
	item_pack_sizes.item_category_id
FROM
	stock_batch
INNER JOIN pack_info ON stock_batch.pack_info_id = pack_info.pk_id
INNER JOIN stakeholder_item_pack_sizes ON pack_info.stakeholder_item_pack_size_id = stakeholder_item_pack_sizes.pk_id
INNER JOIN item_pack_sizes ON stakeholder_item_pack_sizes.item_pack_size_id = item_pack_sizes.pk_id
WHERE
	stock_batch.number = '$batchnumber'
AND stock_batch.expiry_date = '$expirydate'";

        $row_select2 = $conn->query($qry_select2);
    }

    // item ID
    $item_id = $res_select2['pk_id'];
    $item_unit_id = $res_select2['item_unit_id'];
    $item_category_id = $res_select2['item_category_id'];
    //$item_name_b = $res_select2['epi_mis_batch_item_name'];
    // from warehouse ID
    $str_qry_f_w = "SELECT
                warehouses.pk_id
                FROM
                warehouses
                INNER JOIN locations ON warehouses.location_id = locations.pk_id
                WHERE
                locations.dhis_code = '$issuedbyid'";

    $row_f_w = $conn->query($str_qry_f_w);

    $res_f_w = $row_f_w->fetch_assoc();

    $from_warehouse_id = $res_f_w['pk_id'];

    // to warehouse ID
    $str_qry_t_w = "SELECT
                warehouses.pk_id
                FROM
                warehouses
                INNER JOIN locations ON warehouses.location_id = locations.pk_id
                WHERE
                locations.province_id = 3 AND
                locations.dhis_code = '$issuedtoid'";

    $row_t_w = $conn->query($str_qry_t_w);

    $res_t_w = $row_t_w->fetch_assoc();

    $to_warehouse_id = $res_t_w['pk_id'];

    $qry_select = "SELECT 
            stock_master.pk_id
            
            FROM
            stock_master
            
            WHERE
            stock_master.transaction_type_id = 1 AND 
            stock_master.to_warehouse_id = '$to_warehouse_id'"
            . " AND stock_master.from_warehouse_id = '$from_warehouse_id'"
            . " AND stock_master.transaction_date = '$transactiondate'";

    $row_select = $conn->query($qry_select);

    $res_select = $row_select->fetch_assoc();

    if (empty($res_select)) {
        $tr_type_e = 1;
        $tr_date_e = $transactiondate;
        $wh_id_e = $to_warehouse_id;
        $trans_id_e = '';
        //$trans = getTransactionNumber($tr_type_e, $tr_date_e, $wh_id_e, $trans_id_e);
        //$tr_number = $trans['trans_no'];
        $tr_counter = 1;
        $str_qry1 = "INSERT INTO stock_master
                    (stock_master.transaction_date,
                    stock_master.transaction_number,
                    stock_master.transaction_reference,
                    stock_master.transaction_counter,
                    stock_master.draft,
                    stock_master.transaction_type_id,
                    stock_master.from_warehouse_id,
                    stock_master.to_warehouse_id,
                    stock_master.stakeholder_activity_id,
                    stock_master.parent_id ,
                    stock_master.created_by,
                    stock_master.created_date,
                    stock_master.modified_by,
                    stock_master.modified_date
                    )
                    VALUES ('$transactiondate', '$transactionid', '$transactionref','$tr_counter','0','1','$from_warehouse_id','$to_warehouse_id','$purpose','0','1','$created_date','1','$modified_date')";

        $conn->query($str_qry1);
        $stock_master_id = $conn->insert_id;
    } else {
        $stock_master_id = $res_select['pk_id'];
    }

    $qry_select_man = "SELECT
	stakeholders.pk_id
FROM
	stock_batch
INNER JOIN pack_info ON stock_batch.pack_info_id = pack_info.pk_id
INNER JOIN stakeholder_item_pack_sizes ON pack_info.stakeholder_item_pack_size_id = stakeholder_item_pack_sizes.pk_id
INNER JOIN stakeholders ON stakeholder_item_pack_sizes.stakeholder_id = stakeholders.pk_id
INNER JOIN stock_batch_warehouses ON stock_batch_warehouses.stock_batch_id = stock_batch.pk_id
WHERE
	stakeholders.stakeholder_type_id = 3
AND stock_batch_warehouses.warehouse_id = $from_warehouse_id
AND stakeholder_item_pack_sizes.item_pack_size_id = $item_id
AND stock_batch.number = '$batchnumber' LIMIT 1";
//echo $qry_select_man;

    $row_select_man = $conn->query($qry_select_man);
    $res_select_man = $row_select_man->fetch_assoc();

    if (count($res_select_man) == 0) {
        $manufacturer_id = 93;
//                    $str_qry1man = "INSERT INTO stakeholders (stakeholders.stakeholder_name,
//    stakeholders.list_rank,
//    stakeholders.parent_id,
//    stakeholders.stakeholder_type_id,
//    stakeholders.stakeholder_sector_id,
//    stakeholders.geo_level_id,
//    stakeholders.stakeholder_activity_id,
//    stakeholders.created_by,
//    stakeholders.created_date,
//    stakeholders.modified_by,
//    stakeholders.modified_date) VALUES ('".$manufacturer."','1','0','3','1','1','1','1',NOW(),'1',NOW())";
//
//                    $conn->query($str_qry1man);
//                    $manufacture_id = $conn->insert_id;                    
    } else {
        $manufacture_id = $res_select_man['pk_id'];
    }

    $qry_select_stkitm = "SELECT
                stakeholder_item_pack_sizes.pk_id
                FROM
                stakeholder_item_pack_sizes
                
                WHERE
                stakeholder_item_pack_sizes.item_pack_size_id = '$item_id'
                and stakeholder_item_pack_sizes.stakeholder_id = '$manufacture_id'
                LIMIT 1";

    $row_select_stkitm = $conn->query($qry_select_stkitm);
    $res_select_stkitm = $row_select_stkitm->fetch_assoc();
    if (empty($res_select_stkitm)) {
        $str_qrystkitm = "INSERT INTO stakeholder_item_pack_sizes (stakeholder_item_pack_sizes.stakeholder_id,
    stakeholder_item_pack_sizes.item_pack_size_id,
    stakeholder_item_pack_sizes.created_by,
    stakeholder_item_pack_sizes.created_date,
    stakeholder_item_pack_sizes.modified_by,
    stakeholder_item_pack_sizes.modified_date) VALUES ('" . $manufacture_id . "','" . $item_id . "','1','$created_date','1','$modified_date')";

        $conn->query($str_qrystkitm);
        $stk_item_id = $conn->insert_id;
    } else {
        $stk_item_id = $res_select_stkitm['pk_id'];
    }

    $qry_select_pack = "SELECT
                pack_info.pk_id
                FROM
                pack_info                
                WHERE
                pack_info.stakeholder_item_pack_size_id = '$stk_item_id'
                LIMIT 1";

    $row_select_pack = $conn->query($qry_select_pack);
    $res_select_pack = $row_select_pack->fetch_assoc();
    if (empty($res_select_pack)) {
        $str_qrypack = "INSERT INTO pack_info (pack_info.stakeholder_item_pack_size_id,
    pack_info.created_by,
    pack_info.created_date,
    pack_info.modified_by,
    pack_info.modified_date) VALUES ('" . $stk_item_id . "','1','$created_date','1','$modified_date')";

        $conn->query($str_qrypack);
        $pack_info_id = $conn->insert_id;
    } else {
        $pack_info_id = $res_select_pack['pk_id'];
    }

    $qry_batch_sel = "SELECT
                stock_batch.pk_id,
                stock_batch.number,
                stock_batch.expiry_date,
                stock_batch.unit_price,
                stock_batch.production_date,
                stock_batch.vvm_type_id,
                stock_batch.pack_info_id,
                stock_batch.created_by,
                stock_batch.created_date,
                stock_batch.modified_by,
                stock_batch.modified_date
                FROM
                stock_batch
                WHERE
                stock_batch.number = '$batchnumber' AND
                stock_batch.pack_info_id = '$pack_info_id'";
    $row_select_batch = $conn->query($qry_batch_sel);

    $res_select_batch = $row_select_batch->fetch_assoc();

    if (empty($res_select_batch)) {
        $expiry_date = date('Y-m-d', strtotime('+5 years'));
        $production_date = date('Y-m-d');
        $str_qry3 = "INSERT INTO stock_batch
                    (stock_batch.number,
                    stock_batch.expiry_date,
                    stock_batch.production_date,
                    stock_batch.unit_price,
                    stock_batch.vvm_type_id,
                    stock_batch.pack_info_id,
                    stock_batch.created_by,
                    stock_batch.created_date,
                    stock_batch.modified_by,
                    stock_batch.modified_date
                    )
                    VALUES ('$batchnumber','$expiry_date','$production_date','0','1', '$pack_info_id','1',NOW(),'1',NOW())";

        $conn->query($str_qry3);
        $batchid = $conn->insert_id;
    } else {
        $batchid = $res_select_batch['pk_id'];
    }
    $qry_batch_warehouse_sel = "SELECT
                    stock_batch_warehouses.pk_id,
                    stock_batch_warehouses.quantity
                    FROM
                    stock_batch_warehouses
                    INNER JOIN stock_batch ON stock_batch_warehouses.stock_batch_id = stock_batch.pk_id
                    WHERE
                    stock_batch.pk_id = '$batchid' AND
                    stock_batch_warehouses.warehouse_id = '$to_warehouse_id' AND
                    stock_batch.pack_info_id = '$pack_info_id'";

    $row_select_batch_warehouse = $conn->query($qry_batch_warehouse_sel);

    $res_select_batch_warehouse = $row_select_batch_warehouse->fetch_assoc();
    if ($issuedqty > 0) {
        if (empty($res_select_batch_warehouse)) {
            $str_qry5 = "INSERT INTO stock_batch_warehouses
                       (stock_batch_warehouses.quantity,
                        stock_batch_warehouses.`status`,
                        stock_batch_warehouses.warehouse_id,
                        stock_batch_warehouses.stock_batch_id,
                        stock_batch_warehouses.created_by,
                        stock_batch_warehouses.created_date,
                        stock_batch_warehouses.modified_by,
                        stock_batch_warehouses.modified_date
                    )
                    VALUES ('$issuedqty', 'Running','$to_warehouse_id','$batchid','1','$created_date','1','$modified_date')";

            $conn->query($str_qry5);
            $stock_batch_id = $conn->insert_id;
        } else {
            $stock_batch_id = $res_select_batch_warehouse['pk_id'];

            $str_qry1 = "SELECT AdjustQty($stock_batch_id,$to_warehouse_id) from DUAL";
            $conn->query($str_qry1);
        }

        $conn->query("DELETE FROM stock_detail WHERE quantity='$issuedqty' AND adjustment_type = '1' AND stock_master_id='$stock_master_id' AND stock_batch_warehouse_id = '$stock_batch_id' AND created_date = '$created_date'");

        $str_qry2_detail = "INSERT INTO stock_detail
                    (stock_detail.quantity,
                    stock_detail.`temporary`,
                    stock_detail.vvm_stage,
                    stock_detail.is_received,
                    stock_detail.adjustment_type,
                    stock_detail.stock_master_id,
                    stock_detail.stock_batch_warehouse_id,
                    stock_detail.item_unit_id,
                    stock_detail.created_by,
                    stock_detail.created_date,
                    stock_detail.modified_by,
                    stock_detail.modified_date
                    )
                    VALUES ('$issuedqty', '0', '$vvmstage','1','1','$stock_master_id','$stock_batch_id','$item_unit_id','1','$created_date','1','$modified_date')";

        $conn->query($str_qry2_detail);
        $stock_detail_id_last = $conn->insert_id;

        $conn->query("UPDATE epi_mis_kp_im_interfacing_data SET is_processed = $stock_detail_id_last WHERE transaction_id = $epimistransactionid");
    }

    if ($item_category_id == 1) {

        $qry_placement_location = "SELECT DISTINCT
                    warehouses.warehouse_name,
                    cold_chain.asset_id,
                    placement_locations.pk_id as location_id,
                    warehouses.pk_id
                    FROM
                    cold_chain
                    INNER JOIN warehouses ON cold_chain.warehouse_id = warehouses.pk_id
                    INNER JOIN placement_locations ON cold_chain.pk_id = placement_locations.location_id
                    WHERE
                    warehouses.province_id = 3 AND
                    warehouses.stakeholder_office_id = 4 AND
                    placement_locations.location_type = 99
                    and warehouses.pk_id = '$to_warehouse_id'
                    LIMIT 1";
        $row_placement_location = $conn->query($qry_placement_location);

        $res_placement_location = $row_placement_location->fetch_assoc();
        $placement_location_id = $res_placement_location['location_id'];
    }
    // Non Vaccines
    else {
        $qry_placement_location = "SELECT DISTINCT
                        non_ccm_locations.location_name,
                        non_ccm_locations.warehouse_id,
                        warehouses.warehouse_name,
                        placement_locations.pk_id as location_id
                        FROM
                        non_ccm_locations
                        INNER JOIN warehouses ON non_ccm_locations.warehouse_id = warehouses.pk_id
                        INNER JOIN placement_locations ON non_ccm_locations.pk_id = placement_locations.location_id
                        WHERE
                        warehouses.province_id = 3 AND
                        warehouses.stakeholder_office_id = 4 AND
                        placement_locations.location_type = 100
                        and warehouses.pk_id = '$to_warehouse_id'
                    LIMIT 1";
        $row_placement_location = $conn->query($qry_placement_location);

        $res_placement_location = $row_placement_location->fetch_assoc();
        $placement_location_id = $res_placement_location['location_id'];
    }

    $str_qry2_placment = "INSERT INTO placements
                    (placements.quantity,
                    placements.vvm_stage,
                    placements.is_placed,
                    placements.placement_location_id,
                    placements.stock_batch_warehouse_id,
                    placements.stock_detail_id,
                    placements.placement_transaction_type_id,
                    placements.created_by,
                    placements.created_date,
                    placements.modified_by,
                    placements.modified_date
                    )
                    VALUES ('$issuedqty','$vvmstage','1','$placement_location_id','$stock_batch_id','$stock_detail_id_last','114','1','$created_date','1','$modified_date')";

    $conn->query($str_qry2_placment);
    $i++;

    $no_of_proc_vouchers[$stock_master_id] = $stock_master_id;
    $no_of_proc_transactions[$stock_detail_id_last] = $stock_detail_id_last;
}

mail("emailaddresshere", "IM Received data has been updated for EPI-MIS - $date", "Inventory Mangement Received data for EPI-MIS for the date of $date has been downloaded and processed in vLMIS. Total Downloaded vouchers: " . count($no_of_vouchers) . ", Processed vouvhers: " . count($no_of_vouchers) . ", Total Downloaded Transactions: " . count($no_of_transactions) . ", Processed Transactions: " . count($no_of_proc_transactions));
echo "Executed Successfully!";