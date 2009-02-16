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

class CMBSquirrel
{
    private $db;

    private $outputFile;

    public function __construct($database)
    {
        $this->database = $database;
        $this->init();
    }

    public function init()
    {
        if (file_exists($this->database) === false
            && is_readable($this->database) === false) {
            throw new CMBSquirrelException('Cannot find database');
        }
        $this->db = new PDO('sqlite:' . $this->database);
    }

    public function setOutputFile($filename)
    {
        if (file_exists($filename) === true) {
            throw new CMBSquirrelException('Output file already exist');
        }
        $this->outputFile = $filename;
    }

    public function loadTransactions($account = null)
    {
        $extra = '';

        if ($account !== null) {
            $extra = ' and zaccount.z_pk=' . $this->db->quote($account);
        }

        $query = 'select
                    ztransaction.z_pk, zaccount.zaccountname,
                    zcategory, zdate, zamount,
                    zcategory.zname as category, ztransactiondescription,
                    zaccount.Z_PK as accountid
                from
                    ztransaction, zaccount, zcategory
                where
                    ztransaction.zaccount=zaccount.z_pk
                and
                    ztransaction.zcategory=zcategory.z_pk
                ' . $extra . '
                order by
                    ztransaction.zaccount, ztransaction.zdate';

        $transactions = array();

        // when parsing the date we need to add the number of seconds
        // between unix epoch and NSDate reference as per
        // http://developer.apple.com/documentation/Cocoa/Reference/Foundation/Classes/NSDate_Class/Reference/Reference.html
        foreach ($this->db->query($query) as $row) {
            $timestamp = $row['ZDATE'] + 978307200.0;
            $transactions[] = array(
                'account'     => $row['ZACCOUNTNAME'],
                'date'        => date('Y-m-d', $timestamp),
                'year'        => date('Y', $timestamp),
                'description' => trim($row['ZTRANSACTIONDESCRIPTION']),
                'amount'      => number_format($row['ZAMOUNT'], 2, ',', ' '),
                'category'    => $row['category'],
                'account_id'  => $row['accountid'],
            );
        }
        return $transactions;
    }

    public function listAccounts()
    {
        $query = 'select z_pk, zaccountname from zaccount';
        $res = array();
        foreach ($this->db->query($query) as $row) {
            $res[$row['Z_PK']] = $row['ZACCOUNTNAME'];
        }
        return $res;
    }

    public function saveToFile($transactions)
    {
        if (is_array($transactions) === false
                && count($transactions) === 0) {
            throw new CMBSquirrelException('No transactions to save in a file');
        }
        $output = new SplFileObject($this->outputFile, 'w');
        foreach ($transactions as $transaction) {
            $output->fwrite((implode(';', $transaction) . "\n"));
        }
    }

    public function extractAndForecast($account, $plannedExpenses)
    {
        $transactions = $this->loadTransactions($account);
        $last_transaction = end($transactions);
        $last_ts = strtotime($last_transaction['date']);
        $last_day = date('d', $last_ts);
        $last_month = date('m', $last_ts);
        $year = date('Y');
        foreach ($plannedExpenses as $day => $info) {
            $day = str_pad($day, 2, 0, STR_PAD_LEFT);
            if ($day >= $last_day) {
                $transactions[] = array(
                    'account'     => $last_transaction['account'],
                    'date'        => date("Y-m-$day"),
                    'year'        => $year,
                    'description' => $info['description'],
                    'amount'      => number_format($info['amount'], 2, ',', ' '),
                    'category'    => $info['category'],
                    'account_id'  => $account,
                );
            }
        }
        return $transactions;
    }
}

class CMBSquirrelException extends Exception {}
