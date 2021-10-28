<?php

/**
 * This is my custom helper function
 * Laravel keeps resetting it's global helper file
 * So I opened a new file of my own, damn you Laravel!
 */

use App\Utilities\Accent;
use App\Utilities\DateManipulator;
use App\Utilities\RandomGenerator;
use App\Utilities\Shopping;
use App\Utilities\StringModifier;
use App\Utilities\Utility;

if (! function_exists('generate_random_string')) {
    /**
     * @param $length
     * @return string
     */
    function generate_random_string($length)
    {
        return RandomGenerator::generate_random_string($length);
    }
}

if (! function_exists('get_rating')) {
    /**
     * @param $rating
     * @return string
     */
    function get_rating($rating)
    {
        return Utility::get_rating($rating);
    }
}

if (! function_exists('get_average_rating')) {
    /**
     * @param $rating
     * @return float|int
     */
    function get_average_rating($rating)
    {
        return Utility::get_average_rating($rating);
    }
}

if (! function_exists('discount')) {
    /**
     * @param $price
     * @param $discount
     * @return float|int|string
     */
    function discount($price, $discount)
    {
        return Shopping::discount($price, $discount);
    }
}

if (! function_exists('generate_unique_uuid')) {
    /**
     * @return mixed
     */
    function generate_unique_uuid()
    {
        return RandomGenerator::generate_unique_uuid();
    }
}

if (! function_exists('generate_random_numbers')) {
    /**
     * @param $length
     * @return string
     */
    function generate_random_numbers($length)
    {
        return RandomGenerator::generate_random_numbers($length);
    }
}

if (! function_exists('generate_personal_code')) {
    /**
     * @param $model
     * @return string
     */
    function generate_personal_code($model)
    {
        return RandomGenerator::generate_personal_code($model);
    }
}

if (! function_exists('time_ago')) {
    /**
     * @param $date
     * @return string
     */
    function time_ago($date)
    {
        return DateManipulator::time_ago($date);
    }
}

if (! function_exists('date_percent_diff')) {
    /**
     * @param $date1
     * @param $date2
     * @return float|int
     */
    function date_percent_diff($date1, $date2)
    {
        return DateManipulator::date_percent_diff($date1, $date2);
    }
}

if (! function_exists('get_working_days')) {
    /**
     * @param $date1
     * @param $date2
     * @return int
     */
    function get_working_days($date1, $date2)
    {
        return DateManipulator::getWorkingDays($date1, $date2);
    }
}

if (! function_exists('list_working_days')) {
    /**
     * @param $date
     * @return array
     */
    function list_working_days($date)
    {
        return DateManipulator::listWorkingDays($date);
    }
}

if (! function_exists('get_working_days_from_range')) {
    /**
     * @param $start
     * @param $end
     * @return mixed
     */
    function get_working_days_from_range($start, $end)
    {
        return DateManipulator::getWorkingDaysFromRange($start, $end);
    }
}

if (! function_exists('get_date_from_range')) {
    /**
     * @param $date1
     * @param $date2
     * @return array
     */
    function get_date_from_range($date1, $date2)
    {
        return DateManipulator::getDatesFromRange($date1, $date2);
    }
}

if (! function_exists('time_left')) {
    /**
     * @param $date
     * @return mixed
     */
    function time_left($date)
    {
        return DateManipulator::time_remaining($date);
    }
}

if (! function_exists('age')) {
    /**
     * @param $date
     * @return int
     */
    function age($date)
    {
        return DateManipulator::age($date);
    }
}

if (! function_exists('get_age')) {
    /**
     * @param $date
     * @return false|int|string
     */
    function get_age($date)
    {
        return DateManipulator::get_age($date);
    }
}

if (! function_exists('remove_accent')) {
    /**
     * @param $string
     * @return mixed
     */
    function remove_accent($string)
    {
        return Accent::remove_accent($string);
    }
}

if (! function_exists('ellipsis')) {
    /**
     * @param $string
     * @param $length
     * @return string
     */
    function ellipsis($string, $length)
    {
        return StringModifier::ellipsis($string, $length);
    }
}

if (! function_exists('abbreviate')) {
    /**
     * @param $string
     * @return string
     */
    function abbreviate($string)
    {
        return StringModifier::abbreviate($string);
    }
}

