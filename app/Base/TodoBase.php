<?php

namespace App\Base;

use App\Models\Todo;
use App\Models\TodoPoint;
use App\Models\User;
use App\Utilities\AppUtility;
use App\Utilities\Utility;
use Carbon\Carbon;


class TodoBase {
    public static function arg() {
        return "";
    }
    public function gather_point($pending_category_count){
        try {
            //constants/vars
            $point_def_value = 10; 
            $cat_dev_value = 0.01;
            
            
            $category_pts = ($pending_category_count * $point_def_value * $cat_dev_value) * 10;
            
            $point = $point_def_value + $category_pts;
            
            return ["status" => Utility::$positive, "data" => $point, "message" => ""];
        }
        catch(\Exception $ex) {
            return ["status" => Utility::$error, [], "message" => $ex->getMessage()];
        }
    }

    public function get_points_for_user($user_id){
        try {
            //constants/vars
            //->sum() ?? 0
            $response = AppUtility::prepare_initialization_data([]);
            $c_points = TodoPoint::where('user_id', $user_id)->where('type', 'C')->sum('amount') ?? 0; 
            $d_points = TodoPoint::where('user_id', $user_id)->where('type', 'D')->sum('amount') ?? 0; 
            if (!$c_points) {
                return ["status" => Utility::$negative, "data" => $diff, "message" => "no points for user."];
            }
            
            $diff = (intval($c_points)) - intval($d_points);
            if ($diff < 0) {
                //make positve
                $diff = (-1) * $diff;
            }
            //after getting difference increase by X %
            $_x_percentage = 0.05;
            $diff = intval($diff * $_x_percentage);
            $response['status'] = Utility::$positive; 
            $response['message'] = ""; 
            $response['data'] = $diff;             
            //return ["status" => Utility::$positive, "data" => $diff, "message" => ""];
        }
        catch(\Exception $ex) {
            $response['data'] = []; 
            $response['message'] = $ex->getMessage(); 
            $response['status'] = Utility::$error; 
            //return ["status" => Utility::$error, [], "message" => $ex->getMessage()];

        }
        return $response;
    }

    public function compound_points() {
        try {
            $response = AppUtility::prepare_initialization_data([]);
            // get all users
            $nowDay = Carbon::now()->format('d');
            //->whereDay('created_at', $nowDay)
            $users = User::where('user_type', \get_api_string('user_type_user'))->get();
            $succesful_counter = 0;
            $failed_counter = 0;
            
            if (count($users) > 0) {
                foreach ($users as $value) {
                    try {
                        $user_point_increase = $this->get_points_for_user($value->id);
                        if ($user_point_increase['status'] > Utility::$negative) {
                            $todo_pointer = TodoPoint::create([
                                'amount' => $user_point_increase['data'],
                                'user_id' => $value->id,
                                'type' => 'C',
                            ]);
                            if (!$todo_pointer) {
                                // failed to create point for user
                                $failed_counter = $failed_counter + 1;
                                
                            }
                            else {
                                $succesful_counter = $succesful_counter + 1;
                            }
                        }
                        else {
                            $failed_counter = $failed_counter + 1;
                        }
                        
                        
                    }
                    catch(\Exception $e) {
                        $failed_counter = $failed_counter + 1;
                        $response['message'] = $e->getMessage();
                    }
                }
                $response['status'] = Utility::$positive;
                $response['message']  = $succesful_counter . " Succesful, Failed: ".$failed_counter;
                //return ["status" => Utility::$positive, [], "message" => $succesful_counter . " Succesful, Failed: ".$failed_counter];
                return $response;
            }
            $response['status'] = Utility::$positive;
            $response['message']  = "No Users created on this day";
            return $response;
            //return ["status" => Utility::$negative, [], "message" => "No Users created on this day"];
            //return ["status" => Utility::$negative, [], "message" => $nowDay];

            
            
        }
        catch(\Exception $ex) {
            //return \prepare_json(Utility::$error, [], \get_api_string('error_occured'));
            return ["status" => Utility::$error, [], "message" => $ex->getMessage()];
        }
    }
}
?>