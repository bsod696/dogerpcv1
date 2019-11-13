<?php
 
use App\Setting;
use App\PriceCrypto;
use App\WalletAddress;
use App\User;
use App\Withdrawal;
use App\TransLND;
use App\TransUser;
use App\Currency;
use Carbon\Carbon;

use App\Mail\emailBasic;
use App\Mail\emailAttachment;
 

///////////////////////////////////////////////////////////////
/// SETTINGS /////////////////////////////////////////////////
/////////////////////////////////////////////////////////////
function settings($value){
    $setting = Setting::first();
    return $setting->$value;
}
 
///////////////////////////////////////////////////////////////
/// SEND EMAIL /////////////////////////////////////////////////
/////////////////////////////////////////////////////////////
 
function send_email_basic002($to, $subject, $name, $message){
  $msgData = array(
    "uname" => $name,
    "msg" => $message,
    "subject" => $subject,
    "supportemail" => "info@pinkexc.com"
  );
  Mail::to($to)->send(new emailBasic($msgData));
}

function send_email_attach($to, $subject, $from_email, $name, $message, $attachment, $filename){
  $msgData = array(
    "uname" => $name,
    "msg" => $message,
    "subject" => $subject,
    "fromemail" => $from_email,
    "attachment" => $attachment,
    "filename" => $filename,
    "supportemail" => "info@pinkexc.com"
  );
  //dd($msgData);
  Mail::to($to)->send(new emailAttachment($msgData));
}

 

/////////////////////////////////////////////////////////////////////
///  CONN CRYPTO                     ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function getconnection(){
    $crycode = 'dogecoin';
    $conn = bitcoind()->client($crycode)->getBlockchainInfo()->get();
    return $conn;
}



///////////////////////////////////////////////////////////////
/// BLOCKHASH BY BLOCKID /////////////////////////////////////////////////
/////////////////////////////////////////////////////////////
function getblockhash($blockid) {
    $crycode = 'dogecoin';
    $blockhash = bitcoind()->client($crycode)->getblockhash($blockid)->get();
    return $blockhash;
}



///////////////////////////////////////////////////////////////
/// BLOCKTIME BY BLOCKHASH /////////////////////////////////////////////////
/////////////////////////////////////////////////////////////
function getblockdet($blockhash) {
    $crycode = 'dogecoin';
    $blockhash = bitcoind()->client($crycode)->getblock($blockhash)->get();
    return $blockhash;
}



///////////////////////////////////////////////////////////////
/// ESTIMATE NETWORK FEE /////////////////////////////////////////////////
/////////////////////////////////////////////////////////////
function getestimatefee() {
    $numberblock = 25;
    $crycode = 'dogecoin';
    $fee = number_format(bitcoind()->client($crycode)->estimatesmartfee($numberblock)->get()['feerate'], 8, '.', '');
    return $fee;
}
 

/////////////////////////////////////////////////////////////////////
///  BALANCE                    ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function getbalance($address) {
    $addrdet = json_decode(file_get_contents('https://api.blockchair.com/dogecoin/dashboards/address/'.$address), TRUE);
    if($addrdet['data']) {
        $wallet_balance = number_format($addrdet['data'][$address]['address']['balance'], 8, '.', '');
        WalletAddress::where('address', $address)->update(['balance' => $wallet_balance]);
        return $wallet_balance;
    }
    else{return null;}
}

 

/////////////////////////////////////////////////////////////////////
///  ADDRESS                      ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function getaddress($label) { 
    getbalance($crypto, $label);
    $wallet_address = bitcoind()->client('dogecoin')->getaddressesbyaccount($label)->get()[0];
    return $wallet_address;
}
 

/////////////////////////////////////////////////////////////////////
///  ADD CRYPTO              ///////////////////////////////////////
////////////////////////////////////////////////////////////////////

function addCrypto($label) {
    bitcoind()->client('dogecoin')->getnewaddress($label)->get();
    $wallet_address = bitcoind()->client('dogecoin')->getnewaddress($label)->get();
    return $wallet_address;
}

function get_label_crypto($address) {
    $addrinfo = bitcoind()->client('dogecoin')->getaccount($address)->get();
    
    if($addrinfo != null){
        $label = $addrinfo;
        return $label;
    }
    else{return null;}
}


/////////////////////////////////////////////////////////////////////
///  TRANSACTIONS                 ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function listransactionall() {
    $crycode = 'dogecoin';
    //GET all transaction
    $transaction = bitcoind()->client($crycode)->listtransactions()->get();
    if($transaction){return $transaction;}
    else{return null;}
}

