<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use App\Utilities\AppUtility;
use App\Utilities\Utility;
use Carbon\Carbon;

class TodoController extends Controller
{
    public $appUtility;
    
    function __construct() {
        
        $this->appUtility = new AppUtility();
    }

    public function get_items_by_timer_constraint(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];
            $result = [];

            
            $builder = $this->appUtility->raw_builder($data);
            if ($builder['status'] < Utility::$neutral) {
                return \prepare_json($builder['status'], [], $builder['message']);
            }
            
            $records = $builder['data'];
            $records = Todo::where('status', '!=' ,Utility::$positive)->orderBy('id', 'desc')->get();
            // get individual total    
            foreach ($records as $item) {
                $temp = [];
                //$single_item = FundTracker::where('status', '!=' ,Utility::$positive);
                $single_item = $item;
                
                if ($single_item == null) {
                    return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
                }
                $temp = [
                    'date_created' => $item->created_at,
                    'created_at' => $item->created_at,
                ];
                if ($single_item->due_date == null || $single_item->due_date == "") {
                    $single_item->due_date = Carbon::now()->addMonths(6)->date;
                }
                $temp['item'] = $single_item;
                $time_value = $this->calc_due_days($single_item->due_date);
                $time_const = 100/($time_value == 0 ? 1: $time_value);
                $weight = $time_const +  $single_item->constraint_personal + $single_item->constraint_value + $single_item->constraint_urgency + $single_item->constraint_importance;
                
                
                $temp['due_date_weight'] = $weight;
                
                array_push($result, $temp) ;
            }

            $due_date_weight = array_column($result, 'due_date_weight');
            array_multisort($due_date_weight, SORT_DESC, $result);

            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    private function calc_due_days($_date) {
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
}
