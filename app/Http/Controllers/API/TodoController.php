<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;
use App\Utilities\AppUtility;
use App\Utilities\Utility;
use Carbon\Carbon;
use App\Base\TodoBase;
use App\Models\TodoPoint;

class TodoController extends Controller
{
    public $appUtility;
    public $utility;
    public $base;
    
    function __construct() {
        
        $this->appUtility = new AppUtility();
        $this->base = new TodoBase();
    }

    public function get_items_by_timer_constraint(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];
            $result = [];
            $user = auth()->guard('api-user')->user();

            
            $builder = $this->appUtility->raw_builder($data);
            if ($builder['status'] < Utility::$neutral) {
                return \prepare_json($builder['status'], [], $builder['message']);
            }
            
            $records = $builder['data'];
            $records = Todo::where('user_id',$user->id)->where('status', '!=' ,Utility::$positive)->orderBy('id', 'desc')->get();
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
                    $single_item->due_date = Carbon::now()->addMonths(6)->toDateTimeString();
                }
                $temp['item'] = $single_item;
                //$time_value = $this->calc_due_days($single_item->due_date);
                
                $time_value = Utility::calc_due_days($single_item->due_date);
                $time_value = $time_value == 0 ? 1: $time_value;
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

    public function get_point_from_all_done(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];
            $result = [];
            $user = auth()->guard('api-user')->user();

            // category pending 
            $todo = Todo::where('user_id', $user->id)->where('status', Utility::$positive)->get();
            
            foreach($todo as $item) {
                $category = Todo::where('user_id', $user->id)->where('category', $item->category)->where('status', Utility::$positive)->get();
                //dd($this->base->gather_point(2));
                //dd($item->category);
                $_point = $this->base->gather_point(count($category));
                //dd($_point);
                //$_point = TodoBase::gather_point($todo, count($category));
                
                //save point
                $todo_pointer = TodoPoint::create([
                    'amount' => $_point,
                    'user_id' => $user->id,
                    'type' => 'C',
                ]);
            }

            
            
            $result = $todo;
            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result, 'todo_pointer' => $todo_pointer], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }


    public function mark_as_done(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];
            $result = [];
            $user = auth()->guard('api-user')->user();

            
            if (!array_key_exists('id', $data)) {
                return \prepare_json(Utility::$negative, ['key' => $key, $key => $result], \get_api_string('invalid_action', 'Id is required'));
            }
            
            $todo = Todo::where('id',$data['id'])->where('user_id',$user->id)->first();
            if ($todo == null) {
                return \prepare_json(Utility::$negative, ['key' => $key, $key => $result], \get_api_string('invalid_action'));
            }
            
            // category pending 
            $category = Todo::where('category', $todo->category)->where('status', Utility::$neutral)->get();

            //$todo->deleted_at = Carbon::now();
            $todo->status = Utility::$positive;
            $todo->save();

            $_point = $this->base->gather_point(count($category));
            if ($_point['status'] == Utitlity::$positive) {
                //save point
                $todo_pointer = TodoPoint::create([
                    'amount' => $_point,
                    'user_id' => $user->id,
                    'type' => 'C',
                ]);
            }
            else {
                // error happened with point
            }
            
            
            $result = $todo;
            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result, 'todo_pointer' => $todo_pointer], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function delete_todo(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];
            $result = [];
            $user = auth()->guard('api-user')->user();

            
            $builder = $this->appUtility->raw_builder($data);
            if ($builder['status'] < Utility::$neutral) {
                return \prepare_json($builder['status'], [], $builder['message']);
            }
            if (!array_key_exists('id', $data)) {
                return \prepare_json(Utility::$negative, ['key' => $key, $key => $result], \get_api_string('invalid_action', 'Id is required'));
            }
            
            $records = $builder['data'];
            $todo = Todo::where('id',$data['id'])->where('user_id',$user->id)->first();
            if ($todo == null) {
                return \prepare_json(Utility::$negative, ['key' => $key, $key => $result], \get_api_string('no_record', 'TOdo'));
            }
            
            $todo->deleted_at = Carbon::now();
            $todo->status = Utility::$negative;
            $todo->save();
            
            $result = $todo;
            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function get_closed_items(Request $request) {
        try {
            $data = $request->all();
            
            $key = $data['key'];
            $result = [];
            $user = auth()->guard('api-user')->user();
            
            // get all items
            // sort the items by date deleted
            //categorize them by date
            // get total counts
            
            $builder = $this->appUtility->raw_builder($data);
            if ($builder['status'] < Utility::$neutral) {
                return \prepare_json($builder['status'], [], $builder['message']);
            }
            
            $records = $builder['data'];
            $records = Todo::where('user_id',$user->id)->select('id', 'status', 'updated_at','created_at', 'user_id')->where('status', Utility::$positive)->withTrashed()->get()->groupBy(function($date) {
                return Carbon::parse($date->updated_at)->format('Y.m.d'); // grouping by years
                //return Carbon::parse($date->created_at)->format('m'); // grouping by months
            });

            
            // get individual total    
            foreach ($records as $item) {
                $temp = [
                    'date' => Carbon::parse($item[0]->updated_at)->format('Y-m-d'),
                    'count' => count($item),
                ];
                array_push($result, $temp) ;
                
            }

            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function get_done_pending_ratio(Request $request) {
        try {
            $data = $request->all();
            
            $key = $data['key'];
            $result = [];
            $user = auth()->guard('api-user')->user();
            
            // get all items
            // sort the items by date deleted
            //categorize them by date
            // get total counts
            
            $builder = $this->appUtility->raw_builder($data);
            if ($builder['status'] < Utility::$neutral) {
                return \prepare_json($builder['status'], [], $builder['message']);
            }
            
            $records = Todo::where('user_id', $user->id)->where('status', Utility::$positive)->withTrashed()->get();
            $pending_records = Todo::where('user_id', $user->id)->where('status', Utility::$neutral)->withTrashed()->get();
            $deleted_records = Todo::where('user_id', $user->id)->where('status',Utility::$negative)->withTrashed()->get();
            
            $result  = [
                'pending' => count($pending_records),
                'done' => count($records),
                'deleted_records' => count($deleted_records),
            ];

            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    
}