function listransaction($address) {
    $addrdet = json_decode(file_get_contents('https://api.blockchair.com/dogecoin/dashboards/address/'.$address), TRUE);
    if($addrdet['data']) {
        //$balance = number_format($addrdet['data'][$address]['address']['balance'], 8, '.', '');
        $txarr[0] = $addrdet['data'][$address]['transactions'];

        foreach ($txarr[0] as $tx) {
            $txdet = json_decode(file_get_contents('https://api.blockchair.com/dogecoin/dashboards/transaction/'.$tx), TRUE);
            if($txdet['data']) {
                $blockid = $txdet['data'][$tx]['transaction']['block_id'];
                $blockhash = getblockhash($blockid);
                $blocktime = getblockdet($blockhash)['time'];
                $timeraw = $txdet['data'][$tx]['transaction']['time'];
                $time = strtotime($timeraw);
                $timereceived = strtotime($timeraw);
                $txid = $tx;
                $net_fee = number_format($txdet['data'][$tx]['transaction']['fee'], 8, '.', '');
                $net_fee_usd = number_format($txdet['data'][$tx]['transaction']['fee_usd'], 8, '.', '');
                $amount = number_format($txdet['data'][$tx]['outputs'][0]['value'], 8, '.', '');
                $amount_usd = number_format($txdet['data'][$tx]['outputs'][0]['value_usd'], 2, '.', '');
                $conf = getblockdet($blockhash)['confirmations'];
                
                $recipent = $txdet['data'][$tx]['outputs'][0]['recipient'];
                if ($recipent == $address) {$txtype = 'receive';}
                else{$txtype = "send";}

                $currency = 'USD';
                $remarks = strtoupper($txtype);
                $userdet = array('uid' => 666);

                //$rate = json_decode(file_get_contents('https://api.coingecko.com/api/v3/simple/price?ids=dogecoin&vs_currencies=usd'), TRUE);
                $rate = number_format(strval($net_fee_usd/($net_fee/100000000)), 8, '.', '');

                $transaction[] = array(
                    'uid' => '1661',//$userdet->uid,
                    'status' => 'success',
                    'blockid' => $blockid,
                    'blockhash' => $blockhash,
                    'blocktime' => $blocktime,
                    'txid' => $tx,
                    'net_fee' => $net_fee,
                    'net_fee_usd' => $net_fee_usd,
                    'amount' => $amount,
                    'amount_usd' => $amount_usd,
                    'currency' => $currency,
                    'rate' => $rate,
                    'recipent' => $recipent,
                    'txtype' => $txtype,
                    'confirmation' => $conf,
                    'remarks' => strtoupper($txtype),
                    'txdate' => $timeraw,
                    'time' => strtotime($timeraw),
                    'timereceived' => strtotime($timeraw),
                );

                // $transaction_count = sizeof($transaction);
                // if($transaction_count>0){ 
                //     if(!isset($transaction[0]['time'])){
                //         //$userdetfromuid = WalletAddress::where('uid', $transaction['uid'])->where('crypto', $crypto)->first();

                //         if(array_key_exists('time',$transaction)){
                //             $starT = $transaction['time'] - 10000;
                //             $endT = $transaction['time'] + 10000;                
                //         }
                //         else{
                //             $starT = $transaction['timereceived'] - 10000;
                //             $endT = $transaction['timereceived'] + 10000;
                //         }

                //         $priceA = json_decode(file_get_contents('https://api.coingecko.com/api/v3/coins/dogecoin/market_chart/range?vs_currencies=usd&from='.$starT.'&to='.$endT), TRUE)['prices'];
                //         // $json_string = settings('url_gecko').'coins/'.$id_gecko.'/market_chart/range?vs_currency='.$idcurrency.'&from='.$starT.'&to='.$endT;
                //         // $jsondata = file_get_contents($json_string);
                //         // $obj = json_decode($jsondata, TRUE); 
                //         // $priceA = $obj['prices'];
                        
                //         for($i=0;$i<count($priceA);$i++){
                //             $price[] = $priceA[$i][0];
                //         }
                        
                //         foreach ($price as $i) {
                //             if(array_key_exists('time',$transaction)){$smallest[$i] = abs($i - $transaction['time']);}
                //             else{$smallest[$i] = abs($i - $transaction['timereceived']);    }
                //         }

                //         asort($smallest); 
                //         $ids = array_search(key($smallest),$price);
                //         //$totaldis = disply_convert($crypto,$userdetfromuid->value_display,$transaction['amount']);

                //         $info = array(
                //             'price_lock' => number_format($priceA[$ids][1], 2, '.', ''),
                //             //'totaldis' => number_format($totaldis, 8, '.', '').' '.$userdetfromuid->value_display,
                //             //'totaldis5D' => sprintf("%.5f", $totaldis).' '.$userdetfromuid->value_display,
                //             //'value_display' => $userdetfromuid->value_display,
                //             'tran' => $transaction,
                //         );
                //     }
                //     else{
                //         foreach($transaction as $key => $trans){ 
                //             if(array_key_exists('time',$trans)){
                //                 $starT = $trans['time'] - 10000;
                //                 $endT = $trans['time'] + 10000;                
                //             }
                //             else{
                //                 $starT = $trans['timereceived'] - 10000;
                //                 $endT = $trans['timereceived'] + 10000;
                //             }

                //             $priceA = json_decode(file_get_contents('https://api.coingecko.com/api/v3/coins/dogecoin/market_chart/range?vs_currencies=usd&from='.$starT.'&to='.$endT), TRUE)['prices'];
                //             // $json_string = settings('url_gecko').'coins/'.$id_gecko.'/market_chart/range?vs_currency='.$idcurrency.'&from='.$starT.'&to='.$endT;
                //             // $jsondata = file_get_contents($json_string);
                //             // $obj = json_decode($jsondata, TRUE); 
                //             // $priceA = $obj['prices'];

                //             //echo count($priceA);

                //             for($i=0;$i<count($priceA);$i++){
                //                 $price[] = $priceA[$i][0];
                //             }

                //             foreach ($price as $i) {
                //                 if(array_key_exists('time',$trans)){$smallest[$i] = abs($i - $trans['time']);}
                //                 else{$smallest[$i] = abs($i - $trans['timereceived']);}
                //             }

                //             asort($smallest); 
                //             $ids = array_search(key($smallest),$price);

                //             $userdetfromuid = WalletAddress::where('uid', $trans['uid'])->where('crypto', $crypto)->first();
                //             $initlabel = $userdetfromuid->label;
                        
                //             //$totaldis = disply_convert($crypto,$userdetfromuid->value_display,$trans['amount']);

                //             $info[] = array(
                //                 'price_lock' => number_format($priceA[0][1], 2, '.', ''),
                //                 //'totaldis' => number_format($totaldis, 8, '.', '').' '.$userdetfromuid->value_display,
                //                 //'totaldis5D' => sprintf("%.5f", $totaldis).' '.$userdetfromuid->value_display,
                //                 //'value_display' => $userdetfromuid->value_display,
                //                 'tran' => array(
                //                     'account' => $initlabel,
                //                     'address' =>  $trans['recipient'],
                //                     'category' =>  $trans['category'],
                //                     'amount' => floatval($trans['amount']),
                //                     'label' =>  $trans['recipient_id'],
                //                     'confirmations' =>  intval($trans['confirmation']),
                //                     'blockhash' => $trans['blockhash'],
                //                     //'blockindex' => 89,
                //                     'blocktime' => $trans['blocktime'],
                //                     'txid' => $trans['txid'],
                //                     // 'walletconflicts' => [],
                //                     'time' =>  intval($trans['time']),
                //                     'timereceived' =>  intval($trans['timereceived'])
                //                 ),
                //             );
                //         }
                //     }
                //     return $info;
                // }
                // else{return null;}
            }
        }
        return $transaction;
    }
    else{return null;}

    // $tx = "c261de28fc6d2ef6a4151e8ec96a7e3ae32e3bbcc8e22559671d2f41a7f6a034";
    // $txdet = json_decode(file_get_contents('https://api.blockchair.com/dogecoin/dashboards/transaction/'.$tx), TRUE);
    // if($txdet['data']) {
    //     $blockid = $txdet['data'][$tx]['transaction']['block_id'];
    //     $blockhash = getblockhash($blockid);
    //     $blocktime = getblockdet($blockhash)['time'];
    //     $timestamp = $txdet['data'][$tx]['transaction']['time'];
    //     $net_fee = number_format($txdet['data'][$tx]['transaction']['fee'], 8, '.', '');
    //     $amount = number_format($txdet['data'][$tx]['outputs'][0]['value'], 8, '.', '');
    //     $conf = getblockdet($blockhash)['confirmations'];
        
    //     $outaddr = $txdet['data'][$tx]['outputs'][0]['recipient'];
    //     if ($outaddr == $address) {$txtype = 'RECEIVE';}
    //     else{$txtype = "SEND";}
    // }
    // else{return null;}

}
function listransactionOLD($label, $idcurrency, $id_gecko) {
    $crycode = 'dogecoin';
    $user = WalletAddress::where('label', $label)->first();
    $userid = $user->uid; 
        
    //GET label transaction
    $alltransbtc[] = bitcoind()->client($crycode)->listtransactions($label, 1000000, 0)->get();
    foreach ($alltransbtc as $transbtc) {
        if(!array_key_exists('address', $transbtc)){
            foreach ($transbtc as $tbtc) {
                if(isset($tbtc['label'])){
                    $label = $tbtc['label'];
                    $usrtx = User::where('label', $label)->first();
                }
                if(isset($tbtc['confirmations'])){$confirmations = $tbtc['confirmations'];}
                if(isset($tbtc['txid'])){$txid = $tbtc['txid'];}
                if(isset($tbtc['timereceived'])){$timereceived = $tbtc['timereceived'];}


                $crosscheck = TransUser::where('uid' ,$usrtx->uid)->where('category', 'send')->where('txid', $txid)->count();
                if($crosscheck == 0) {
                    $checktx = TransUser::where('category', 'receive')->where('txid', $txid)->count();
                    if($checktx == 0) {
                        $userdet = WalletAddress::where('crypto', $crypto)->where('label', $label)->first();
                        $recvuid = $userdet->uid;
                        $amount = number_format($tbtc['amount'], 8, '.', '');
                        $before_bal = number_format($userdet->balance/100000000, 8, '.', '');
                        $after_bal = number_format(getbalance($crypto, $label)/100000000, 8, '.', '');

                        $useruid = User::where('label', $label)->first();   
                        $priceApi = PriceCrypto::where('crypto', $crypto)->first();     
                        $currency = Currency::where('id', $useruid->currency)->first();
                        $json_string = settings('url_gecko').'simple/price?ids='.$priceApi->id_gecko.'&vs_currencies='.strtolower($currency->code);
                        $jsondata = file_get_contents($json_string);
                        $obj = json_decode($jsondata, TRUE); 
                        $price = number_format($obj[$priceApi->id_gecko][strtolower($currency->code)], 2, '.', '');
                        $myr_amt = number_format($amount*$price, 2, '.', '');

                        $btctx = TransUser::create([
                            'uid' => $userdet->uid,
                            'type' => 'external',
                            'crypto' => $crypto,
                            'category' => $tbtc['category'],
                            'using' => 'mobile',
                            'status' => 'success',
                            'error_code' => '',
                            'recipient' => $tbtc['address'],
                            'recipient_id' => $label,
                            'txid' => $txid,
                            'confirmations' => $confirmations,
                            'amount' => $amount,
                            'before_bal' => $before_bal,
                            'after_bal' => $after_bal,
                            'myr_amount' => $myr_amt,
                            'rate' => $price,
                            'currency' => $useruid->currency,
                            'netfee' => "0.00000000",
                            'walletfee' => "0.00000000",
                            'remarks' => 'RECEIVE',
                            'time' => $timereceived,
                            'timereceived' => $timereceived,
                            'txdate' => date_format(Carbon::createFromTimestamp($timereceived), "Y-m-d H:i:s"),
                            'vout' => $tbtc['vout'],
                            'blockhash' => $tbtc['blockhash'],
                            'blockindex' => $tbtc['blockindex'],
                            'blocktime' => date_format(Carbon::createFromTimestamp($tbtc['blocktime']), "Y-m-d H:i:s"),
                            'walletconflicts' => ''
                        ]);
                    }
                    else {$btctx = TransUser::where('category', 'receive')->where('txid', $txid)->update(['confirmation' => $confirmations]);}
                }           
            }
        }
        else {
            $usrtx = User::where('label', $transbtc['label'])->first();
            $crosscheck = TransUser::where('uid' ,$usrtx->uid)->where('category', 'send')->where('txid', $transbtc['txid'])->count();
            
            if($crosscheck == 0) {
                $checktx = TransUser::where('category', 'receive')->where('txid', $transbtc['txid'])->count();
                
                if($checktx == 0) {
                    $userdet = WalletAddress::where('crypto', $crypto)->where('label', $transbtc['label'])->first();
                    $recvuid = $userdet->uid;
                    $amount = number_format($transbtc['amount'], 8, '.', '');
                    $before_bal = number_format($userdet->balance/100000000, 8, '.', '');
                    $after_bal = number_format(getbalance($crypto, $transbtc['label'])/100000000, 8, '.', '');

                    $useruid = User::where('label',$transbtc['label'])->first();   
                    $priceApi = PriceCrypto::where('crypto', $crypto)->first();     
                    $currency = Currency::where('id',$useruid->currency)->first();
                    $json_string = settings('url_gecko').'simple/price?ids='.$priceApi->id_gecko.'&vs_currencies='.strtolower($currency->code);
                    $jsondata = file_get_contents($json_string);
                    $obj = json_decode($jsondata, TRUE); 
                    $price = number_format($obj[$priceApi->id_gecko][strtolower($currency->code)], 2, '.', '');
                    $myr_amt = number_format($amount*$price, 2, '.', '');

                    $btctx = TransUser::create([
                        'uid' => $userdet->uid,
                        'type' => 'external',
                        'crypto' => $crypto,
                        'category' => $transbtc['category'],
                        'using' => 'mobile',
                        'status' => 'success',
                        'error_code' => '',
                        'recipient' => $transbtc['address'],
                        'recipient_id' => $label,
                        'txid' => $transbtc['txid'],
                        'confirmations' => $transbtc['confirmations'],
                        'amount' => $amount,
                        'before_bal' => $before_bal,
                        'after_bal' => $after_bal,
                        'myr_amount' => $myr_amt,
                        'rate' => $price,
                        'currency' => $useruid->currency,
                        'netfee' => "0.00000000",
                        'walletfee' => "0.00000000",
                        'remarks' => 'RECEIVE',
                        'time' => $transbtc['timereceived'],
                        'timereceived' => $transbtc['timereceived'],
                        'txdate' => date_format(Carbon::createFromTimestamp($transbtc['timereceived']), "Y-m-d H:i:s"),
                        'vout' => $transbtc['vout'],
                        'blockhash' => $transbtc['blockhash'],
                        'blockindex' => $transbtc['blockindex'],
                        'blocktime' => date_format(Carbon::createFromTimestamp($transbtc['blocktime']), "Y-m-d H:i:s"),
                        'walletconflicts' => ''
                    ]);
                }
                else{
                    $btctx = TransUser::where('category', 'receive')->where('txid', $transbtc['txid'])->update([
                        'confirmation' => $transbtc['confirmations']
                    ]);
                }
            }
        }
    }
    $transaction = TransUser::where('crypto', $crypto)->where('uid', $userid)->orderBy('txdate','asc')->get();
    $transaction_count = TransUser::where('crypto', $crypto)->where('uid', $userid)->orderBy('txdate','asc')->count();

    if($transaction_count>0){ 
        if(!isset($transaction[0]['time'])){
            $userdetfromuid = WalletAddress::where('uid', $transaction['uid'])->where('crypto', $crypto)->first();

            if(array_key_exists('time',$transaction)){
                $starT = $transaction['time'] - 10000;
                $endT = $transaction['time'] + 10000;                
            }
            else{
                $starT = $transaction['timereceived'] - 10000;
                $endT = $transaction['timereceived'] + 10000;
            }

            $json_string = settings('url_gecko').'coins/'.$id_gecko.'/market_chart/range?vs_currency='.$idcurrency.'&from='.$starT.'&to='.$endT;
            $jsondata = file_get_contents($json_string);
            $obj = json_decode($jsondata, TRUE); 
            $priceA = $obj['prices'];
                
            for($i=0;$i<count($priceA);$i++){
                $price[] = $priceA[$i][0];
            }
                
            foreach ($price as $i) {
                if(array_key_exists('time',$transaction)){
                    $smallest[$i] = abs($i - $transaction['time']);
                }
                else{
                    $smallest[$i] = abs($i - $transaction['timereceived']);    
                }
            }

            asort($smallest); 
            $ids = array_search(key($smallest),$price);
            $totaldis = disply_convert($crypto,$userdetfromuid->value_display,$transaction['amount']);

            $info = array(
                'price_lock' => number_format($priceA[$ids][1], 2, '.', ''),
                'totaldis' => number_format($totaldis, 8, '.', '').' '.$userdetfromuid->value_display,
                'totaldis5D' => sprintf("%.5f", $totaldis).' '.$userdetfromuid->value_display,
                'value_display' => $userdetfromuid->value_display,
                'tran' => $transaction,
            );
        }
        else{
            foreach($transaction as $key => $trans){ 
                if(array_key_exists('time',$trans)){
                    $starT = $trans['time'] - 10000;
                    $endT = $trans['time'] + 10000;                
                }
                else{
                    $starT = $trans['timereceived'] - 10000;
                    $endT = $trans['timereceived'] + 10000;
                }

                $json_string = settings('url_gecko').'coins/'.$id_gecko.'/market_chart/range?vs_currency='.$idcurrency.'&from='.$starT.'&to='.$endT;
                $jsondata = file_get_contents($json_string);
                $obj = json_decode($jsondata, TRUE); 
                $priceA = $obj['prices'];

                //echo count($priceA);

                for($i=0;$i<count($priceA);$i++){
                        $price[] = $priceA[$i][0];
                    }

                    foreach ($price as $i) {
                        if(array_key_exists('time',$trans)){
                            $smallest[$i] = abs($i - $trans['time']);
                        }
                        else{
                            $smallest[$i] = abs($i - $trans['timereceived']);    
                        }
                    }

                    asort($smallest); 
                    $ids = array_search(key($smallest),$price);

                    $userdetfromuid = WalletAddress::where('uid', $trans['uid'])->where('crypto', $crypto)->first();
                    $initlabel = $userdetfromuid->label;
                
                    $totaldis = disply_convert($crypto,$userdetfromuid->value_display,$trans['amount']);

                    $info[] = array(
                        'price_lock' => number_format($priceA[0][1], 2, '.', ''),
                        'totaldis' => number_format($totaldis, 8, '.', '').' '.$userdetfromuid->value_display,
                        'totaldis5D' => sprintf("%.5f", $totaldis).' '.$userdetfromuid->value_display,
                        'value_display' => $userdetfromuid->value_display,
                        'tran' => array(
                            'account' => $initlabel,
                            'address' =>  $trans['recipient'],
                            'category' =>  $trans['category'],
                            'amount' => floatval($trans['amount']),
                            'label' =>  $trans['recipient_id'],
                            'vout' => 3,
                            'confirmations' =>  intval($trans['confirmation']),
                            'blockhash' => '0000000000000000005fca13b9f9fe8a5763730f15cc41182f0ea4bf90789564',
                            'blockindex' => 89,
                            'blocktime' => 1567400116,
                            'txid' => $trans['txid'],
                            'walletconflicts' => [],
                            'time' =>  intval($trans['time']),
                            'timereceived' =>  intval($trans['timereceived'])
                        ),
                    );
                
                }
            }
            return $info;
        }
        else{return null;}
}