if (! function_exists('get_guard')) {
    /**
     * @return mixed
     */
    function get_guard()
    {
        $guard = auth()->guard();

        $sessionName = $guard->getName();

        $parts = explode('_', $sessionName);

        unset($parts[count($parts) -1]);

        unset($parts[0]);

        $guardName = implode('_', $parts);

        return $guardName;
    }
}

if (! function_exists('get_gender')) {
    /**
     * @param $string
     * @return string
     */
    function get_gender($string)
    {
        return $string == 'Male' ? 'his' : 'her';
    }
}

if (! function_exists('slug_reverse')) {
    /**
     * @param $string
     * @return string
     */
    function slug_reverse($string)
    {
        return title_case(str_replace('-', ' ', $string));
    }
}

if (! function_exists('me')) {
    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    function me()
    {
        return auth()->user();
    }
}

if(! function_exists('notify')) {
    /**
     * @param $title
     * @param $state('primary', 'success', 'info', 'danger', 'warning', 'danger', 'secondary', 'default')
     * @param $content
     * @param $from $args['top', 'bottom']
     * @param $align $args['left', 'right', 'center'
     * @param $icon
     * @param $delay
     */
    function notify($title, $state, $content, $from, $align, $icon, $delay = null)
    {
        session()->flash('message', [
            'title' => $title,
            'content' => $content,
            'state' => $state,
            'from' => $from,
            'align' => $align,
            'icon' => $icon,
            'delay' => $delay
        ]);
    }
}

if(! function_exists('get_full_name')) {
    /**
     * @param $person
     * @return string
     */
    function get_full_name($person)
    {
        return $person->first_name .' '. $person->last_name;
    }
}

// API
if(! function_exists('prepare_json')) {
    /**
     * @param $code
     * @param $data
     * @param string $msg
     * @param int $status_code
     * @return \Illuminate\Http\JsonResponse
     */
    function prepare_json($code, $data, $msg='', $status_code = Symfony\Component\HttpFoundation\Response::HTTP_OK) {
        // $response = new Symfony\Component\HttpFoundation\Response;
//        return response()->json(['status' => $status, 'data' => $data, 'message' => $msg], $status_code);
        if (gettype($data) === "object") {
            $arr = get_object_vars($data);
            $data_keys = array_keys($arr);
        }
        else {
            $data_keys = array_keys($data);
        }

        if ($code == Utility::$negative && $status_code == 200) {
            $status_code = Utility::$_400;
        }
        else if ($code == Utility::$error && $status_code != Utility::$_500) {
            $status_code = Utility::$_500;
        }

        if (gettype($code) === "boolean") {
            if ($code === false) {
                if ($status_code === Utility::$_500) {
                    $code = Utility::$error;
                }
                else {
                    $code = Utility::$negative;
                }
            }
            else {
    //            dd((gettype($data[$data_keys[0]])));
                if (count ($data_keys) > 0 && gettype($data[$data_keys[0]]) == "array") {
                    $code = Utility::$neutral;
                }
                else {
                    $code = Utility::$positive;
                }

            }
        }


        if ($code < 0) {
            $status = false;
        }
        else {
            $status = true;
        }


        return response()->json(['code' => $code, 'status' => $status, 'data' => $data, 'message' => $msg, ], $status_code);
    }
}

if(! function_exists('validator_result')) {
    /**
     * @param $status
     * @param $messages
     * @return array
     */
    function validator_result($status, $messages=[]) {
        // $response = new Symfony\Component\HttpFoundation\Response;
        return ['failed'=>$status, 'messages'=> $messages];
    }
}

if(! function_exists('get_api_string')) {
    /**
     * @param $title
     * @return string
     */
    function get_api_string($title, $plug_in_var='') {
        // $response = new Symfony\Component\HttpFoundation\Response;
        return \App\Utilities\AppStrings::get_app_string($title, $plug_in_var);
    }
}

if(! function_exists('date_compare')) {
    /**
     * @param $a
     * @param $b
     * @return false|int
     */
    function date_compare($a, $b)
    {
        $t1 = strtotime($a['created_at']);
        $t2 = strtotime($b['created_at']);
        return $t1 - $t2;
    }
}
