<?php
/*
Copyright (c) 2009, Arnaud Limbourg
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
    * Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/*************************************************
 * Non destructive (read-only) script that
 * extracts transactions from a squirrel database
 * to a csv file.
 *
 * To be safe use on a copy of the database.
*************************************************/
if (!ini_get('register_argc_argv')) {
    bail('register_argc_argv must be set in your php.ini');
}

if (count($argv) === 1) {
    bail();
}

if (empty($argv[1])) {
    bail();
} else {
    $database_file = $argv[1];
}

if (empty($argv[2])) {
    bail();
} else {
    $output_file = $argv[2];
}
   
if (!file_exists($database_file)) {
    bail("Cannot find database file");
}

if (file_exists($output_file)) {
    bail("Output file already exists, please delete it first");
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
            ztransaction.zcategory=zcategory.z_pk
          order by
            ztransaction.zaccount, ztransaction.zdate'; 

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
    $output->fwrite((implode(';', $transaction) . "\n"));
}

function bail($msg = '')
{
    if (empty($msg)) {
        echo "Usage: php extract.php /path/to/database path/to/extract_file\n";
    } else {
        echo "$msg\n";
    }
    exit(1);
}
