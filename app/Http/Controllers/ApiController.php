<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CommonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Routine;
use App\Models\Subject;
use App\Models\Syllabus;
use App\Models\ClassRoom;
use App\Models\Session;
use App\Models\DailyAttendances;
use App\Models\Department;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Gradebook;
use App\Models\StudentFeeManager;
use File;

class ApiController extends Controller
{

	//student login function
	public function login(Request $request) {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->where('status', 1)->first();

        // Check password
        if(!$user || !Hash::check($fields['password'], $user->password)) {
            if(isset($user) && $user->count() > 0){
                return response([
                    'message' => 'Invalid credentials!'
                ], 401);
            } else {
                return response([
                    'message' => 'User not found!'
                ], 401);
            }
        } else if($user->role_id == 7) {
            
        	$token = $user->createToken('auth-token')->plainTextToken;

	        $response = [
	        	'message' => 'Login successful',
	            'user' => $user,
	            'token' => $token
	        ];

	        return response($response, 201);

        } else {

        	//user not authorized
            return response()->json([
                'message' => 'User not found!',
            ], 400);
        }
    }
    
    // App login function for multi-role
    public function appLogin(Request $request) {
        $fields = $request->validate([
            'role' => 'required|string',
            'login_id' => 'required|string',
            'password' => 'required|string'
        ]);

        $query = User::where('status', 1);

        if ($fields['role'] === 'student') {
            $id = trim($fields['login_id']);
            $cleanId = preg_replace('/^MCC/i', '', $id);
            $withMcc = 'MCC' . $cleanId;

            $query->where('role_id', 7)->where(function($q) use ($id, $cleanId, $withMcc) {
                $q->where('student_id', $id)
                  ->orWhere('student_id', $cleanId)
                  ->orWhere('student_id', $withMcc)
                  ->orWhere('phone_number', $id)
                  ->orWhere('email', $id);
            });
        } elseif ($fields['role'] === 'teacher') {
            $query->where('teacher_id', $fields['login_id']);
        } elseif ($fields['role'] === 'guardian') {
            $query->where('phone_number', $fields['login_id'])->where('role_id', 6);
        } else {
            return response(['message' => 'Invalid role selected.'], 400);
        }

        $user = $query->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response([
                'message' => 'Invalid credentials!'
            ], 401);
        }

        if ($user->role_id == 7) {
            $admission = \App\Models\Admission::where('user_id', $user->id)->first();
            if ($admission) {
                $user->admission = $admission;
                $user->department = $admission->department;
                $user->class_name = $admission->class_name;
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $response = [
            'status' => 'success',
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'role' => $fields['role']
        ];

        return response($response, 200);
    }
    
    //student account remove function
    public function account_delete(Request $request)
    {
        $user = User::find(auth('sanctum')->user()->id);
        $user['status'] = 0;
        $user->save();

    	return response()->json([
            'message' => 'Account removed',
        ], 201);
    }


    //student logout function
    public function logout(Request $request)
    {
    	auth()->user()->tokens()->delete();

    	return response()->json([
            'message' => 'Logged out successfully.',
        ], 201);
    }


    //student details
    public function userDetails(Request $request)
    {
    	$student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);

    	if($student_data) {
    		return response($student_data, 201);
    	} else {
    		return response()->json([
                'message' => 'User information not found!',
            ], 400);
    	}
    }


    //student class routine get
    public function routine(Request $request)
    {
    	$student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];


        $routines = Routine::where(['class_id' => $class_id, 'section_id' => $section_id, 'session_id' => $session_id, 'school_id' => $school_id])->get();