/////////////////////////////////////////////////////////////////////
///  ADMIN GET TRANSACTION             ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function gettransaction_crypto($txid) {
    $transaction = bitcoind()->client('dogecoin')->gettransaction($txid)->get();
    return $transaction;
}


/////////////////////////////////////////////////////////////////////
///  CHECK ADDRESS           ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function checkAddress($address) {
    $valid = bitcoind()->client('dogecoin')->validateaddress($address)->get()['isvalid'];
    return $valid;
}


/////////////////////////////////////////////////////////////////////
///  PAYMENT / WITHDRAW / SEND                       ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function move_crypto($crypto, $label, $label2, $amount) {
    getbalance($label);
    getbalance($label2);

    $txid = bitcoind()->client('dogecoin')->move($label, $label2, $amount)->get();

    getbalance($label);
    getbalance($label2);

    if($txid != ''){return $txid;}
    else{return null;}
}


////////////////////////////////////////////////////////////////////
////////////////////////////move with comment////////////////////////
//////////////////////////////////////////////////////////////////////
function move_crypto_comment($crypto, $label, $label2, $amount, $comment) {
    getbalance($label);
    getbalance($label2);

    $txid = bitcoind()->client('dogecoin')->move($label, $label2, $amount, $comment)->get();

    getbalance($label);
    getbalance($label2);

    if($txid != ''){return $txid;}
    else{return null;}
}


