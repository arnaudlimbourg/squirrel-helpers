<?php
$output_file = 'extract.csv';
$database_file = 'database.squirrel';

if (!file_exists($database_file)) {
    echo "Cannot find database file\n";
    exit(1);
}

if (!file_exists($output_file)) {
    echo "Output file already exists, please delete it first\n";
}

$db = new pdo('sqlite:' . $database_file);

$query = 'select
            ztransaction.z_pk, zaccount.zaccountname, zcategory, zdate, zamount,
            zcategory.zname as category, ztransactiondescription
          from
            ztransaction, zaccount, zcategory
          where
            ztransaction.zaccount=zaccount.z_pk
          and
            ztransaction.zcategory=zcategory.z_pk'; 

$output = new SplFileObject($output_file, 'w');

// when parsing the date we need to add the number of seconds
// between unix epoch and NSDate reference as per
// http://developer.apple.com/documentation/Cocoa/Reference/Foundation/Classes/NSDate_Class/Reference/Reference.html
foreach ($db->query($query) as $row) {
    $transaction = array();
    $transaction['account']     = $row['ZACCOUNTNAME'];
    $transaction['date']        = date('Y-m-d', $row['ZDATE'] + 978307200.0);
    $transaction['description'] = trim($row['ZTRANSACTIONDESCRIPTION']);
    $transaction['amount']      = number_format($row['ZAMOUNT'], 2);
    $transaction['category']    = $row['category'];
    $output->fwrite((implode(',',$transaction) . "\n"));
}


