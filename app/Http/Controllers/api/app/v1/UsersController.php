<?php

namespace App\Http\Controllers\api\app\v1;

use App\User;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\api\ResponseController;

class UsersController extends ResponseController {


    public function otp_verification(UsersRequest $request) {
        try {
            $userObj = User::where(["status" => 1, "otp" => $request->otp, "id" => $request->user_id])->first();
            if ($userObj) {
                if ($userObj->otp == trim($request->otp)) {

                    $static_mobile_number = $userObj->get_patient_details->mobile_number;
                    if ($static_mobile_number != '7069140591') {
                        //do not update otp for static mobile
                        $userObj->otp = null;
                    }
                    $userObj->save();
                
                    $user_details = User::with('get_patient_details')->findOrFail($userObj->id);
                    $is_doctor = DoctorAssociated::where(['patient_id' => $userObj->id, 'status' => 1])->count();
                    $user_details['token'] = $request->user()->createToken('token')->accessToken;
                    return $this->_sendResponse("Verify otp successfully.", $user_details);
                } else {
                    return $this->_sendErrorResponse("Sorry! You have entered wrong otp.", $request->all());
                }
            } else {
                return $this->_sendErrorResponse("Invalid OTP Try Again.", $request->all(), 400);
            }
        } catch (\Exception $e) {
            return $this->_sendErrorResponse("Unable to process.", $e->getMessage(), 500);
        }
    }


    public function registration(UsersRequest $request) {
        try {
            $otp = $this->_generateOtp();
            $user = new User;
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = bcrypt('User@123');
            $user->otp = $otp;
            $user->status = 1;
            if ($user->save()) {
                $details['user'] = User::with('get_patient_details')->findOrFail($user->id);
                $details['user']['token'] = $user->createToken('token')->accessToken;
                $details['user']['app_details'] = $app_detail;
                return $this->_sendResponse("Registration successfully.", $details['user']);
            }
        } catch (\Exception $e) {
            return $this->_sendErrorResponse("Unable to process.", $e->getMessage(), 500);
        }
    }

    public function get_profile(Request $request) {
        try {
            $userObj = User::with('get_patient_details')->findOrFail($request->user()->id);
            if ($userObj) {
                $userObj['token'] = $request->bearerToken();                
                return $this->_sendResponse("Details retrieved successfully", $userObj);
            } else {
                return $this->_sendErrorResponse("Error.", $request->all());
            }
        } catch (\Exception $e) {
            return $this->_sendErrorResponse("Unable to process.", $e->getMessage(), 500);
        }
    }

    public function update_user_details(UsersRequest $request) {
        try {
            $file_name = '';
                $user_details = User::findOrFail($request->user()->id);
                if (isset($request->profile_picture)) {
                    $user_profile = User::findOrFail($request->user()->id);
                    $folder = $request->user()->id;

                    $image = request()->file('profile_picture');
                    $file_name = time() . "_" . rand(0000, 9999) . '.' . $image->getClientOriginalExtension();


                    if (Storage::disk('s3')->has('users/' . $request->user()->id . '/profile_picture' . '/' . $user_profile->profile_picture)) {
                        $delete_path = 'users/' . $request->user()->id . '/profile_picture' . '/' . $user_profile->profile_picture;
                        Storage::disk('s3')->delete($delete_path);

                        $filePath = 'users/' . $request->user()->id . '/profile_picture' . '/' . $file_name;
                        Storage::disk('s3')->put($filePath, file_get_contents($image));
                        $user_details->profile_picture = $file_name;
                    } else {
                        $filePath = 'users/' . $request->user()->id . '/profile_picture' . '/' . $file_name;
                        Storage::disk('s3')->put($filePath, file_get_contents($image));
                        $user_details->profile_picture = $file_name;
                    }
                }

                if (isset($request->username)) {
                    $user_details->username = $request->username;
                }
                if (isset($request->email)) {
                    $user_details->email = $request->email;
                }
                $user_details->save();
            $data['user'] = User::with('get_patient_details')->findOrFail($request->user()->id);
            $data['user']['token'] = $request->bearerToken();
            return $this->_sendResponse("User Updated successfully", $data);
        } catch (Exception $e) {
            return $this->_sendErrorResponse(trans('messages.unableprocess'), $e->getMessage(), 500);
        }
    }

    /*
      Generate OTP For Forgot Password Verification
     */

    public function _generateOtp() {
        $otp = rand(1000, 9999);
        return $otp;
    }
}
