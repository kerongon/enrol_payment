<?php

require_once('../../../config.php');
require_once("$CFG->libdir/moodlelib.php");
require_once('../discountlib.php');

global $DB;


$instanceid = $_GET['instanceid'];
$prepayToken = $_GET['prepaytoken'];

$instance = $DB->get_record('enrol', array('id' => $instanceid), '*', MUST_EXIST);
$correct_code = (trim($_GET['discountcode']) == trim($instance->customtext2));
$payment = null;

if($correct_code) {
    try {
        $payment = $DB->get_record_sql('SELECT * FROM {enrol_ecommerce_ipn} WHERE '.$DB->sql_compare_text('prepaytoken') . ' = ? ', array('prepaytoken' => $prepayToken));
    } catch (Exception $e) {
        echo json_encode([ 'success' => false
                         , 'failmessage' => $e->getMessage() ]);
                         // , 'failmessage' => "Payment UUID ".$prepayToken." not found in database."]);
        die();
    }

    try {
        $dataobj = array( 'id' => $payment->id
                        , 'discounted' => true );

        $DB->update_record('enrol_ecommerce_ipn', $dataobj);
    } catch (Exception $e) {
        echo json_encode([ 'success' => false
                         , 'failmessage' => "Unable to update payment record in database."]);
        die();
    }

    $to_return = calculate_cost($instance, $payment);
    $to_return["success"] = true;
    echo json_encode($to_return);
} else {
    echo json_encode(array("success" => false) );
}

?>
