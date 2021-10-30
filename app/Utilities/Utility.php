<?php

namespace App\Utilities;
use App\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use Carbon\Carbon;

class Utility
{
    /** display positive message to user compulsory **/
    public static $positive = 1;
    /** display message to user not compulsory **/
    public static $neutral = 0;
    /** display negative message to user compulsory **/
    public static $negative = -1;
    /** display error message to user compulsory **/
    public static $error = -2;

    public static $_500 = 500;
    public static $_422 = 422;
    public static $_401 = 401;
    public static $_403 = 403;
    public static $_201 = 201;
    public static $_400 = 400;

    public static function validator(array $data, $fields)
    {
        $validator =  Validator::make($data, $fields);
        if ($validator->fails()) {
            return \validator_result(true, $validator->errors()->all());
        }

        return \validator_result(false);
    }

    public static function send_mail($to,$from, $subject,$title,$body) {
        try {
            $details = [
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                'title' => $title,
                "body"  => $body
            ];


            Mail::raw($body, function ($message) use($to, $from, $subject, $title) {
                $message->to($to)->from($from)
                    ->subject($subject);

                });

            if (Mail::failures()) {
                return [
                    'code'  => self::$negative,
                    'data'    => $details,
                    'message' => "Couldn't sending mail.. retry again"
                ];

            }
            return [
                'status'  => self::$positive,
                'data'    => $details,
                'message' => 'Mail sent'
            ];
        }
        catch (\Exception $ex) {
            return [
                'status'  => self::$error,
                'message' => $ex->getMessage()
            ];
        }
    }



    public static function send_token_mail($to,$from, $subject, $body, $token, $first_name) {
        try {
            $details = [
                'to' => $to,
                'from' => $from,
                'subject' => $subject,
                "body"  => $body
            ];


            Mail::send('emails.token', ['token' => $token, 'first_name' => $first_name], function ($message) use($to, $from, $subject) {
                $message->from($from);
                $message->subject($subject);
                $message->to($to);
            });


            if (Mail::failures()) {
                return [
                    'code'  => self::$negative,
                    'data'    => $details,
                    'message' => "Couldn't sending mail.. retry again"
                ];

            }
            return [
                'status'  => self::$positive,
                'data'    => $details,
                'message' => 'Mail sent'
            ];
        }
        catch (\Exception $ex) {
            return [
                'status'  => self::$error,
                'message' => $ex->getMessage()
            ];
        }
    }

    public static function calc_due_days($_date) {
        try {
            $date = Carbon::parse($_date);
            $now = Carbon::now();

            // return $date->diffInMinutes($now);
            //dd($date, $now);
            //dd($now->diffInDays($date));
            $diff = $date->diffInDays($now);
            return $diff <= 0 ? 1 : $diff;
        }
        catch(\Exception $ex) {
            
        }
    }


    /**
     * @param $rating
     * @return string
     */
    public static function get_rating($rating)
    {
        $stars = "";

        if($rating == 0){
            for($s = 1; $s <= 5; $s++){
                $stars .= '<span class="fa fa-star col-gray">&nbsp;</span>';
            }
        }

        $whole = floor($rating);
        $fraction = $rating - $whole;

        if($fraction < .25){
            $dec=0;
        }elseif($fraction >= .25 && $fraction < .75){
            $dec=.50;
        }elseif($fraction >= .75){
            $dec=1;
        }
        $r = $whole + $dec;

        //As we sometimes round up, we split again
        $newwhole = floor($r);
        $fraction = $r - $newwhole;

        for($s=1;$s<=$newwhole;$s++){
            $stars .= '<span class="fa fa-star col-orange">&nbsp;</span>';
        }
        if($fraction==.5){
            $stars .= '<span class="fa fa-star-half col-orange">&nbsp;</span>';
        }

        return $stars;
    }

    /**
     * @param $rating
     * @return float|int
     */
    public static function get_average_rating($rating)
    {
        if($rating->count() == 0){
            return 0;
        }

        $max = 0;

        foreach($rating as $rate => $count) { // iterate through array
            $max = $max + $count->rating;
        }

        return $max / $rating->count();
    }

    public static function run_request($url, $data=null, $method="POST",$headers=null, $base_uri="") {
        try {
            $client_init = [
                "base_uri" => $base_uri,
            ];

            if ($headers !== null) {
                $client_init['headers'] = $headers;
            }

            if ($base_uri !== "") {
                $client = new Client($client_init);
            }
            else {
                $client = new Client();
            }


            if ($data !== null) {
                $body = $data;
                $body = json_encode($body);
            }


            if ($data != null) {
                $response = $client->request($method, $url, ['body' => $body]);
                dd(json_decode($response->getBody()));
            }
            else {
                $response = $client->request($method, $url);
            }

            $response = json_decode($response->getBody());

            return \prepare_json(Utility::$positive, $response, \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

}
