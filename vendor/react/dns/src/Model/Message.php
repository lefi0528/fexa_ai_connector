<?php

namespace React\Dns\Model;

use React\Dns\Query\Query;


final class Message
{
    const TYPE_A = 1;
    const TYPE_NS = 2;
    const TYPE_CNAME = 5;
    const TYPE_SOA = 6;
    const TYPE_PTR = 12;
    const TYPE_MX = 15;
    const TYPE_TXT = 16;
    const TYPE_AAAA = 28;
    const TYPE_SRV = 33;
    const TYPE_SSHFP = 44;

    
    const TYPE_OPT = 41;

    
    const TYPE_SPF = 99;

    const TYPE_ANY = 255;
    const TYPE_CAA = 257;

    const CLASS_IN = 1;

    const OPCODE_QUERY = 0;
    const OPCODE_IQUERY = 1; 
    const OPCODE_STATUS = 2;

    const RCODE_OK = 0;
    const RCODE_FORMAT_ERROR = 1;
    const RCODE_SERVER_FAILURE = 2;
    const RCODE_NAME_ERROR = 3;
    const RCODE_NOT_IMPLEMENTED = 4;
    const RCODE_REFUSED = 5;

    
    const OPT_TCP_KEEPALIVE = 11;

    
    const OPT_PADDING = 12;

    
    public static function createRequestForQuery(Query $query)
    {
        $request = new Message();
        $request->id = self::generateId();
        $request->rd = true;
        $request->questions[] = $query;

        return $request;
    }

    
    public static function createResponseWithAnswersForQuery(Query $query, array $answers)
    {
        $response = new Message();
        $response->id = self::generateId();
        $response->qr = true;
        $response->rd = true;

        $response->questions[] = $query;

        foreach ($answers as $record) {
            $response->answers[] = $record;
        }

        return $response;
    }

    
    private static function generateId()
    {
        if (function_exists('random_int')) {
            return random_int(0, 0xffff);
        }
        return mt_rand(0, 0xffff);
    }

    
    public $id = 0;

    
    public $qr = false;

    
    public $opcode = self::OPCODE_QUERY;

    
    public $aa = false;

    
    public $tc = false;

    
    public $rd = false;

    
    public $ra = false;

    
    public $rcode = Message::RCODE_OK;

    
    public $questions = array();

    
    public $answers = array();

    
    public $authority = array();

    
    public $additional = array();
}
