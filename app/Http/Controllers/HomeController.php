<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Session;
use App\Models\School;
use Mail;
use App\Mail\SchoolEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Modules\Cms\Models\CmsGalleryItem;
use App\Modules\Cms\Models\CmsNotice;

class HomeController extends Controller
{
    public function index()
    {
        // Admission itself happens in the MCC Hello Future app, so the landing page
        // only needs notices and gallery from the database.
        return view('frontend.landing', [
            'notices'         => $this->landingQuery('notices', fn () => CmsNotice::live()->take(4)->get()),
            'gallery'         => $this->landingQuery('gallery', fn () => CmsGalleryItem::published()->take(10)->get()),
        ]);
    }

    /**
     * Live landing data is a nice-to-have: if a table is missing or a query
     * fails, log it and render the static fallback instead of a 500.
     */
    private function landingQuery(string $what, \Closure $query): \Illuminate\Support\Collection
    {
        try {
            return collect($query());
        } catch (\Throwable $e) {
            Log::warning("Landing page: could not load {$what}", ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function superadminHome()
    {
        return view('superadminHome');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function adminDashboard()
    {
        return view('admin.dashboard');
    }


    public function schoolCreate(Request $request)
    {
        $data = $request->all();
        $school_email = $data['school_email'];
        $admin_email = $data['admin_email'];
        $duplicate_school_email_check = School::get()->where('email', $school_email);
        $duplicate_admin_email_check = User::get()->where('email', $admin_email);
        $recaptcha_secret = get_settings('recaptcha_secret_key');
        $recaptcha_switch = get_settings('recaptcha_switch_value');
        
        if($recaptcha_switch == 'Yes'){
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$data['g-recaptcha-response']);
        
                $response = json_decode($response, true);
            if($response['success'] === true){
                if(count($duplicate_school_email_check) == 0 && count($duplicate_admin_email_check) == 0) {
                $school = School::create([
                    'title' => $data['school_name'],
                    'email' => $data['school_email'],
                    'phone' => $data['school_phone'],
                    'address' => $data['school_address'],
                    'school_info' => $data['school_info'],
                    'status' => '2',
                ]);
    
                if (isset($school->id) && $school->id != "") {
    
                    $data['status'] = '1';
                    $data['session_title'] = date("Y");
                    $data['school_id'] = $school->id;
    
                    $session = Session::create($data);
    
                    School::where('id', $school->id)->update([
                        'running_session' => $session->id,
                    ]);
                    
                    if (!empty($data['photo'])) {
    
                        $imageName = time() . '.' . $data['photo']->extension();
    
                        $data['photo']->move(public_path('assets/uploads/user-images/'), $imageName);
    
                        $photo  = $imageName;
                    } else {
                        $photo = '';
                    }
                    $info = array(
                        'gender' => $data['gender'],
                        'blood_group' => $data['blood_group'],
                        'birthday' => isset($data['birthday'])? strtotime($data['birthday']):"",
                        'phone' => $data['admin_phone'],
                        'address' => $data['admin_address'],
                        'photo' => $photo
                    );
                    $data['user_information'] = json_encode($info);
                    User::create([
                        'name' => $data['admin_name'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['admin_password']),
                        'role_id' => '2',
                        'school_id' => $school->id,
                        'user_information' => $data['user_information'],
                        'status' => 1,
                    ]);
                }
                if(!empty(get_settings('smtp_user')) && (get_settings('smtp_pass')) && (get_settings('smtp_host')) && (get_settings('smtp_port'))){
                    Mail::to($data['admin_email'])->send(new SchoolEmail($data));
                }
    
                return redirect()->route('login')->with('message', 'School Created Successfully');
            } else {
                return redirect()->back()->with('warning','Some of the emails have been taken.');
            }
            }else{
            return redirect()->back()->with('warning', 'Something went wrong');
            }
        }else{
            if(count($duplicate_school_email_check) == 0 && count($duplicate_admin_email_check) == 0) {
                $school = School::create([
                    'title' => $data['school_name'],
                    'email' => $data['school_email'],
                    'phone' => $data['school_phone'],
                    'address' => $data['school_address'],
                    'school_info' => $data['school_info'],
                    'status' => '2',
                ]);
    
                if (isset($school->id) && $school->id != "") {
    
                    $data['status'] = '1';
                    $data['session_title'] = date("Y");
                    $data['school_id'] = $school->id;
    
                    $session = Session::create($data);
    
                    School::where('id', $school->id)->update([
                        'running_session' => $session->id,
                    ]);
                    
                    if (!empty($data['photo'])) {
    
                        $imageName = time() . '.' . $data['photo']->extension();
    
                        $data['photo']->move(public_path('assets/uploads/user-images/'), $imageName);
    
                        $photo  = $imageName;
                    } else {
                        $photo = '';
                    }
                    $info = array(
                        'gender' => $data['gender'],
                        'blood_group' => $data['blood_group'],
                        'birthday' => isset($data['birthday'])? strtotime($data['birthday']):"",
                        'phone' => $data['admin_phone'],
                        'address' => $data['admin_address'],
                        'photo' => $photo
                    );
                    $data['user_information'] = json_encode($info);
                    User::create([
                        'name' => $data['admin_name'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['admin_password']),
                        'role_id' => '2',
                        'school_id' => $school->id,
                        'user_information' => $data['user_information'],
                        'status' => 1,
                    ]);
                }
                if(!empty(get_settings('smtp_user')) && (get_settings('smtp_pass')) && (get_settings('smtp_host')) && (get_settings('smtp_port'))){
                    Mail::to($data['admin_email'])->send(new SchoolEmail($data));
                }
    
                return redirect()->route('login')->with('message', 'School Created Successfully');
            } else {
                return redirect()->back()->with('warning','Some of the emails have been taken.');
            }
        }
        
    }
    
    public function webRedirectToPayFee(Request $request)
    {
        // Remove the 'Basic ' prefix
        $base64Credentials = substr($request->query('auth'), 6);

        // Decode the base64-encoded string
        $credentials = base64_decode($base64Credentials);

        // Split the decoded string into email and password
        list($email, $password, $timestamp) = explode(':', $credentials);

        // Get the current timestamp
        $timestamp1 = strtotime(date('Y-m-d'));

        $difference = $timestamp1 - $timestamp;

        if($difference < 86400) {
            if (auth()->attempt(array('email' => $email, 'password' => $password))) {
                // Authentication passed...
                return redirect()->intended('/student/fee_manager/payment/'.$request->query('fee_id'));
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Invalid email or password',
            ]);
        } else {
            return redirect()->route('login')->withErrors([
                'email' => 'Token expired!',
            ]);
        }
    }
}
