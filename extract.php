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

$options = getopt('d:la::f::o::');

include 'squirrel.php';

if (array_key_exists('d', $options) === false) {
    bail('Please indicate database with the -d flag');
}

try {
    $squirrel = new CMBSquirrel($options['d']);
} catch (Exception $e) {
    bail('Cannot initialize: ' . $e->getMessage());
}

if (array_key_exists('l', $options)) {
    foreach ($squirrel->listAccounts() as $id => $name) {
        echo 'Account ID: ', $id, ' - ', $name, "\n";
    }
    exit(0);
}

if (array_key_exists('a', $options)
        && array_key_exists('f', $options)) {
    if (file_exists($options['f']) === false) {
        bail('The file containing your forecast expenses does not exists');
    }
    include $options['f'];
    $transactions = $squirrel->extractAndForecast($options['a'], $expenses_monthly);
} else {
    $transactions = $squirrel->loadTransactions();
}

if (array_key_exists('o', $options)) {
    $squirrel->setOutputFile($options['o']);
    $squirrel->saveToFile($transactions);
} else {
    bail('Please indicate the output file name with the -o flag');
}

function bail($msg = '')
{
    if (empty($msg)) {
        echo "
Usage:
    php extract.php -d/path/to/database
    php extract.php -l -d/path/to/database # list accounts
    php extract.php -l -oextract.csv -d/path/to/database
    php extract.php -l -aACCOUNTID -oextract.csv -d/path/to/database

Examples:
    php extract.php -a3 -fexpenses.php -ofoo.csv -ddatabase.squirrel #extracts transactions for account 3, from the list accounts";
    } else {
        echo "$msg\n";
    }
    exit(1);
}