function sendtoaddressRAW($label, $recvaddress, $cryptoamount, $memo, $comm_fee) {
    $pxfeeaddr = WalletAddress::where('crypto', $crypto)->where('label', 'usr_doradofees')->first()->address;;
        $pxfee = $comm_fee;
        $balance = number_format(getbalance($crypto, $label)/100000000, 8, '.', '');
        $estfee = getestimatefee($crypto);
        $total =  number_format(($cryptoamount+$estfee+$pxfee), 8, '.', '');
        $j = 0;
        $balacc[] = bitcoind()->client('dogecoin')->listunspent()->get();
        $prevtxn[] = null;
        $totalin = 0;
        foreach ($balacc as $acc) {
            $ac[$j] = $acc;
            foreach ($ac as $a) {
                $i = 0;
                foreach ($a as $x) {
                    if(in_array('label', $x) == $label){
                        $txid[$i] = $x['txid'];
                        $vout[$i] = $x['vout'];
                        $amt[$i] = number_format($x['amount'], 8, '.', '');
                        $totalin += $amt[$i];
                        $prevtxn[$i] = array(
                            "txid"=>$txid[$i],
                            "vout"=>$vout[$i],
                        );
                        $i++;
                        if($totalin > $total){break;} 
                    }
                }
            }
            $j++;
            $txin = array_filter($prevtxn);
        }
        $change = number_format($totalin-$total, 8, '.', '');
        if(bitcoind()->client('dogecoin')->getaddressesbyaccount($label)->get()[1]){
            $changeaddr = bitcoind()->client('dogecoin')->getaddressesbyaccount($label)->get()[1];
        }
        else{
            $changeaddr = bitcoind()->client('dogecoin')->getnewaddress($label)->get();
        }
        if($balance >= $total){  
            $createraw = bitcoind()->client('dogecoin')->createrawtransaction(
                $txin,
                array(
                    $recvaddress=>number_format($cryptoamount, 8, '.', ''),
                    $changeaddr=>$change,
                    $pxfeeaddr => $pxfee
                )
            )->get();
            $signing = bitcoind()->client('dogecoin')->signrawtransaction($createraw)->get();
            $decode = bitcoind()->client('dogecoin')->decoderawtransaction($signing['hex'])->get();
            //dd("Fee: ".$estfee, "Cost: ".$total, "Input: ".$totalin, "Change: ".$change, "Before Balance: ".$balance, $decode);
            if($signing['complete'] == true){
                $txid = bitcoind()->client('dogecoin')->sendrawtransaction($signing['hex'])->get();
                getbalance($crypto, $label);
                return $txid;
            }
            else{
                $msg = array('error'=>"Signing Failed. ".$decode);
                return $msg;
            }
        }
        else{
            $msg = array('error'=>"Insufficient fund. You need at least ".$total." ".$crypto." to perform this transaction");
            return $msg;
        }
}
////////////////////////////////////////////////////////////////////
function sendtomanyaddress($sendlabel, $recvaddress, $cryptoamount, $memo, $comm_fee) {
    $pxfeeaddr = bitcoind()->client('dogecoin')->getaddressesbyaccount('usr_doradofees')->get()[0];
        $pxfee = $comm_fee;
        $bal = getbalance($crypto, $sendlabel);
        $estfee = getestimatefee($crypto);
        $txcost =  number_format(($cryptoamount+$estfee+$pxfee)*100000000, 0, '.', '');

        if ($bal >= $txcost){
            $txid = bitcoind()->client('dogecoin')->sendmany("",
                array(
                    $recvaddress => $cryptoamount,
                    $pxfeeaddr => $pxfee
                )
            )->get();
            getbalance($crypto, $sendlabel);
            return $txid;
        }
        else{
            $msg = array('error'=>"Insufficient fund. You need at least ".($txcost/'10000000')." ".$crypto." to perform this transaction");
            return $msg;
        }
}


