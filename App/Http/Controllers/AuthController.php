<?php

namespace Themes\Ecommerce\App\Http\Controllers;

use App\Models\Account;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ProtoneMedia\Splade\Facades\Toast;
use TomatoPHP\TomatoCrm\Facades\TomatoAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        TomatoAuth::loginBy('email');
        TomatoAuth::guard('accounts');
        TomatoAuth::requiredOtp(true);
        TomatoAuth::model(Account::class);
        TomatoAuth::createValidation([
            "name" => "required|max:191|string",
            "phone" => "required|max:14|string|unique:accounts,phone",
            "email" => "required|email|max:191|string|unique:accounts,email",
            "password" => "required|confirmed|min:6|max:191"
        ]);
    }

    public function login(){
        if(!auth('accounts')->user()){
            return view('themes::auth.login');
        }

        return redirect()->route('profile.index');
    }

    public function check(Request $request){
        $login = TomatoAuth::login(
            request: $request,
            type:'web'
        );
        if($login->success){
            Toast::success($login->message)->autoDismiss(2);
            return redirect()->route('profile.index');
        }
        else {
            if($login->message === __("Your account is not active yet")){
                Toast::danger($login->message)->autoDismiss(2);
                session()->put('email', $request->get('email'));
                TomatoAuth::resend($request, 'web');
                return redirect()->route('otp');
            }
            else {
                Toast::danger($login->message)->autoDismiss(2);
                return back();
            }

        }
    }

    public function register(){
        return view('themes::auth.register');
    }

    public function store(Request $request){
        $register = TomatoAuth::register(
            request: $request,
            type: 'web'
        );
        if($register->success){
            session()->put('email', $request->get('email'));

            Toast::success($register->message)->autoDismiss(2);
            return redirect()->route('otp');
        }
        else {
            Toast::danger($register->message)->autoDismiss(2);
            return redirect()->back();
        }
    }

    public function otp(){
        return view('themes::auth.otp');
    }

    public function forget(){
        return view('themes::auth.forget');
    }

    public function resend(Request $request){
        $request->merge([
            "email" => session()->get('email')
        ]);

        $resend = TomatoAuth::resend($request, 'web');
        if($resend->success){
            Toast::success($resend->message)->autoDismiss(2);
            return redirect()->back();
        }
        else {
            Toast::danger($resend->message)->autoDismiss(2);
            return redirect()->back();
        }
    }

    public function email(Request $request){
        $reset = TomatoAuth::reset($request, 'web');
        if($reset->success){
            session()->put('email', $request->get('email'));

            Toast::success($reset->message)->autoDismiss(2);
            return redirect()->route('reset');
        }
        else {
            Toast::danger($reset->message)->autoDismiss(2);
            return redirect()->back();
        }
    }

    public function reset(){
        if(session()->get('email')){
            return view('themes::auth.reset');
        }

    }

    public function otpCheck(Request $request){
        if(session()->get('email')){
            $request->merge([
                "email" => session()->get('email')
            ]);

            $checkOtp = TomatoAuth::otp($request, 'web');

            if($checkOtp->success){
                session()->put('email', $request->get('email'));
                session()->put('otp', $request->get('otp_code'));

                Toast::success(__('Your Otp is correct your account is active now'))->autoDismiss(2);
                return redirect()->route('login');
            }
            else {
                Toast::danger($checkOtp->message)->autoDismiss(2);
                return redirect()->back();
            }
        }
        else {
            return redirect()->route('forget');
        }

    }

    public function password(Request $request){
        $request->merge([
           "email" => session()->get('email'),
        ]);
        $reset = TomatoAuth::password($request, 'web');
        if($reset->success){
            session()->forget('email');

            Toast::success($reset->message)->autoDismiss(2);
            return redirect()->route('login');
        }
        else {
            Toast::danger($reset->message)->autoDismiss(2);
            return redirect()->back();
        }
    }
}
