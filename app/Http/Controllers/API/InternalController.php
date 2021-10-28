<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FundTracker;
use App\Utilities\AppUtility;
use App\Utilities\Utility;

class InternalController extends Controller
{
    public $appUtility;
    
    function __construct() {
        $this->appUtility = new AppUtility();
    }

    public function get_fund_tracker_by_date(Request $request) {
        try {

            
            $data = $request->all();
            $key = $data['key'];
            $result = [];

            
            $builder = $this->appUtility->raw_builder($data);
            
            if ($builder['status'] < Utility::$neutral) {
                return \prepare_json($builder['status'], [], $builder['message']);
            }
            
            $records = $builder['data']->orderBy('date_created', 'desc')->groupBy($data['group'])->get();
            // get individual total    
            foreach ($records as $item) {
                $temp = [];
                $single_item = FundTracker::where('date_created', $item->date_created);
                $sum = $single_item->sum('amount');
                $count = $single_item->count('id');
                $temp = [
                    'date_created' => $item->date_created,
                    'amount' => $sum,
                    'count' => $count,
                    'created_at' => $item->created_at,
                ];
                array_push($result, $temp) ;
                
            }

            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }
}