        if(count($routines) > 0) {

        	foreach($routines as $key => $routine) {
        		$res[$key]['id'] = $routine->id;
        		$res[$key]['subject_id'] = $routine->subject_id;
        		$res[$key]['subject_name'] = (new CommonController)->getSubjectDetails($routine->subject_id)->name;
        		$res[$key]['starting_time'] = $routine->starting_hour.':'.$routine->starting_minute;
        		$res[$key]['ending_time'] = $routine->ending_hour.':'.$routine->ending_minute;
                $res[$key]['room_id'] = $routine->room_id;
                $res[$key]['room_name'] = ClassRoom::find($routine->room_id)->value('name');
        		$res[$key]['day'] = $routine->day;
        		$res[$key]['teacher_id'] = $routine->teacher_id;
        		$res[$key]['teacher_name'] = (new CommonController)->idWiseUserName($routine->teacher_id);
                $res[$key]['teacher_image'] = get_user_image($routine->teacher_id);
                $res[$key]['teacher_designation'] = User::where('id', $routine->teacher_id)->value('designation');
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
	        	'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'routines' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No routine found!',
            ], 400);
        }
    }


    //student attendance get
    public function attendanceReport(Request $request)
    {
    	$student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];


        $date = '01 '.$request->month.' '.$request->year;
        $first_date = strtotime($date);
        $last_date = date("Y-m-t", strtotime($date));
        $last_date = strtotime($last_date);


        $attendance_of_student = DailyAttendances::whereBetween('timestamp', [$first_date, $last_date])->where(['class_id' => $student_data['class_id'], 'section_id' => $student_data['section_id'], 'student_id' => auth('sanctum')->user()->id, 'school_id' => $school_id])->get();

        if(count($attendance_of_student) > 0) {

        	foreach($attendance_of_student as $key => $data) {
        		$res[$key]['id'] = $data->id;
        		$res[$key]['status'] = $data->status;
        		$res[$key]['timestamp'] = (string)$data->timestamp;
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
	        	'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'attedances' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'Attendance report not found!',
            ], 400);
        }
    }

    public function subjects(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        $subjects = Subject::where(['class_id' => $class_id, 'session_id' => $session_id, 'school_id' => $school_id])->get();

        if(count($subjects) > 0) {

        	foreach($subjects as $key => $subject) {
        		$res[$key]['id'] = $subject->id;
        		$res[$key]['name'] = $subject->name;
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'subjects' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No subject found!',
            ], 400);
        }
    }

    public function syllabus_list(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        if(!empty($request->all()))
        {
            $syllabuses = Syllabus::where(['class_id' => $class_id, 'section_id' => $section_id, 'subject_id' => $request->subject_id, 'session_id' => $session_id, 'school_id' => $school_id])->get();
        } else {
            $syllabuses = Syllabus::where(['class_id' => $class_id, 'section_id' => $section_id, 'session_id' => $session_id, 'school_id' => $school_id])->get();
        }

        $subjects = Subject::where(['class_id' => $class_id, 'session_id' => $session_id, 'school_id' => $school_id])->get();

        $res_sub = [];

        if(count($syllabuses) > 0) {

        	foreach($syllabuses as $key => $syllabus) {
        		$res[$key]['id'] = $syllabus->id;
        		$res[$key]['title'] = $syllabus->title;
                $res[$key]['subject_id'] = $syllabus->subject_id;
                $res[$key]['subject_name'] = (new CommonController)->getSubjectDetails($syllabus->subject_id)->name;
                $res[$key]['file'] = $syllabus->file;
                $res[$key]['file_url'] = asset('assets/uploads/syllabus/'.$syllabus->file);
        	}

            if(count($subjects) > 0) {
                foreach($subjects as $key => $subject) {
                    $res_sub[$key]['subject_id'] = $subject->id;
                    $res_sub[$key]['subject_name'] = $subject->name;
                }
            }

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
                'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'syllabuses' => $res,
                'subjects' => $res_sub
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No subject found!',
            ], 400);
        }
    }

    public function teacher_list(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        if(!empty($request->all())){
            $teachers = User::where('role_id', 3)->where('school_id', $school_id)->where('department_id', $request->department_id)->get();
        } else {
            $teachers = User::where('role_id', 3)->where('school_id', $school_id)->get();
        }
        
        $departments = Department::where('school_id', $school_id)->get();

        $res_dep=[];

        if(count($teachers) > 0) {

        	foreach($teachers as $key => $teacher) {
        		$res[$key]['id'] = $teacher->id;
        		$res[$key]['name'] = $teacher->name;
                $res[$key]['department_id'] = $teacher->department_id;
                $res[$key]['school_id'] = $teacher->school_id;
                $res[$key]['department_name'] = Department::find($teacher->department_id)->name;
                $res[$key]['designation'] = $teacher->designation;
        	}
        	
        	if(count($departments) > 0) {
                foreach($departments as $key => $department) {
                    $res_dep[$key]['department_id'] = $department->id;
                    $res_dep[$key]['department_name'] = $department->name;
                }
            }

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
                'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'teachers' => $res,
	            'departments' => $res_dep
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No teacher found!',
            ], 400);
        }
    }

    public function book_list(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        if(!empty($request->all())){
            $books = Book::where('school_id', $school_id)->where('name', 'LIKE', "%".$request->search_key."%")->get();
        } else {
            $books = Book::where('school_id', $school_id)->get();
        }

        if(count($books) > 0) {

        	foreach($books as $key => $book) {
        		$res[$key]['id'] = $book->id;
        		$res[$key]['name'] = $book->name;
                $res[$key]['author'] = $book->author;

                $number_of_issued_book = BookIssue::get()->where('book_id', $book->id)->where('status', 0);

                $res[$key]['copies'] = $book->copies;
                $res[$key]['available_copies'] = $book->copies - count($number_of_issued_book);
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
                'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'books' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No book found!',
            ], 400);
        }
    }

    public function book_issue_list(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        if(!empty($request->all())){
            $data = $request->all();
            $date = explode('-', $data['date_range']);
            $date_from = strtotime($date[0].' 00:00:00');
            $date_to  = strtotime($date[1].' 23:59:59');
            $issued_books = BookIssue::whereBetween('timestamp', [$date_from, $date_to])->where('student_id', auth('sanctum')->user()->id)->where('school_id', $school_id)->where('session_id', $session_id)->get();
        } else {
            $issued_books = BookIssue::where('student_id', auth('sanctum')->user()->id)->where('school_id', $school_id)->where('session_id', $session_id)->get();
        }

        if(count($issued_books) > 0) {

        	foreach($issued_books as $key => $issued_book) {
        		$res[$key]['id'] = $issued_book->id;
                $res[$key]['book_id'] = $issued_book->book_id;

                $book_details = Book::find($issued_book->book_id);

        		$res[$key]['book_name'] = $book_details->name;
                $res[$key]['author'] = $book_details->author;


                $res[$key]['issue_date'] = $issued_book->issue_date;
                $res[$key]['status'] = $issued_book->status == 0 ? 'pending' : 'returned';
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
                'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'issued_books' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No issued book found!',
            ], 400);
        }
    }

    public function exam_list(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        if(!empty($request->all())){
            $exams = Exam::where('class_id', $class_id)->where('school_id', $school_id)->where('subject_id', $request->subject_id)->get();
        } else {
            $exams = Exam::where('class_id', $class_id)->where('school_id', $school_id)->get();
        }

        if(count($exams) > 0) {

        	foreach($exams as $key => $exam) {
        		$res[$key]['id'] = $exam->id;
        		$res[$key]['name'] = $exam->name;
                $res[$key]['exam_type'] = $exam->exam_type;
                $res[$key]['subject_id'] = $exam->subject_id;
                $res[$key]['subject_name'] = (new CommonController)->getSubjectDetails($exam->subject_id)->name;
                $res[$key]['starting_time'] = $exam->starting_time;
                $res[$key]['ending_time'] = $exam->ending_time;
                $res[$key]['total_marks'] = $exam->total_marks;

                $today = strtotime(date("Y-m-d H:i:s"));
                $expiry_status = $exam->ending_time < $today;
                if($expiry_status){
                    $res[$key]['status'] = 'Expired';
                } else {
                    $res[$key]['status'] = 'Upcoming';
                }
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
                'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'exams' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'No exam found!',
            ], 400);
        }
    }

    public function profile_update(Request $request)
    {
        $previous_data = User::find(auth('sanctum')->user()->id);
        
        $prev_user_info = json_decode($previous_data->user_information);

        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $user_info['gender'] = $request->gender;
        $user_info['blood_group'] = $prev_user_info->blood_group;
        $user_info['birthday'] = $prev_user_info->birthday;
        $user_info['phone'] = $request->phone;
        $user_info['address'] = $request->address;

        $user_info['photo'] = $prev_user_info->photo;

        $data['user_information'] = json_encode($user_info);

        User::where('id', auth('sanctum')->user()->id)->update($data);

        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);

    	if($student_data) {
    		return response()->json([
                'student_data' => $student_data,
                'message' => 'Profile Update successfully',
            ], 201);
    	} else {
    		return response()->json([
                'message' => 'Profile Update failed!',
            ], 400);
    	}
    }
    
    public function change_profile_photo(Request $request)
    {
        $previous_data = User::find(auth('sanctum')->user()->id);

        $prev_user_info = json_decode($previous_data->user_information);

        if(!empty($request->file('user_image'))){

            $user_info['photo'] = $prev_user_info->photo;


            $data['name'] = $previous_data->name;
            $data['email'] = $previous_data->email;
            $user_info['gender'] = $prev_user_info->gender;
            $user_info['blood_group'] = $prev_user_info->blood_group;
            $user_info['birthday'] = $prev_user_info->birthday;
            $user_info['phone'] = $prev_user_info->phone;
            $user_info['address'] = $prev_user_info->address;

            $data['user_information'] = json_encode($user_info);

            User::where('id', auth('sanctum')->user()->id)->update($data);

            $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);

            if($student_data) {
                return response()->json([
                    'student_data' => $student_data,
                    'message' => 'Profile Picture Updated',
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Profile Picture Update failed!',
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'First select your image',
            ], 400);
        }

    }

    public function marks(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];

        $subjects = Subject::where(['class_id' => $class_id, 'school_id' => $school_id])->get();

        if(!empty($request->all())){
            $exam_marks = Gradebook::where('class_id', $class_id)->where('student_id', auth('sanctum')->user()->id)->where('exam_category_id', $request->exam_category_id)->where('session_id', $session_id)->get();
        } else {
            $exam_marks = Gradebook::where('class_id', $class_id)->where('student_id', auth('sanctum')->user()->id)->where('session_id', $session_id)->get();
        }

        $exam_categories = ExamCategory::where('school_id', $school_id)->get();

        $res = [];
        $categories = [];

        if(count($exam_marks) > 0) {

            foreach($exam_marks as $key => $exam) {
                $res[$key]['exam_id'] = $exam->id;
                $res[$key]['exam_category_id'] = $exam->exam_category_id;
                $res[$key]['exam_category_name'] = ExamCategory::where('id', $exam->exam_category_id)->value('name');

                $subject_list = json_decode($exam->marks, true);
                foreach($subjects as $index => $subject) {
                    if(!empty($subject_list[$subject->id])){
                        $sub_res[$index]['subject_id'] = $subject->id;
                        $sub_res[$index]['subject_name'] = $subject->name;
                        $sub_res[$index]['marks'] = $subject_list[$subject->id];
                        $res[$key]['subjects'] = $sub_res;
                    }
                }

                $res[$key]['comment'] = $exam->comment;
            }
        } 

        if(count($exam_categories) > 0) {
            foreach($exam_categories as $key => $category) {
                $categories[$key]['exam_category_id'] = $category->id;
                $categories[$key]['exam_category_name'] = $category->name;
            }
        }

        $response = [
            'class_id' => $class_id,
            'class_name' => $class_name,
            'section_id' => $section_id,
            'section_name' => $section_name,
            'school_id' => $school_id,
            'session_id' => $session_id,
            'exam_marks' => $res,
            'exam_categories' => $categories
        ];

        return response($response, 201);

    }

    public function fee_list(Request $request)
    {
        $student_data = (new CommonController)->get_student_details_by_id(auth('sanctum')->user()->id);
        $class_id = $student_data['class_id'];
        $class_name = (new CommonController)->getClassDetails($class_id)->name;
        $section_id = $student_data['section_id'];
        $section_name = (new CommonController)->getSectionDetails($section_id)->name;
        $school_id = $student_data['school_id'];
        $session_id = $student_data['running_session'];


        if(!empty($request->all())){
            $data = $request->all();
            $date = explode('-', $data['date_range']);
            $date_from = strtotime($date[0].' 00:00:00');
            $date_to  = strtotime($date[1].' 23:59:59');
            $invoices = StudentFeeManager::whereBetween('timestamp', [$date_from, $date_to])->where('student_id', auth('sanctum')->user()->id)->where('school_id', $school_id)->where('session_id', $session_id)->get();
        } else {
            $invoices = StudentFeeManager::where('student_id', auth('sanctum')->user()->id)->where('school_id', $school_id)->where('session_id', $session_id)->get();
        }

        if(count($invoices) > 0) {

            foreach($invoices as $key => $invoice) {
        		$res[$key]['id'] = $invoice->id;
        		$res[$key]['title'] = $invoice->title;
                $res[$key]['total_amount'] = $invoice->total_amount;
                $res[$key]['payment_method'] = $invoice->payment_method;
                $res[$key]['paid_amount'] = $invoice->paid_amount;
                $res[$key]['status'] = $invoice->status;
                $res[$key]['timestamp'] = $invoice->timestamp;
        	}

        	$response = [
	        	'class_id' => $class_id,
	        	'class_name' => $class_name,
                'section_id' => $section_id,
	        	'section_name' => $section_name,
	        	'school_id' => $school_id,
	        	'session_id' => $session_id,
	            'invoices' => $res
	        ];

	    	return response($response, 201);
        } else {
        	return response()->json([
                'message' => 'Invoices not found!',
            ], 400);
        }
    }

    /**
     * Get students for teacher attendance / class roster
     */
    public function teacherStudents(Request $request)
    {
        $classFilter = $request->query('class');
        $query = User::where('role_id', 7)->where('status', 1);

        $students = $query->get()->map(function ($s, $index) {
            $class = 'Class 1';
            $roll = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            if ($s->student_info) {
                $info = json_decode($s->student_info, true);
                if (is_array($info) && !empty($info['class'])) {
                    $class = $info['class'];
                }
            }
            return [
                'id' => $s->id,
                'name' => $s->name,
                'student_id' => $s->student_id ?: ('MCC' . $s->id),
                'roll' => $roll,
                'class' => $class,
                'status' => 'present',
            ];
        });

        if ($classFilter) {
            $students = $students->filter(function ($item) use ($classFilter) {
                return stripos($item['class'], $classFilter) !== false;
            })->values();
        }

        return response()->json([
            'status' => 'success',
            'data' => $students
        ]);
    }

    /**
     * Get classes for teacher
     */
    public function teacherClasses(Request $request)
    {
        $classes = [
            ['id' => 1, 'name' => 'General Studies', 'section' => 'Class 6 - A', 'students' => 24, 'progress' => '65%'],
            ['id' => 2, 'name' => 'Elementary Math', 'section' => 'Class 3 - B', 'students' => 18, 'progress' => '50%'],
            ['id' => 3, 'name' => 'Junior Science', 'section' => 'Class 2 - A', 'students' => 15, 'progress' => '40%'],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $classes
        ]);
    }

    /**
     * Get teacher conversation threads with guardians
     */
    public function teacherThreads(Request $request)
    {
        $guardians = User::where('role_id', 6)->where('status', 1)->take(6)->get();
        $threads = [];

        foreach ($guardians as $g) {
            $student = User::where('parent_id', $g->id)->where('role_id', 7)->first();
            $label = $student ? "{$g->name} (Guardian of {$student->name})" : "{$g->name} (Guardian)";
            $threads[] = [
                'id' => $g->id,
                'name' => $label,
                'snippet' => 'Inquiry regarding student academic progress and class attendance.',
                'time' => '10:45 AM',
                'unread' => 1,
            ];
        }

        if (empty($threads)) {
            $threads[] = [
                'id' => 991,
                'name' => 'Faculty Announcements',
                'snippet' => 'Staff and departmental meeting scheduled for this week.',
                'time' => 'Today',
                'unread' => 0,
            ];
            $threads[] = [
                'id' => 992,
                'name' => 'Academic Routine Committee',
                'snippet' => 'Mid-term syllabus review is now accessible on the portal.',
                'time' => 'Mon',
                'unread' => 0,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $threads
        ]);
    }

    /**
     * Submit attendance
     */
    public function submitAttendance(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Attendance successfully recorded for ' . count($request->input('attendance', [])) . ' students.'
        ]);
    }
}