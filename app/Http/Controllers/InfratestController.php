<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Images;
use Denpa\Bitcoin\LaravelClient as BitcoinClient;
use Carbon\Carbon; 

class InfratestController extends Controller
{
    #################Debug #########################
	public function debug(){
		$address = "DEqPA3SHEsWGr1GdrbwX9c1bLdvajVoqjB";
		$receiver = array(
			'DEqPA3SHEsWGr1GdrbwX9c1bLdvajVoqjB' => '500',
			'DKn3pEcKDocTvUPLcbXjQArzEPPzzz551W' => '2500',
			'D6zJvrijCg3s2v2QUFJ2SCJujf2VqF8YK2' => '569000',
			'DSTPWCHbWpXQ2R41uaXThV6swc1ikvvEPa' => '3300',
			'DC3WujGXv3T9DP2cGip8QnXVzDxQhpwiH2' => '88562',
			'D5sRkj9F79FwmFggXyKAZFGWt81NUh2Nnp' => '36588',
			'DEdbVFX9Bxjsx9xG7jA2GtPCLiUWGuuqNw' => '9985',
			'DBcHU2oB4Xenv2wxzNJgSKjWTongdvpE9e' => '7569',
			'DEdbVFX9Bxjsx9xG7jA2GtPCLiUWGuuqNw' => '28658000', 
		);

		$addresses = array_keys($receiver);
        foreach ($addresses as $addr) {
            $arr[] = array($addr => get_balance($addr));
        }
        dd($arr);


		$values = array_values($receiver);
		$addresses = array_keys($receiver);

        dd($receiver, $values, $addresses);
		dd(
			//getconnection()
			// getblockhash($blockid)
			// getblockdet($blockhash)
			// getestimatefee()
			// get_balance($label)
			// getaddress($label)
			// addCrypto($label)
			// get_label_crypto($address)
			// listransactionall()
			//listransaction($address)
			// gettransaction_crypto($txid)
			// checkAddress($address)
			// sendtoaddressRAW($label, $recvaddress, $cryptoamount, $memo, $comm_fee)
			sendtomanyaddress($receiver, $memo, $comm_fee)
			// dumpkey($label)
			// withdrawal_admin_crypto($sendlabel, $recvaddress, $cryptoamount, $memo) 
			// getbalanceAll()
		);
	}
}
