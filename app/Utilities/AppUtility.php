<?php

namespace App\Utilities;

use App\Models\User;
use App\Models\Record;
use App\Models\UserToken;
use App\Models\Todo;
use App\Models\FundTracker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class AppUtility extends Controller
{
    private static  $instance = [];
    private static $email_verification = false;
    private static $user_unique_variable = 'phone';

    public static $instance_keys = [
        'user' => 'user',
        'record' => 'record',
        'todo' => 'todo',
        'fund_tracker' => 'fund_tracker',

    ];
    private static  $password_encryption_type = "bcrypt";

    public function __construct()
    {
        self::$instance = array_merge(self::$instance, [
            self::$instance_keys['user'] => ['instance' => new User(), 'db_name' => 'users'],
            self::$instance_keys['record'] => ['instance' => new Record(), 'db_name' => 'records'],
            self::$instance_keys['todo'] => ['instance' => new Todo(), 'db_name' => 'todos'],
            self::$instance_keys['fund_tracker'] => ['instance' => new FundTracker(), 'db_name' => 'fund_trackers'],
        ]);
    }

    private static function prepare_initialization_data($data) {
        $response = ['status' => Utility::$neutral, 'data' => $data, 'message'=> '', 'extra_data' => []];

        return $response;
    }

    public function create_user_raw($data) {
        
        try {

        
            $model = $data['key'];
            unset($data['key']);


            if (self::$password_encryption_type == "bcrypt") {
                $data['password'] = bcrypt($data['password']);
            }

            $response = self::prepare_initialization_data($data);
            

            // check if user is not used

            if (User::where(self::$user_unique_variable, $response['data'][self::$user_unique_variable])->first() !== null) {
                $response['status'] = Utility::$negative;
                $response['message'] = \get_api_string('record_exist', self::$user_unique_variable);

                return $response;
            }

            /***
            custom changes needed for this system
            **/
            // AppUtilityAddons::generate_referral_code($response);
            /***
            custom changes needed for this system
            **/

        
            $response = self::prepare_initialization_data($data);

            if (($response['status'] >= Utility::$neutral)) {
                //dd($response['data']);
                self::$instance[$model]['instance']->fill($response['data']);
                self::$instance[$model]['instance']->save();
                $response['data'] = self::$instance[$model]['instance']->id;

                if (self::$email_verification) {
                    $token = UserToken::create([
                        'user' => $data['email'],
                        'token' => generate_random_numbers(6),
                    ]);

                    $resend_counter = 0;

                    for(;;) {
                        $mail_status = Utility::send_token_mail($data['email'], "odenigbo67@gmail.com   ", \get_api_string('enter_reset_code', "Token Generated"), "Title", $token->token, $data['firstname']);

                        if ($resend_counter == 5) {
                            $response['status'] = Utility::$negative;
                            $response['message'] = \get_api_string('error_occurred', 'Token could not be sent to your email. '.$mail_status['message']);

                            return $response;
                        }

                        if ($mail_status['status'] >= Utility::$neutral) {
                            break;
                        }
                        //try to resend the mail
                        $resend_counter += 1;
                    }
                }
            }
            else {
                unset ($response['data']);
            }
        }
        catch(\Exception $ex) {
            AppUtilityAddons::treat_exception($response, $ex);
        }
        return $response;

    }

    public function generate_token(Request $request) {
        $validator = Utility::validator($request->all(),[
            'email' => 'required|string',
        ]);

        if ($validator['failed']) {
            return \prepare_json(Utility::$negative, ['messages' => $validator['messages']],'',$status_code=Utility::$_422);
        }

        $data = $request->all();

        try {
            $user = User::where('email', $data['email'])->first();

            if(!$user) {
                return \prepare_json(Utility::$negative, [],\get_api_string('not_found', 'User'));
            }


            $password_reset = UserToken::create([
                'user' => $user->email,
                'token' => generate_random_numbers(6),
            ]);

            $resend_counter = 0;
            if (!self::$email_verification) {
                if (array_key_exists('resend',$data)) {
                    return \prepare_json(Utility::$positive, ['user' => $user],\get_api_string('code_resent'));
                }
                return \prepare_json(Utility::$positive, ['user' => $user],\get_api_string('enter_reset_code'));
            }

            if ($user && $password_reset) {
                for(;;) {
                    $mail_status = Utility::send_mail($user->email, "odenigbo67@gmail.com   ", \get_api_string('enter_reset_code', "Token Generated"), "Title", "Please use this Token ".$password_reset->token);

                    if ($resend_counter == 5) {
                        return \prepare_json(Utility::$negative, [],\get_api_string('error_occurred', 'Token could not be sent to your email. '.$mail_status['message']));
                    }

                    if ($mail_status['status'] >= Utility::$neutral) {
                        break;
                    }

                    //try to resend the mail
                    $resend_counter += 1;
                }

                if (array_key_exists('resend',$data)) {
                    return \prepare_json(Utility::$positive, ['user' => $user],\get_api_string('code_resent'));
                }


                return \prepare_json(Utility::$positive, ['user' => $user],\get_api_string('enter_reset_code'));
            }
            return \prepare_json(Utility::$negative, [],\get_api_string('error_occurred'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function validate_token(Request $request) {
        $validator = Utility::validator($request->all(),[
            'token' => 'required|numeric',
        ]);

        if ($validator['failed']) {
            return \prepare_json(Utility::$negative, ['messages' => $validator['messages']],'',$status_code=Utility::$_422);
        }
        try {
            $data = $request->all();


            $password_reset = UserToken::where('token', $data['token'])->where('status', Utility::$neutral)->latest("id")->first();

            if (is_null($password_reset)) {
                return \prepare_json(Utility::$negative, [],\get_api_string('token_invalid'));
            }

            if (Carbon::parse($password_reset->created_at)->addMinutes(10)->isPast()) {
                $password_reset->status = Utility::$negative;
                $password_reset->save();
                return \prepare_json(Utility::$negative, [],\get_api_string('invalid_action', 'Token expired'));
            }

            $password_reset->status = Utility::$positive;
            $password_reset->save();

            return \prepare_json(Utility::$positive, ['token' => $password_reset],\get_api_string('token_valid'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred'), Utility::$_500);
        }
    }

    public function reset_password(Request $request) {
        $validator = Utility::validator($request->all(),[
            'email' => 'string',
            'new_password' => 'required|string',
            'confirm_password' => 'required|string',
        ]);

        if ($validator['failed']) {
            return \prepare_json(Utility::$negative, ['messages' => $validator['messages']],'',$status_code=Utility::$_422);
        }
        try {
            $data = $request->all();


            if ($data['new_password'] != $data['confirm_password']) {
                return \prepare_json(Utility::$negative, [],\get_api_string('passwords_dont_match'));
            }

            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                return prepare_json(Utility::$negative, [], get_api_string('not_found', 'User'));
            }

            $password = bcrypt($data['new_password']);

            $user->password = $password;

            $user->save();


            //reset password token
            $password_reset = UserToken::where(['user' => $user->email])->first();
            $password_reset->token = "";
            $password_reset->save();

            return \prepare_json(Utility::$positive, [],\get_api_string('password_changed'));
        }
        catch (ModelNotFoundException $ex) {
            return \prepare_json(Utility::$negative, [], \get_api_string('not_found', 'User'), Utility::$_500);
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function login_raw($data) {
        try {
            $model = $data['key'];

            $response = self::prepare_initialization_data($data);

            /***
            custom changes needed for this system
             **/
            /***
            custom changes needed for this system
             **/
            unset($data['key']);

            
            if (array_key_exists('user_type', $response['data'])) {
                

                $user = User::where(self::$user_unique_variable, $response['data'][self::$user_unique_variable])->where('user_type', $response['data']['user_type'])->first();

            }
            else {


                $user = User::where(self::$user_unique_variable, $response['data'][self::$user_unique_variable])->first();
                // dd($user);
                //done passport install?! I no get strength to yab.. i lost 240k.. so i will let it go
                // send me this song
                // push to repo and merge with main or master.. your choice
                // i already made sime changes here too.. so ill do the same
            }
            if ($user) {
                if ($user->user_type !== get_api_string('user_type_admin') && $user->user_type !== get_api_string('user_type_agent') && $user->user_type !== get_api_string('user_type_user')) {
                    $response['status'] = Utility::$negative;
                    $response['message'] = get_api_string('error_occurred', 'Invalid user type');
                    return $response;
                }
                
                if ((Hash::check($response['data']['password'], $user->password))) {
                    $user['token'] =  $user->createToken('new_login_'.$user->user_type)->accessToken;

                    $response['data'] = $user;
                    return $response;
                }
                $response['status'] = Utility::$negative;
                return $response;
            }
            $response['status'] = Utility::$negative;
            return $response;

        }
        catch (\Exception $ex) {
            AppUtilityAddons::treat_exception($response, $ex);
            return $response;
        }
    }

    public function create_raw($data) {
        try {
            $key = $data['key'];
            $response = self::prepare_initialization_data($data);

            AppUtilityAddons::init($response);

            if ($response['status'] < Utility::$neutral) {
                return $response;
            }
            

            unset($response['data']['key']);

            self::$instance[$key]['instance']->fill($response['data']);
            self::$instance[$key]['instance']->save();

            AppUtilityAddons::finalize($response, $key);
            

            $response['data'] = self::$instance[$key]['instance']->id;
        }
        catch(\Exception $ex) {
            AppUtilityAddons::treat_exception($response, $ex);
        }
        return $response;
    }

    public function create_bulk_raw($data) {
        $key = $data['key'];
        $bulk_key = $data['bulk'];

        $response = self::prepare_initialization_data($data);
        AppUtilityAddons::init($response);

        unset($response['data']['key']);

        if ($response['status'] < Utility::$neutral) {
            return $response;
        }

        $counter = 0;
        $ids = [];
        $exception_messages = [];

        self::$instance[$key]['instance']->insert($response['data']['bulk']);

        $successful = count($ids);
        $failed = (count($response['data']['bulk']) - count($ids));

        $response['data'] = ['record_ids' => $ids, 'successful'=> $successful, 'failed' => $failed, 'exception_messages' => $exception_messages];
        //dd($response);
        if (count($ids) <= 0) {
            $response['status'] = Utility::$negative;
            $response['message'] = get_api_string("record_exist", $key.'(s)');
        }
        return $response;
    }

    public function multi_raw($builder_data) {
        $key = $builder_data['key'];
        $response = self::prepare_initialization_data($builder_data);

        $filters = null;
        $limit = null;
        $fetch = null;
        $order = null;
        $group = null;
        $sum = null;
        $count = null;
        
        try {
            $filter_query = [];

            if($filters) {
                $filters = explode(',', $filters);
                foreach ($filters as $filter) {
                    $filter = explode('|', $filter);
                    array_push($filter_query, $filter);
                }
            }
        
            

            if (array_key_exists('limit', $response['data'])) {
                $limit = $response['data']['limit'];
                unset($response['data']['limit']);
            }

            if (array_key_exists('order', $response['data'])) {
                $order = explode(",", $response['data']['order']);
                unset($response['data']['order']);
            }

            if (array_key_exists('group', $response['data'])) {
                $group = explode(",", $response['data']['group']);
                unset($response['data']['group']);
            }

            if (array_key_exists('sum', $response['data'])) {
                $sum = $response['data']['sum'];
                unset($response['data']['sum']);
            }

            if (array_key_exists('count', $response['data'])) {
                $count = $response['data']['count'];
                unset($response['data']['count']);
            }


            unset($response['data']['multi']);

            
            $builder = $this->raw_builder($response['data']);
            
            
            if ($builder['status'] < Utility::$neutral) {
                $response['status'] = $builder['status'];
                $response['message'] = $records['message'];
                return $response;
            }

            $records = $builder['data'];


            // group *, sum *, order *, limit *, count *
            //$builder['data']

            if ($group != null) {
                $records = $records->groupBy($group);
            }

            if ($order != null) {
                $records = $records->orderBy($order[0], $order[1]);
            }

            if ($limit != null) {
                $records = $records->take($limit);
            }

            // dd($records);
            

            if ($sum != null) {
                $records = $records->sum($sum);
            }

            
            
            if ($count != null) {
                $records = $records->count();
            }

            if ($count == null && $sum == null) {
                $records = $records->get();
            }

            $response['data'] = $records;
        }
    
        catch(\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function raw_builder($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);
        $filters = null;
        $limit = null;
        $fetch = null;
        $order = null;
        $group = null;
        $sum = null;
        $multi = null;

        $withTrashed = array_key_exists('withtrashed', $response['data']);
        $multi = array_key_exists('multi', $response['data']);

        if ($multi) {
            return $this->multi_raw($data);
        }

        if (array_key_exists('filters', $response['data'])) {
            $filters = $response['data']['filters'];
        }

        if (array_key_exists('limit', $response['data'])) {
            $limit = $response['data']['limit'];
        }

        if (array_key_exists('fetch', $response['data'])) {
            $fetch = $response['data']['fetch'];
        }

        if (array_key_exists('order', $response['data'])) {
            $order = explode(",", $response['data']['order']);
        }

        if (array_key_exists('group', $response['data'])) {
            $group = explode(",", $response['data']['group']);
        }

        if (array_key_exists('sum', $response['data'])) {
            $sum = $response['data']['sum'];
        }

        try {

            $filter_query = [];

            if($filters) {
                $filters = explode(',', $filters);
                foreach ($filters as $filter) {
                    $filter = explode('|', $filter);
                    array_push($filter_query, $filter);
                }
            }

            $db_name = self::$instance[$key]['db_name'];
            if ($fetch !== null) {
                $fetch = explode(',', $fetch);
            }

            $records = null;

            if ($filters) {

                if ($limit) {
                    if ($fetch != null) {
                        if ($order != null) {
                            $records = DB::table($db_name)->select($fetch)->where($filter_query)->orderBy($order[0], $order[1])->take($limit);
                        }
                        else {
                            $records = DB::table($db_name)->select($fetch)->where($filter_query)->take($limit);
                        }

                    }
                    else {
                        if ($order != null) {
                            $records  = DB::table($db_name)->where($filter_query)->orderBy($order[0], $order[1])->take($limit);
                        }
                        else {
                            $records  = DB::table($db_name)->where($filter_query)->take($limit);
                        }

                    }

                }
                else {

                    if ($fetch != null) {
                        if ($order != null) {
                            $records  = DB::table($db_name)->select($fetch)->where($filter_query)->orderBy($order[0], $order[1]);
                        }
                        else {
                            $records  = DB::table($db_name)->select($fetch)->where($filter_query);
                        }

                    }
                    else {
                        if ($order != null) {
                            $records  = DB::table($db_name)->where($filter_query)->orderBy($order[0], $order[1]);
                        }
                        else {
                            $records  = DB::table($db_name)->where($filter_query);
                        }

                    }

                }

            }
            else {
                if ($limit) {
                    if ($fetch != null) {
                        if ($order != null) {
                            $records  = DB::table($db_name)->select($fetch)->orderBy($order[0], $order[1])->take($limit);
                        }
                        else {
                            $records  = DB::table($db_name)->select($fetch)->take($limit);
                        }

                    }
                    else {
                        if ($order != null) {
                            $records  = DB::table($db_name)->orderBy($order[0], $order[1])->take($limit);
                        }
                        else {
                            $records  = DB::table($db_name)->take($limit);
                        }

                    }

                }
                else {
                    if ($fetch != null) {
                        if ($order != null) {
                            $records  = DB::table($db_name)->select($fetch)->orderBy($order[0], $order[1]);
                        }
                        else {
                            $records  = DB::table($db_name)->select($fetch);
                        }

                    }
                    else {
                        if ($order != null) {
                            $records  = DB::table($db_name)->select($fetch)->orderBy($order[0], $order[1]);
                        }
                        else {
                            $records  = DB::table($db_name);
                        }

                    }
                }
            }

            if (!$withTrashed) {
                $records = $records->whereNull('deleted_at');
            }


            $response['data'] = $records;
        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function get_raw($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);
        $filters = null;
        $limit = null;
        $fetch = null;
        $order = null;

        if (array_key_exists('filters', $response['data'])) {
            $filters = $response['data']['filters'];
        }

        if (array_key_exists('limit', $response['data'])) {
            $limit = $response['data']['limit'];
        }

        if (array_key_exists('fetch', $response['data'])) {
            $fetch = $response['data']['fetch'];
        }

        if (array_key_exists('order', $response['data'])) {
            $order = explode(",", $response['data']['order']);
        }
        

        try {

            $builder = $this->raw_builder($response['data']);
            if ($builder['status'] < Utility::$neutral) {
                $response['status'] = $builder['status'];
                $response['message'] = $builder['message'];
            }
            else {
                $response['data'] = $this->raw_builder($response['data'])['data']->get();
            }

        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function single_raw($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);
        $filters = null;
        $fetch = null;
        $filter_query = [];


        try {
            $db_name = self::$instance[$key]['db_name'];

            if (array_key_exists('column', $response['data'])) {
                $response['data']['filters'] = $response['data']['column'].'|=|'.$response['data']['value'];
            }

            $builder = $this->raw_builder($response['data']);

            if ($builder['status'] < Utility::$neutral) {
                $response['status'] = $builder['status'];
                $response['message'] = $builder['message'];
            }
            else {
                $response['data'] = $builder['data']->first();
            }
        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }


    public function toggle_status_raw($data) {
        $key = $data['key'];
        $data['withtrashed'] = 1;
        $response = self::prepare_initialization_data($data);

        $record = $this->single_raw($response['data']);
        
        if ($record['data'] === null) {
            $response['status'] = Utility::$negative;
            $response['message'] = get_api_string('not_found', $key);

            return $response;
        }


        $db_name = self::$instance[$key]['db_name'];

        
        if ($record['data']->deleted_at === null) {
            
            DB::table($db_name)->where($response['data']['column'], $response['data']['value'])->update(['deleted_at' => Carbon::now()]);
            // DB::table(self::$instance[$key]['db_name'])->where($response['data']['column'], $response['data']['value'])->delete();
        }
        else {

            $record['data']->deleted_at = null;
            DB::table($db_name)->where($response['data']['column'], $response['data']['value'])->update(['deleted_at' => null]);

            // DB::withTrashed()->table(self::$instance[$key]['db_name'])->where($response['data']['column'], $response['data']['value'])->restore();
        }

        return $response;
    }

    public function sum_raw($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);

        try {

            $db_name = self::$instance[$key]['db_name'];

            $builder = $this->raw_builder($response['data']);
            if ($builder['status'] < Utility::$neutral) {
                $response['status'] = $builder['status'];
                $response['message'] = $builder['message'];
            }
            else {
                // $response['data'] = $this->raw_builder($response['data'])['data']->sum($response['data']['sum']);

                if ($builder['status'] < Utilities::$neutral) {
                    $response['status'] = $builder['status'];
                    $response['message'] = $builder['message'];
                    return $response;
                }
                
                $response['data'] = $builder['data']->sum($response['data']['sum']);
                $response['data'] = ['sum' => $response['data']];
            }
        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function count_raw($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);
        $filters = null;
        $limit = null;
        $fetch = null;

        if (array_key_exists('filters', $response['data'])) {
            $filters = $response['data']['filters'];
        }

        if (array_key_exists('limit', $response['data'])) {
            $limit = $response['data']['limit'];
        }

        if (array_key_exists('fetch', $response['data'])) {
            $fetch = $response['data']['fetch'];
        }

        try {


            $filter_query = [];

            if($filters) {
                $filters = explode(',', $filters);
                foreach ($filters as $filter) {
                    $filter = explode('|', $filter);
                    array_push($filter_query, $filter);
                }
            }

            $db_name = self::$instance[$key]['db_name'];
            if ($fetch !== null) {
                $fetch = explode(',', $fetch);
            }

            $builder = $this->raw_builder($response['data']);
            if ($builder['status'] < Utility::$neutral) {
                $response['status'] = $builder['status'];
                $response['message'] = $builder['message'];
            }
            else {
                $response['data'] = $this->raw_builder($response['data'])['data']->get()->count();
            }

            $response['data'] = ['count' => $response['data']];
        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function group_raw($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);
        $filters = null;
        $fetch = null;
        $filter_query = [];


        try {
            $db_name = self::$instance[$key]['db_name'];

            if (array_key_exists('column', $response['data'])) {
                $response['data']['filters'] = $response['data']['column'].'|=|'.$response['data']['value'];
            }

            $builder = $this->raw_builder($response['data']);

            if ($builder['status'] < Utility::$neutral) {
                $response['status'] = $builder['status'];
                $response['message'] = $builder['message'];
            }
            else {
                //
                $response['data'] = $builder['data']->groupBy($response['data']['group'])->get();
            }
        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function update_raw($data) {
        $key = $data['key'];
        $response = self::prepare_initialization_data($data);
        try {
            unset($response['data']['key']);

            $db_name = self::$instance[$key]['db_name'];

            $filters = null;

            if (array_key_exists('filters', $response['data'])) {
                $filters = $response['data']['filters'];
            }

            unset($response['data']['filters']);

            $filter_query = [];


            if($filters) {
                $filters = explode(',', $filters);
                foreach ($filters as $filter) {
                    $filter = explode('|', $filter);
                    array_push($filter_query, $filter);
                }
            }
            if (count(DB::table($db_name)->where($filter_query)->get()) < 1) {
                $response['status'] = Utility::$negative;
                $response['message'] = get_api_string('not_found', $key);
            }
            else {
                DB::table($db_name)->where($filter_query)->update($response['data']);
                $response['data'] = DB::table($db_name)->where($filter_query)->first();
            }
        }
        catch (\Exception $ex) {
            $response['status'] = Utility::$error;
            $response['message'] = $ex->getMessage();
        }

        return $response;
    }

    public function create(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];


            $response = $this->create_raw($data);

            if (($response['status']) < Utility::$negative) {
                throw new \Exception($response['message']);
            }
            else if (($response['status']) < Utility::$neutral) {
                return \prepare_json(Utility::$negative, [$key => [], 'data' => $data, 'key' => $key], $response['message'], Utility::$_400);
            }

            return \prepare_json(Utility::$positive, [$key => $response['data'], 'data' => $data, 'key' => $key], \get_api_string('created_ok', $key), Utility::$_201);

        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],$ex->getMessage(), Utility::$_500);
        }
    }

    public function create_bulk(Request $request) {
        try {
            $data = $request->all();
            $key = $data['key'];


            $response = $this->create_bulk_raw($data);

            if (($response['status']) < Utility::$negative) {
                throw new \Exception($response['message']);
            }
            else if (($response['status']) < Utility::$neutral) {
                return \prepare_json(Utility::$negative, [$key => $response['data'], 'data' => $data, 'key' => $key], $response['message'], Utility::$_400);
            }

            return \prepare_json(Utility::$positive, [$key => $response['data'], 'data' => $data, 'key' => $key], \get_api_string('created_ok', $key), Utility::$_201);

        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function get(Request $request) {
        try {
            $data = $request->all();

            $key = $data['key'];
            $result = $this->get_raw($data);
            

            if ($result['status'] < Utility::$negative) {
                throw new \Exception($result['message']);
            }

            return \prepare_json(Utility::$neutral, [$key => $result['data'], 'key' => $key], \get_api_string('generic_ok'));

        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function single(Request $request) {
        try {
            $data = $request->all();

            $result = $this->single_raw($data);
            $key = $data['key'];

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json(Utility::$negative, [$key => [], 'key' => $key], \get_api_string('not_found', $key), Utility::$_400);
            }
            else {
                return \prepare_json(Utility::$neutral, [$key => $result['data'], 'key' => $key], \get_api_string('generic_ok'));
            }
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function create_user(Request $request) {
        try {
            $data = $request->all();

            $result = $this->create_user_raw($data);
            $key = $data['key'];


            if ($result['status'] < Utility::$neutral) {
                return \prepare_json(Utility::$negative, [$key => [], 'key' => $key], $result['message']);
            }

            return \prepare_json(Utility::$positive, [$key => $result['data'], 'key' => $key], \get_api_string('generic_ok'), Utility::$_201);

        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function login(Request $request) {
        try {
            $data = $request->all();

            $result = $this->login_raw($data);
            $key = $data['key'];

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json(Utility::$negative, [$key => [], 'key' => $key], \get_api_string('not_found', $key), Utility::$_400);
            }
            else {
                return \prepare_json(Utility::$positive, [$key => $result['data'], 'key' => $key], \get_api_string('generic_ok'));
            }

        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [],\get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function toggle_status(Request $request) {
        try {

            $data = $request->all();
            $key = $data['key'];

            $result = $this->toggle_status_raw($data);

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json(Utility::$negative, [$key => [], 'key' => $key], $result['message'], Utility::$_400);
            }
            
            return \prepare_json(Utility::$positive, [], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function sum(Request $request) {
        try {

            $data = $request->all();
            $key = $data['key'];

            $result = $this->sum_raw($data);

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json($result['status'], [], $result['message']);
            }


            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result['data']], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function group(Request $request) {
        try {

            $data = $request->all();
            $key = $data['key'];

            $result = $this->group_raw($data);

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json($result['status'], [], $result['message']);
            }


            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result['data']], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function count(Request $request) {
        try {

            $data = $request->all();
            $key = $data['key'];

            $result = $this->count_raw($data);

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json($result['status'], [], $result['message']);
            }


            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result['data']], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function update(Request $request) {
        try {

            $data = $request->all();
            $key = $data['key'];

            $result = $this->update_raw($data);

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json($result['status'], [], $result['message']);
            }

            return \prepare_json(Utility::$positive, [$key => $result['data'], 'key' => $key], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function run_request(Request $request) {
        try {

            $data = $request->all();

            $context = null;

            $headers = [
                "email" => "member@mail.com",
                "password" => "12345678",
                "Accept" => "application/json",
                'Content-Type'  => 'application/json',
            ];

            $client_data = [
                "base_uri" => 'http://34.74.220.10/',
            ];

            $url = 'http://34.74.220.10/ringo/public/ringoPaytest/public/api/agent/p2';


            if (array_key_exists('headers', $data)) {
                $client_data['headers'] = $headers;
            }

            $client = new Client($client_data);

            $body = $data['content'];
            $body = json_encode($body);

            if (array_key_exists('options', $data)) {
                $response = $client->request(strtoupper($data['method']), $url, ['body' => $body]);
            }
            else {
                $response = $client->request('GET', $url);
            }

            $response = json_decode($response->getBody());

            return \prepare_json(Utility::$neutral, $response, \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

    public function multi(Request $request) {
        try {

            
            $data = $request->all();
            $key = $data['key'];

            $result = $this->multi_raw($data);
            

            if ($result['status'] < Utility::$neutral) {
                return \prepare_json($result['status'], [], $result['message']);
            }


            return \prepare_json(Utility::$neutral, ['key' => $key, $key => $result['data']], \get_api_string('generic_ok'));
        }
        catch (\Exception $ex) {
            return \prepare_json(Utility::$error, [], \get_api_string('error_occurred').$ex->getMessage(), Utility::$_500);
        }
    }

}
