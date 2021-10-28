<?php

namespace App\Utilities;
use Illuminate\Support\Facades\Validator;

class AppStrings
{
    public static $app_strings = [];


    public static function get_app_string($title, $plug_in_var) {

        $app_strings = [
            'error_occurred' => 'An error Occurred'.(($plug_in_var != '') ? ':'.$plug_in_var : $plug_in_var ).'.',
            'user_type_user' => 'user',
            'user_type_customer' => 'customer',
            'user_type_admin' => 'admin',
            'user_type_agent' => 'agent',
            'invalid_action' => 'Invalid action attempted'.(($plug_in_var != '') ? ':'.$plug_in_var : $plug_in_var ).'.',
            'wrong_password' => 'Incorrect Password.',
            'login_ok' => 'Login Successful.',
            'signup_error' => 'An error occurred during signup. Please try again later.',
            'signup_ok' => 'Signup successful.',
            'invalid_phone_no' => 'Phone Number is invalid.',
            'profile_edit_ok' => $plug_in_var."'s profile editted.",
            'profile_edit_not_ok' => $plug_in_var."'s profile failed to edit.",
            'generic_ok' => "Action Successful".(($plug_in_var != '') ? ':'.$plug_in_var : $plug_in_var ).'.',
            'invalid_file' => 'Invalid file upload',
            'unauthorized' => 'Unauthorized to perform that action.',
            'no_images' => 'No images found for upload',
            'files_uploaded' => 'Files uploaded',
            'file_not_ploaded' => 'Files not uploaded',
            'no_record' => $plug_in_var.": no record to show.",
            'created' => $plug_in_var . ' saved.',
            'created_ok' => $plug_in_var . '  has been created.',
            'not_created' => $plug_in_var . ' not saved.',
            'record_exist' => $plug_in_var . ' already saved.',
            'bank_failed_business_ok' => 'Business saved but bank details failed to save.',
            'client_failed_signup_ok' => 'Signup successful but registration as a client failed.',
            'customer_failed_signup_ok' => 'Signup successful but registration as a customer failed.',
            'item_image_required' => 'Product Image/(s) required for Submission.',
            'account_not_found' => 'Account details incorrect',
            'no_items' => 'No Items to display.',
            'purchase_ok' => 'Items purchased successfully',
            'wrong_user_type' => 'Incorrect User type passed.',
            'old_password_dont_match' => "Old Password incorrect.",
            'passwords_dont_match' => "Passwords don't match.",
            'password_changed' => "Passwords updated.",
            'enter_reset_code' => 'Please enter code sent to your email.',
            'code_resent' => 'Code resent.',
            'reset_code_valid' => 'Reset Code validated.',
            'reset_code_wrong' => 'Code incorrect.',
            'customer_failed_signup' => 'Customer profile failed to be created',
            'admin_failed_signup' => 'Admin profile failed to be created',
            'not_found' => $plug_in_var . " not found.",
            'phone_used' => "Account with that phone number already exist.",
            'only_customer_user_type' => 'Invalid action attempted, Only Customers can '.(($plug_in_var != '') ? ''.$plug_in_var : $plug_in_var ).'.',
            'only_admin_user_type' => 'Invalid action attempted, Only Admins can '.(($plug_in_var != '') ? ''.$plug_in_var : $plug_in_var ).'.',
            'updated' => $plug_in_var . ' updated.',
            'user_type_client' => 'client',
            'not_verified' => "Please Verify ". ($plug_in_var == "" ? "Account" : $plug_in_var),
            'email_exist' => 'Account with this Email exist.',
            'token_valid' => 'Token Validated.',
            'token_invalid' => 'Invalid Token.',
            'insufficient_balance' => 'Insufficient Balance.',
        ];

        return ($title == '') ? $app_strings['generic_ok'] : $app_strings[$title];
    }
}
