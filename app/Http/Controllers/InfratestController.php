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
		dd(
			//getconnection()
			// getblockhash($blockid),
			// getblockdet($blockhash),
			// getestimatefee(),
			// getbalance($label),
			// getaddress($label),
			// addCrypto($label),
			// get_label_crypto($address),
			// listransactionall(),
			listransaction($address)
			// gettransaction_crypto($txid),
			// checkAddress($address),
			// sendtoaddressRAW($label, $recvaddress, $cryptoamount, $memo, $comm_fee),
			// sendtomanyaddress($sendlabel, $recvaddress, $cryptoamount, $memo, $comm_fee),
			// dumpkey($label),
			// withdrawal_admin_crypto($sendlabel, $recvaddress, $cryptoamount, $memo) ,
			// getbalanceAll()
		);
	}
}
