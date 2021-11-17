<?php

namespace App\Base;

use App\Models\Todo;
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
            return \prepare_json($builder['status'], [], $builder['message']);
        }
    }
}
?>