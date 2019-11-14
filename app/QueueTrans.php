<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QueueTrans extends Model
{
    protected $table = 'queue_trans';
	protected $fillable = [
		'recipient', 
		'amount', 
		'txid', 
		'status', 
		'remarks',
	];
}