/////////////////////////////////////////////////////////////////////
/// DUMP PRIVATE KEY             ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function dumpkey($label){
    $crycode = 'dogecoin';
        $addressarr = bitcoind()->client($crycode)->getaddressesbyaccount($label)->get();
        foreach ($addressarr as $addr){
            $priv = bitcoind()->client($crycode)->dumpprivkey($addr)->get();
            $data[] = array(
                "address"=>$addr,
                "key"=>$priv
            );
        }
        return $data;
}


/////////////////////////////////////////////////////////////////////
///  DEC2HEX                ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function dec2hex($number){
    $hexvalues = array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
    $hexval = '';
    while($number != '0'){
        $hexval = $hexvalues[bcmod($number,'16')].$hexval;
        $number = bcdiv($number,'16',0);
    }
    return $hexval;
}


/////////////////////////////////////////////////////////////////////
///  WITHDRAWAL WITHOUT OWNER FEES        ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function withdrawal_admin_crypto($sendlabel, $recvaddress, $cryptoamount, $memo) {
    $balance = number_format(getbalance($crypto, $sendlabel)/100000000, 8, '.', '');
        $estfee = number_format(bitcoind()->client('dogecoin')->estimatesmartfee(6)->get()['feerate'], 8, '.', '');
        $total =  number_format(($cryptoamount+$estfee), 8, '.', '');
        $addressarr = array_keys(bitcoind()->client('dogecoin')->getaddressesbylabel($sendlabel)->get());
        foreach ($addressarr as $address) {
            $j = 0;
            $balacc[] = bitcoind()->client('dogecoin')->listunspent(1, 9999999, [$address])->get();
            $prevtxn[] = null;
            $totalin = 0;
            foreach ($balacc as $acc) {
                $i = 0;
                $ac[$j] = $acc;
                foreach ($acc as $a) {
                    $txid[$i] = $a['txid'];
                    $vout[$i] = $a['vout'];
                    $amt[$i] = $a['amount']; 
                    $scriptPubKey[$i] = $a['scriptPubKey'];
                    $totalin += $amt[$i];
                    $prevtxn[$i] = array(
                        "txid"=>$txid[$i],
                        "vout"=>$vout[$i],
                    );
                    $i++; 
                    if($totalin > $total){break;} 
                }
                $j++;
            }
            $txin = array_filter($prevtxn);
        }
        $change = number_format($totalin-$total, 8, '.', '');
        $changeaddr = array_keys(bitcoind()->client('dogecoin')->getaddressesbylabel($sendlabel)->get())[0];
        if($balance >= $total){  
            $createraw = bitcoind()->client('dogecoin')->createrawtransaction(
                $txin,
                array(
                    $recvaddress=>number_format($cryptoamount, 8, '.', ''),
                    $changeaddr=>$change
                )
            )->get();
            $signing = bitcoind()->client('dogecoin')->signrawtransactionwithwallet($createraw)->get();
            $decode = bitcoind()->client('dogecoin')->decoderawtransaction($signing['hex'])->get();
            //dd("Fee: ".$estfee, "Cost: ".$total, "Input: ".$totalin, "Change: ".$change, "Before Balance: ".$balance, $decode);
            if($signing['complete'] == true){
                $txid = bitcoind()->client('dogecoin')->sendrawtransaction($signing['hex'])->get();
                return $txid;
            }
           else{
                $msg = array('error'=>"Signing Failed. ".$decode);
                return $msg;
            }
        }
        else{
            $msg = array('error'=>"Insufficient fund. You need at least ".$total." ".$crypto." to perform this transaction");
            return $msg;
        }  
}


