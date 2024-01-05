<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $method = request()->segment(2);
        
        
            $current_route_name = Route::currentRouteName();
            switch ($current_route_name) {
                case "login":
                    return [
                        'mobile_number'=>'required|numeric|exists:patient_details,mobile_number,status,1',
                        'phone_code'=>'required'
                    ];
                    break;
                case "registration":
                    return [
                        'email'=>'required|email|unique:users,email,NULL,id,deleted_at,NULL',
                        'username' => 'required|min:2|max:50',
                    ];
                    break;
                case "otp_verification":
                    return [
                        'otp' => 'required|max:4|min:4|digits:4',
                        'user_id'=>'required'
                    ];
                    break;
                case "resend_otp":
                    return [
                        'mobile_number'=>'required|numeric|exists:patient_details,mobile_number,status,1',
                        'phone_code'=>'required'
                    ];
                    break;
                case "profile.update":
                    return [
                        
                        'email'=>'email|unique:users,email,'.Auth::user()->id.',id,deleted_at,NULL',
                        'pincode'=>'numeric',
                        'mobile_number'=>'numeric|unique:patient_details,mobile_number,'.Auth::user()->id.',user_id,deleted_at,NULL',
                        'delivery_date'=>'date|date_format:Y-m-d|after_or_equal:today'
                    ];
                    break;
                
                default:
                    return [];
            }
    }

    public function messages(){
        $current_route_name = Route::currentRouteName();

        if($current_route_name == "login"){
            return [
                    'password.required_if' => 'Password required.','mobile_number.exists'=>'Entered mobile number is not registered with us.',
                    'email.exists'=>'Invalid email or password'
                ];
        }
        else if($current_route_name == "forgot.reset_password"){
            return [
                'password_confirmation.required' => 'Confirm Password is required'
            ];
        }
      
        return [];
    }

    protected function failedValidation(Validator $validator){
        $error_response = [
            'code' => 200,
            'status' => false,
            'message' =>  $validator->errors()->first(),
            'data' => \Request::all()
        ];
        throw new HttpResponseException(response()->json($error_response,200));
    }

   

}
