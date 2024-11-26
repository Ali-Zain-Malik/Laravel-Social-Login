<?php

namespace App\Http\Controllers\Auth\Social;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver("google")->redirect();
    }

    public function callback()
    {
        DB::beginTransaction();
        try 
        {
            $google_user = Socialite::driver("google")
                                    ->setHttpClient(new Client(['verify' => false])) // Set SSL verification to false for working in local.
                                    ->user();
            $user = User::where("google_id", $google_user->getId())->first();
            if(empty($user))
            {
                $new_user = User::create([
                    "name" => $google_user->getName(),
                    "email" => $google_user->getEmail(),
                    "google_id" => $google_user->getId(),
                ]);
                Auth::login($new_user);
                DB::commit();
                return redirect()->route("dashboard");
            }
            else
            {
                Auth::login($user);
                return redirect()->route("dashboard");
            }
        } 
        catch (\Throwable $th) 
        {
            DB::rollBack();
            return redirect()->back()->with("error", $th->getMessage()); 
        }
    }
}