/////////////////////////////////////////////////////////////////////
///  GET BALANCE ALL          ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function getbalanceAll() {
    $wallet_balance = bitcoind()->client('dogecoin')->getbalance()->get();
    return $wallet_balance;
}


/////////////////////////////////////////////////////////////////////
///  API FUNCTION          ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function apiToken($session_uid){
    $key=md5('Dorado2019'.$session_uid);
    return hash('sha256', $key);
}

/////////////////////////////////////////////////////////////////////
///  MNEMONIC FUNCTION          ///////////////////////////////////////
////////////////////////////////////////////////////////////////////
function genseed($session_uid){
    $entr = apiToken($session_uid);
    $entr_arr = str_split($entr);
    $hexarr = array(
        '0' => '0',
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
        '6' => '6',
        '7' => '7',
        '8' => '8',
        '9' => '9',
        '10' => 'a',
        '11' => 'b',
        '12' => 'c',
        '13' => 'd',
        '14' => 'e',
        '15' => 'f',
    );
    $wordlist = array("abandon","ability","able","about","above","absent","absorb","abstract","absurd","abuse","access","accident","account","accuse","achieve","acid","acoustic","acquire","across","act","action","actor","actress","actual","adapt","add","addict","address","adjust","admit","adult","advance","advice","aerobic","affair","afford","afraid","again","age","agent","agree","ahead","aim","air","airport","aisle","alarm","album","alcohol","alert","alien","all","alley","allow","almost","alone","alpha","already","also","alter","always","amateur","amazing","among","amount","amused","analyst","anchor","ancient","anger","angle","angry","animal","ankle","announce","annual","another","answer","antenna","antique","anxiety","any","apart","apology","appear","apple","approve","april","arch","arctic","area","arena","argue","arm","armed","armor","army","around","arrange","arrest","arrive","arrow","art","artefact","artist","artwork","ask","aspect","assault","asset","assist","assume","asthma","athlete","atom","attack","attend","attitude","attract","auction","audit","august","aunt","author","auto","autumn","average","avocado","avoid","awake","aware","away","awesome","awful","awkward","axis","baby","bachelor","bacon","badge","bag","balance","balcony","ball","bamboo","banana","banner","bar","barely","bargain","barrel","base","basic","basket","battle","beach","bean","beauty","because","become","beef","before","begin","behave","behind","believe","below","belt","bench","benefit","best","betray","better","between","beyond","bicycle","bid","bike","bind","biology","bird","birth","bitter","black","blade","blame","blanket","blast","bleak","bless","blind","blood","blossom","blouse","blue","blur","blush","board","boat","body","boil","bomb","bone","bonus","book","boost","border","boring","borrow","boss","bottom","bounce","box","boy","bracket","brain","brand","brass","brave","bread","breeze","brick","bridge","brief","bright","bring","brisk","broccoli","broken","bronze","broom","brother","brown","brush","bubble","buddy","budget","buffalo","build","bulb","bulk","bullet","bundle","bunker","burden","burger","burst","bus","business","busy","butter","buyer","buzz","cabbage","cabin","cable","cactus","cage","cake","call","calm","camera","camp","can","canal","cancel","candy","cannon","canoe","canvas","canyon","capable","capital","captain","car","carbon","card","cargo","carpet","carry","cart","case","cash","casino","castle","casual","cat","catalog","catch","category","cattle","caught","cause","caution","cave","ceiling","celery","cement","census","century","cereal","certain","chair","chalk","champion","change","chaos","chapter","charge","chase","chat","cheap","check","cheese","chef","cherry","chest","chicken","chief","child","chimney","choice","choose","chronic","chuckle","chunk","churn","cigar","cinnamon","circle","citizen","city","civil","claim","clap","clarify","claw","clay","clean","clerk","clever","click","client","cliff","climb","clinic","clip","clock","clog","close","cloth","cloud","clown","club","clump","cluster","clutch","coach","coast","coconut","code","coffee","coil","coin","collect","color","column","combine","come","comfort","comic","common","company","concert","conduct","confirm","congress","connect","consider","control","convince","cook","cool","copper","copy","coral","core","corn","correct","cost","cotton","couch","country","couple","course","cousin","cover","coyote","crack","cradle","craft","cram","crane","crash","crater","crawl","crazy","cream","credit","creek","crew","cricket","crime","crisp","critic","crop","cross","crouch","crowd","crucial","cruel","cruise","crumble","crunch","crush","cry","crystal","cube","culture","cup","cupboard","curious","current","curtain","curve","cushion","custom","cute","cycle","dad","damage","damp","dance","danger","daring","dash","daughter","dawn","day","deal","debate","debris","decade","december","decide","decline","decorate","decrease","deer","defense","define","defy","degree","delay","deliver","demand","demise","denial","dentist","deny","depart","depend","deposit","depth","deputy","derive","describe","desert","design","desk","despair","destroy","detail","detect","develop","device","devote","diagram","dial","diamond","diary","dice","diesel","diet","differ","digital","dignity","dilemma","dinner","dinosaur","direct","dirt","disagree","discover","disease","dish","dismiss","disorder","display","distance","divert","divide","divorce","dizzy","doctor","document","dog","doll","dolphin","domain","donate","donkey","donor","door","dose","double","dove","draft","dragon","drama","drastic","draw","dream","dress","drift","drill","drink","drip","drive","drop","drum","dry","duck","dumb","dune","during","dust","dutch","duty","dwarf","dynamic","eager","eagle","early","earn","earth","easily","east","easy","echo","ecology","economy","edge","edit","educate","effort","egg","eight","either","elbow","elder","electric","elegant","element","elephant","elevator","elite","else","embark","embody","embrace","emerge","emotion","employ","empower","empty","enable","enact","end","endless","endorse","enemy","energy","enforce","engage","engine","enhance","enjoy","enlist","enough","enrich","enroll","ensure","enter","entire","entry","envelope","episode","equal","equip","era","erase","erode","erosion","error","erupt","escape","essay","essence","estate","eternal","ethics","evidence","evil","evoke","evolve","exact","example","excess","exchange","excite","exclude","excuse","execute","exercise","exhaust","exhibit","exile","exist","exit","exotic","expand","expect","expire","explain","expose","express","extend","extra","eye","eyebrow","fabric","face","faculty","fade","faint","faith","fall","false","fame","family","famous","fan","fancy","fantasy","farm","fashion","fat","fatal","father","fatigue","fault","favorite","feature","february","federal","fee","feed","feel","female","fence","festival","fetch","fever","few","fiber","fiction","field","figure","file","film","filter","final","find","fine","finger","finish","fire","firm","first","fiscal","fish","fit","fitness","fix","flag","flame","flash","flat","flavor","flee","flight","flip","float","flock","floor","flower","fluid","flush","fly","foam","focus","fog","foil","fold","follow","food","foot","force","forest","forget","fork","fortune","forum","forward","fossil","foster","found","fox","fragile","frame","frequent","fresh","friend","fringe","frog","front","frost","frown","frozen","fruit","fuel","fun","funny","furnace","fury","future","gadget","gain","galaxy","gallery","game","gap","garage","garbage","garden","garlic","garment","gas","gasp","gate","gather","gauge","gaze","general","genius","genre","gentle","genuine","gesture","ghost","giant","gift","giggle","ginger","giraffe","girl","give","glad","glance","glare","glass","glide","glimpse","globe","gloom","glory","glove","glow","glue","goat","goddess","gold","good","goose","gorilla","gospel","gossip","govern","gown","grab","grace","grain","grant","grape","grass","gravity","great","green","grid","grief","grit","grocery","group","grow","grunt","guard","guess","guide","guilt","guitar","gun","gym","habit","hair","half","hammer","hamster","hand","happy","harbor","hard","harsh","harvest","hat","have","hawk","hazard","head","health","heart","heavy","hedgehog","height","hello","helmet","help","hen","hero","hidden","high","hill","hint","hip","hire","history","hobby","hockey","hold","hole","holiday","hollow","home","honey","hood","hope","horn","horror","horse","hospital","host","hotel","hour","hover","hub","huge","human","humble","humor","hundred","hungry","hunt","hurdle","hurry","hurt","husband","hybrid","ice","icon","idea","identify","idle","ignore","ill","illegal","illness","image","imitate","immense","immune","impact","impose","improve","impulse","inch","include","income","increase","index","indicate","indoor","industry","infant","inflict","inform","inhale","inherit","initial","inject","injury","inmate","inner","innocent","input","inquiry","insane","insect","inside","inspire","install","intact","interest","into","invest","invite","involve","iron","island","isolate","issue","item","ivory","jacket","jaguar","jar","jazz","jealous","jeans","jelly","jewel","job","join","joke","journey","joy","judge","juice","jump","jungle","junior","junk","just","kangaroo","keen","keep","ketchup","key","kick","kid","kidney","kind","kingdom","kiss","kit","kitchen","kite","kitten","kiwi","knee","knife","knock","know","lab","label","labor","ladder","lady","lake","lamp","language","laptop","large","later","latin","laugh","laundry","lava","law","lawn","lawsuit","layer","lazy","leader","leaf","learn","leave","lecture","left","leg","legal","legend","leisure","lemon","lend","length","lens","leopard","lesson","letter","level","liar","liberty","library","license","life","lift","light","like","limb","limit","link","lion","liquid","list","little","live","lizard","load","loan","lobster","local","lock","logic","lonely","long","loop","lottery","loud","lounge","love","loyal","lucky","luggage","lumber","lunar","lunch","luxury","lyrics","machine","mad","magic","magnet","maid","mail","main","major","make","mammal","man","manage","mandate","mango","mansion","manual","maple","marble","march","margin","marine","market","marriage","mask","mass","master","match","material","math","matrix","matter","maximum","maze","meadow","mean","measure","meat","mechanic","medal","media","melody","melt","member","memory","mention","menu","mercy","merge","merit","merry","mesh","message","metal","method","middle","midnight","milk","million","mimic","mind","minimum","minor","minute","miracle","mirror","misery","miss","mistake","mix","mixed","mixture","mobile","model","modify","mom","moment","monitor","monkey","monster","month","moon","moral","more","morning","mosquito","mother","motion","motor","mountain","mouse","move","movie","much","muffin","mule","multiply","muscle","museum","mushroom","music","must","mutual","myself","mystery","myth","naive","name","napkin","narrow","nasty","nation","nature","near","neck","need","negative","neglect","neither","nephew","nerve","nest","net","network","neutral","never","news","next","nice","night","noble","noise","nominee","noodle","normal","north","nose","notable","note","nothing","notice","novel","now","nuclear","number","nurse","nut","oak","obey","object","oblige","obscure","observe","obtain","obvious","occur","ocean","october","odor","off","offer","office","often","oil","okay","old","olive","olympic","omit","once","one","onion","online","only","open","opera","opinion","oppose","option","orange","orbit","orchard","order","ordinary","organ","orient","original","orphan","ostrich","other","outdoor","outer","output","outside","oval","oven","over","own","owner","oxygen","oyster","ozone","pact","paddle","page","pair","palace","palm","panda","panel","panic","panther","paper","parade","parent","park","parrot","party","pass","patch","path","patient","patrol","pattern","pause","pave","payment","peace","peanut","pear","peasant","pelican","pen","penalty","pencil","people","pepper","perfect","permit","person","pet","phone","photo","phrase","physical","piano","picnic","picture","piece","pig","pigeon","pill","pilot","pink","pioneer","pipe","pistol","pitch","pizza","place","planet","plastic","plate","play","please","pledge","pluck","plug","plunge","poem","poet","point","polar","pole","police","pond","pony","pool","popular","portion","position","possible","post","potato","pottery","poverty","powder","power","practice","praise","predict","prefer","prepare","present","pretty","prevent","price","pride","primary","print","priority","prison","private","prize","problem","process","produce","profit","program","project","promote","proof","property","prosper","protect","proud","provide","public","pudding","pull","pulp","pulse","pumpkin","punch","pupil","puppy","purchase","purity","purpose","purse","push","put","puzzle","pyramid","quality","quantum","quarter","question","quick","quit","quiz","quote","rabbit","raccoon","race","rack","radar","radio","rail","rain","raise","rally","ramp","ranch","random","range","rapid","rare","rate","rather","raven","raw","razor","ready","real","reason","rebel","rebuild","recall","receive","recipe","record","recycle","reduce","reflect","reform","refuse","region","regret","regular","reject","relax","release","relief","rely","remain","remember","remind","remove","render","renew","rent","reopen","repair","repeat","replace","report","require","rescue","resemble","resist","resource","response","result","retire","retreat","return","reunion","reveal","review","reward","rhythm","rib","ribbon","rice","rich","ride","ridge","rifle","right","rigid","ring","riot","ripple","risk","ritual","rival","river","road","roast","robot","robust","rocket","romance","roof","rookie","room","rose","rotate","rough","round","route","royal","rubber","rude","rug","rule","run","runway","rural","sad","saddle","sadness","safe","sail","salad","salmon","salon","salt","salute","same","sample","sand","satisfy","satoshi","sauce","sausage","save","say","scale","scan","scare","scatter","scene","scheme","school","science","scissors","scorpion","scout","scrap","screen","script","scrub","sea","search","season","seat","second","secret","section","security","seed","seek","segment","select","sell","seminar","senior","sense","sentence","series","service","session","settle","setup","seven","shadow","shaft","shallow","share","shed","shell","sheriff","shield","shift","shine","ship","shiver","shock","shoe","shoot","shop","short","shoulder","shove","shrimp","shrug","shuffle","shy","sibling","sick","side","siege","sight","sign","silent","silk","silly","silver","similar","simple","since","sing","siren","sister","situate","six","size","skate","sketch","ski","skill","skin","skirt","skull","slab","slam","sleep","slender","slice","slide","slight","slim","slogan","slot","slow","slush","small","smart","smile","smoke","smooth","snack","snake","snap","sniff","snow","soap","soccer","social","sock","soda","soft","solar","soldier","solid","solution","solve","someone","song","soon","sorry","sort","soul","sound","soup","source","south","space","spare","spatial","spawn","speak","special","speed","spell","spend","sphere","spice","spider","spike","spin","spirit","split","spoil","sponsor","spoon","sport","spot","spray","spread","spring","spy","square","squeeze","squirrel","stable","stadium","staff","stage","stairs","stamp","stand","start","state","stay","steak","steel","stem","step","stereo","stick","still","sting","stock","stomach","stone","stool","story","stove","strategy","street","strike","strong","struggle","student","stuff","stumble","style","subject","submit","subway","success","such","sudden","suffer","sugar","suggest","suit","summer","sun","sunny","sunset","super","supply","supreme","sure","surface","surge","surprise","surround","survey","suspect","sustain","swallow","swamp","swap","swarm","swear","sweet","swift","swim","swing","switch","sword","symbol","symptom","syrup","system","table","tackle","tag","tail","talent","talk","tank","tape","target","task","taste","tattoo","taxi","teach","team","tell","ten","tenant","tennis","tent","term","test","text","thank","that","theme","then","theory","there","they","thing","this","thought","three","thrive","throw","thumb","thunder","ticket","tide","tiger","tilt","timber","time","tiny","tip","tired","tissue","title","toast","tobacco","today","toddler","toe","together","toilet","token","tomato","tomorrow","tone","tongue","tonight","tool","tooth","top","topic","topple","torch","tornado","tortoise","toss","total","tourist","toward","tower","town","toy","track","trade","traffic","tragic","train","transfer","trap","trash","travel","tray","treat","tree","trend","trial","tribe","trick","trigger","trim","trip","trophy","trouble","truck","true","truly","trumpet","trust","truth","try","tube","tuition","tumble","tuna","tunnel","turkey","turn","turtle","twelve","twenty","twice","twin","twist","two","type","typical","ugly","umbrella","unable","unaware","uncle","uncover","under","undo","unfair","unfold","unhappy","uniform","unique","unit","universe","unknown","unlock","until","unusual","unveil","update","upgrade","uphold","upon","upper","upset","urban","urge","usage","use","used","useful","useless","usual","utility","vacant","vacuum","vague","valid","valley","valve","van","vanish","vapor","various","vast","vault","vehicle","velvet","vendor","venture","venue","verb","verify","version","very","vessel","veteran","viable","vibrant","vicious","victory","video","view","village","vintage","violin","virtual","virus","visa","visit","visual","vital","vivid","vocal","voice","void","volcano","volume","vote","voyage","wage","wagon","wait","walk","wall","walnut","want","warfare","warm","warrior","wash","wasp","waste","water","wave","way","wealth","weapon","wear","weasel","weather","web","wedding","weekend","weird","welcome","west","wet","whale","what","wheat","wheel","when","where","whip","whisper","wide","width","wife","wild","will","win","window","wine","wing","wink","winner","winter","wire","wisdom","wise","wish","witness","wolf","woman","wonder","wood","wool","word","work","world","worry","worth","wrap","wreck","wrestle","wrist","write","wrong","yard","year","yellow","you","young","youth","zebra","zero","zone","zoo");

    $binval = null;
    foreach ($entr_arr as $earr) {$binarr[] = str_pad(decbin(array_search($earr, $hexarr)), 4, 0, STR_PAD_LEFT);}
    $binval = implode('', $binarr);
    $crccheck = str_pad(decbin(strlen($entr)/32), 4, 0, STR_PAD_LEFT);
    $crc = $binval.$crccheck;
    $splitbin = str_split($crc, 11);
    $mnemonic = '';
    foreach ($splitbin as $split) {
        if($mnemonic == ''){
            $mnemonic = $wordlist[bindec($split)];
        }else{
            $mnemonic = $mnemonic.' '.$wordlist[bindec($split)];
        }
        //$mnemonic[] = $wordlist[bindec($split)];
    }  
    return  $mnemonic;
}  

