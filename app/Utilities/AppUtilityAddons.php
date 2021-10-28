<?php
/**
 * Created by PhpStorm.
 * User: Black
 * Date: 3/20/2021
 * Time: 6:11 PM
 */

namespace App\Utilities;


use App\Models\Discount;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class AppUtilityAddons
{
    /** file can be cleaned out per platform **/

    /**
     * System Code goes here
     */
    
     /**
     * @param $response
     */
    public static function init(&$response) {
        self::save_record($response);
        self::save_todo($response);
        self::save_fund_tracker($response);
    }

    /**
     * @param $response
     * @param $key
     */
    public static function finalize(&$response, $key) {
        $response['data']['key'] = $key;
    }

    public static function treat_exception(&$response, $ex) {
        
        if (strpos(strtolower($ex->getMessage()), 'Integrity constraint violation: 1062 Duplicate entry') !== false) {
            if (strpos(strtolower($ex->getMessage()), 'users_email_unique')) {
                $response['status'] = Utility::$error;
                $response['message'] = \get_api_string('error_occurred', 'Email has already been used');

            }
            else {
                $response['status'] = Utility::$error;
                $response['message'] = \get_api_string('error_occurred', 'Supplied information exists on the system.');
            }
        }
        else if (strpos(strtolower($ex->getMessage()), strtolower('SQLSTATE[HY000]: General error: 1364 Field')) !== false) {
            $response['status'] = Utility::$error;
                $response['message'] = \get_api_string('error_occurred', 'Please fill all fields');
        }
        //
        else if (strpos(strtolower($ex->getMessage()), strtolower('SQLSTATE[42S22]: Column not found:')) !== false) {
            dd($ex->getMessage());
            $response['status'] = Utility::$error;
                //$response['message'] = \get_api_string('error_occurred', 'Field missing in core storage');
                $response['message'] = 'Field missing in core storage';
        }
        else {
            $response['status'] = Utility::$error;
            $response['message'] = \get_api_string('error_occurred', 'Please try again later'.$ex->getMessage());
        }

        return $response;
    }

    /**
     * System Code goes ends here
     */

    /**
     * Custom Code goes here downwards
     */
    
     public static  function save_record(&$response) {
        if ($response['data']['key'] != AppUtility::$instance_keys['record']) {
            return $response;
        }

        if (!array_key_exists('item', $response['data'])) {
            return $response;
        }

        if ($response['status'] < Utility::$neutral) {
            // error occurred in one of senior functions
            return $response;
        }

        if (!array_key_exists('price', $response['data'])) {
            $response['status'] = Utility::$negative;
            $response['message'] = \get_api_string('invalid_action', 'Price not supplied');
            return $response;
        }

        try {
            $user = auth()->guard('api-user')->user();
            $response['data']['user_id'] = $user->id;
        }
        catch (\Exception $ex) {
            self::treat_exception($response, $ex);
            // $response['status'] = Utility::$error;
            // $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public static  function save_todo(&$response) {
        if ($response['data']['key'] != AppUtility::$instance_keys['todo']) {
            return $response;
        }

        if (!array_key_exists('category', $response['data'])) {
            $response['data']['category'] = "uncategorized";
        }

        if (!array_key_exists('status', $response['data'])) {
            $response['data']['status'] = Utility::$neutral;
        }

        if ($response['status'] < Utility::$neutral) {
            // error occurred in one of senior functions
            return $response;
        }

        if (!array_key_exists('title', $response['data'])) {
            $response['status'] = Utility::$negative;
            $response['message'] = \get_api_string('invalid_action', 'Title not supplied');
            return $response;
        }

        try {
            $user = auth()->guard('api-user')->user();
            $response['data']['user_id'] = $user->id;
        }
        catch (\Exception $ex) {
            self::treat_exception($response, $ex);
        }

        return $response;
    }

    public static  function save_fund_tracker(&$response) {
        if ($response['data']['key'] != AppUtility::$instance_keys['fund_tracker']) {
            return $response;
        }

        if ($response['status'] < Utility::$neutral) {
            // error occurred in one of senior functions
            return $response;
        }

        if (!array_key_exists('status', $response['data'])) {
            $response['data']['status'] = Utility::$neutral;
        }

        if (!array_key_exists('title', $response['data'])) {
            $response['status'] = Utility::$negative;
            $response['message'] = \get_api_string('invalid_action', 'Title not supplied');
            return $response;
        }

        if (!array_key_exists('type', $response['data'])) {
            $response['status'] = Utility::$negative;
            $response['message'] = \get_api_string('invalid_action', 'Title not supplied');
            return $response;
        }
        else {
            if (strtolower($response['data']['type']) != "c" && strtolower($response['data']['type']) != "d") {
                $response['status'] = Utility::$negative;
                $response['message'] = \get_api_string('invalid_action', 'Transaction Type is not valid');
                return $response;
            }
            else {
                $response['data']['type'] = strtolower($response['data']['type']);
            }
        }

        try {
            $user = auth()->guard('api-user')->user();
            $response['data']['user_id'] = $user->id;
            $response['data']['date_created'] = Carbon::today()->toDateString();
        }
        catch (\Exception $ex) {
            self::treat_exception($response, $ex);
        }

        return $response;
    }
}