/////////////////////////////////////////////////////////////////////
///  CONVERT DISPLAY         ///////////////////////////////////////
//////////////////////////////////////////////////////////////////// 
	function disply_convert($fromconv,$toconv,$nilai){   
		if($fromconv=='BTC'){
			if($toconv=='Bits'){ 
                $total = $nilai * 1000000; 
			}
			else if($toconv=='mBTC'){
				$total = $nilai * 1000;
			}
			else if($toconv=='SAT'){
				$total = $nilai * 100000000;
			}
			else{
				$total = $nilai;
			}
		}  
		else if($fromconv=='mBTC'){
			if($toconv=='Bits'){
				$total = $nilai * 1000;
			}
			else if($toconv=='BTC'){
				$total = $nilai * 0.001;
			}
			else if($toconv=='SAT'){
				$total = $nilai * 100000;
			}
			else{
				$total = $nilai;
			}
		}  
		else if($fromconv=='Bits'){
			if($toconv=='BTC'){
				$total = $nilai * 0.000001;
			}
			else if($toconv=='mBTC'){
				$total = $nilai * 0.001;
			}
			else if($toconv=='SAT'){
				$total = $nilai * 100;
			}
			else{
				$total = $nilai;
			}
		}
		else if($fromconv=='SAT'){
			if($toconv=='Bits'){
				$total = $nilai * 0.01;
			}
			else if($toconv=='mBTC'){
				$total = $nilai * 0.00001;
			}
			else if($toconv=='BTC'){
				$total = $nilai * 0.00000001;
			}
			else{
				$total = $nilai;
			}
		}
		else{
			if($toconv=='mDOGE' || $toconv=='mDASH' || $toconv=='mLTC' || $toconv=='mBCH'){
				$total = $nilai * 1000;
            }
            else if($fromconv=='mDOGE' || $fromconv=='mDASH' || $fromconv=='mLTC' || $fromconv=='mBCH'){
				$total = $nilai * 0.001;
			}
			else{
				$total = $nilai;
			}
		}

		return $total;
 
	}

