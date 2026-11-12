@extends('teacher.navigation')
@section('content')   
   
   @php
       $class_wise_attandance = array();
       $total_student = 0;
       $todays_total_attandance = 0;
       $all_classes = DB::table('classes')->where('school_id', auth()->user()->school_id)->get();
       $currently_session_id = DB::table('sessions')->where('status', 1)->value('id');
   
       foreach($all_classes as $class){
           $total_student += DB::table('enrollments')->where('session_id', $currently_session_id)->where('class_id', $class->id)->where('school_id', auth()->user()->school_id)->get()->count();
   
           $start_date = strtotime(date('d M Y'));
           $end_date = $start_date + 86400;
           $today_attanded = DB::table('daily_attendances')->where('class_id', $class->id)->where('timestamp', '>=', $start_date)->where('timestamp', '<', $end_date)->get();
           array_push($class_wise_attandance, array("class_name" => $class->name, "today_attended" => $today_attanded->count()));
           $todays_total_attandance += $today_attanded->count();
       }
   @endphp
   
       <!-- Mani section header and breadcrumb -->
       <div class="mainSection-title">
       <div class="row">
         <div class="col-12">
           <div
             class="d-flex justify-content-between align-items-center flex-wrap gr-15"
           >
             <div class="d-flex flex-column">
               <h4>{{ get_phrase('Dashboard') }}</h4>
               <ul class="d-flex align-items-center eBreadcrumb-2">
                 <li><a href="#">{{ get_phrase('Home') }}</a></li>
                 <li><a href="#">{{ get_phrase('Dashboard') }}</a></li>
               </ul>
             </div>
           </div>
         </div>
       </div>
       </div>
   
       <!-- Start Alerts -->
       <div class="row">
       <div class="col-12">
         <div class="eSection-dashboardItems">
           <div class="row flex-wrap">
            <div class="col-lg-12">
                <div class="dashboard_ShortListItem">
                        <h4 class="text-dark">{{ auth()->user()->name }}</h4>
                        <p>Welcome, to {{ DB::table('schools')->where('id', auth()->user()->school_id)->value('title') }}</p>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="dashboard_ShortListItem tp-today">
                    <div class="tp-today-head">
                        <div>
                            <p class="tp-kicker">{{ now()->format('l, d M Y') }}</p>
                            <h5 class="title mb-0">{{ get_phrase("Today's classes") }}</h5>
                        </div>
                        <span class="tp-count">{{ collect($todayClasses ?? [])->count() }} {{ collect($todayClasses ?? [])->count() === 1 ? 'period' : 'periods' }}</span>
                    </div>
                    @php
                        $slotOrder = ['ongoing' => 'Ongoing', 'upcoming' => 'Upcoming', 'earlier' => 'Earlier'];
                        $modeNotes = [
                            'in_class' => 'Take the period in the classroom',
                            'online' => 'Take this period online',
                            'postpone' => 'Move this period to another time',
                            'done' => 'This period is finished',
                        ];
                        $bySlot = collect($todayClasses ?? [])->groupBy('slot');
                    @endphp
                    @if(collect($todayClasses ?? [])->isEmpty())
                        <p class="tp-empty">No periods on your routine for today. The class routine is set from the admin panel.</p>
                    @endif
                    @foreach($slotOrder as $slotKey => $slotTitle)
                        @if(($bySlot[$slotKey] ?? collect())->isNotEmpty())
                            <p class="tp-slot">{{ $slotTitle }}</p>
                            @foreach($bySlot[$slotKey] as $period)
                                <button type="button" class="tp-period" data-bs-toggle="modal" data-bs-target="#classMode{{ $period['id'] }}">
                                    <span class="tp-time">{{ $period['start'] }}</span>
                                    <span class="tp-copy">
                                        <span class="tp-subject">{{ $period['subject'] ?: 'Class' }}</span>
                                        <span class="tp-meta">{{ collect([$period['class'], $period['room'], $period['end'] ? 'until '.$period['end'] : null])->filter()->implode(' · ') }}</span>
                                        @if($period['mode_label'])
                                            <span class="tp-mode tp-mode-{{ $period['mode'] }}">{{ $period['mode_label'] }}</span>
                                        @endif
                                    </span>
                                    <span class="tp-pill tp-pill-{{ $slotKey }}">{{ $period['slot_label'] }}</span>
                                </button>
                                <div class="modal fade" id="classMode{{ $period['id'] }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content tp-modal">
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title">{{ $period['subject'] ?: 'Class' }}</h5>
                                                    <p class="tp-modal-sub mb-0">{{ $period['start'] }} – {{ $period['end'] }} · {{ $period['slot_label'] }}</p>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="tp-prompt">How do you want to take this class?</p>
                                                @foreach(\App\Services\TeacherProfileService::CLASS_MODES as $value => $label)
                                                    <form method="POST" action="{{ route('teacher.class_status') }}">
                                                        @csrf
                                                        <input type="hidden" name="routine_id" value="{{ $period['id'] }}">
                                                        <input type="hidden" name="status" value="{{ $value }}">
                                                        <button type="submit" class="tp-choice {{ $period['mode'] === $value ? 'is-selected' : '' }}">
                                                            <span class="tp-choice-label">{{ $label }}</span>
                                                            <span class="tp-choice-note">{{ $modeNotes[$value] ?? '' }}</span>
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
            <style>
                .tp-today { padding-bottom: 8px; }
                .tp-today-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; }
                .tp-kicker { margin: 0 0 4px; font-size: 12px; font-weight: 700; letter-spacing: .03em; color: #C62828; }
                .tp-count { font-size: 12px; font-weight: 700; color: #666; }
                .tp-empty { margin: 14px 0 0; color: #666; font-size: 14px; }
                .tp-slot { margin: 18px 0 8px; font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #666; }
                .tp-period { display: flex; align-items: center; gap: 14px; width: 100%; text-align: left; margin-bottom: 8px; padding: 12px 14px; border: 1px solid #E8ECF1; border-radius: 14px; background: #fff; }
                .tp-period:hover { border-color: #C62828; }
                .tp-time { width: 78px; flex: none; font-weight: 800; color: #1A1A1A; }
                .tp-copy { flex: 1; min-width: 0; display: flex; flex-direction: column; }
                .tp-subject { font-weight: 800; color: #1A1A1A; }
                .tp-meta { margin-top: 2px; font-size: 13px; color: #666; }
                .tp-mode { align-self: flex-start; margin-top: 6px; border-radius: 999px; padding: 2px 8px; font-size: 11px; font-weight: 800; }
                .tp-mode-in_class { background: #FDECEC; color: #C62828; }
                .tp-mode-online { background: #E0F2FE; color: #0369A1; }
                .tp-mode-postpone { background: #FEF3C7; color: #92400E; }
                .tp-mode-done { background: #F3F4F6; color: #6B7280; }
                .tp-pill { flex: none; border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 800; }
                .tp-pill-ongoing { background: #FDECEC; color: #C62828; }
                .tp-pill-upcoming { background: #FEF3C7; color: #92400E; }
                .tp-pill-earlier { background: #F3F4F6; color: #6B7280; }
                .tp-modal { border: 0; border-radius: 18px; }
                .tp-modal-sub { margin-top: 4px; font-size: 13px; color: #666; }
                .tp-prompt { margin-bottom: 10px; font-size: 13px; font-weight: 700; color: #666; }
                .tp-choice { display: block; width: 100%; text-align: left; margin-bottom: 8px; padding: 12px 14px; border: 1px solid #E8ECF1; border-radius: 12px; background: #fff; }
                .tp-choice.is-selected { border-color: #C62828; background: #FDECEC; }
                .tp-choice-label { display: block; font-weight: 800; color: #1A1A1A; }
                .tp-choice.is-selected .tp-choice-label { color: #C62828; }
                .tp-choice-note { display: block; margin-top: 2px; font-size: 12px; color: #666; }
            </style>
             <!-- Dashboard Short Details -->
             <div class="col-lg-6">
               <div class="dashboard_ShortListItems">
                 <div class="row">
                   <div class="col-md-6">
                     <div class="dashboard_ShortListItem">
                       <div
                         class="dsHeader d-flex justify-content-between align-items-center"
                       >
                         <h5 class="title">{{ get_phrase('Students') }}</h5>
                       </div>
                       <div
                         class="dsBody d-flex justify-content-between align-items-center"
                       >
                         <div class="ds_item_details">
                           <h4 class="total_no">{{ DB::table('users')->where('role_id', 7)->where('school_id', auth()->user()->school_id)->get()->count() }}</h4>
                           <p class="total_info">{{ get_phrase('Total Student') }}</p>
                         </div>
                         <div class="ds_item_icon">
                           <img
                             src="{{ asset('assets/images/Student_icon.png') }}"
                             alt=""
                           />
                         </div>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-6">
                     <div class="dashboard_ShortListItem">
                       <div
                         class="dsHeader d-flex justify-content-between align-items-center"
                       >
                         <h5 class="title">{{ get_phrase('Teacher') }}</h5>
                       </div>
                       <div
                         class="dsBody d-flex justify-content-between align-items-center"
                       >
                         <div class="ds_item_details">
                           <h4 class="total_no">{{ DB::table('users')->where('role_id', 3)->where('school_id', auth()->user()->school_id)->get()->count() }}</h4>
                           <p class="total_info">{{ get_phrase('Total Teacher') }}</p>
                         </div>
                         <div class="ds_item_icon">
                           <img
                             src="{{ asset('assets/images/Teacher_icon.png') }}"
                             alt=""
                           />
                         </div>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-6">
                     <div class="dashboard_ShortListItem">
                       <div
                         class="dsHeader d-flex justify-content-between align-items-center"
                       >
                         <h5 class="title">{{ get_phrase('Parents') }}</h5>
                       </div>
                       <div
                         class="dsBody d-flex justify-content-between align-items-center"
                       >
                         <div class="ds_item_details">
                           <h4 class="total_no">{{ DB::table('users')->where('role_id', 6)->where('school_id', auth()->user()->school_id)->get()->count() }}</h4>
                           <p class="total_info">{{ get_phrase('Total Parent') }}</p>
                         </div>
                         <div class="ds_item_icon">
                           <img
                             src="{{ asset('assets/images/Parents_icon.png') }}"
                             alt=""
                           />
                         </div>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-6">
                     <div class="dashboard_ShortListItem">
                       <div
                         class="dsHeader d-flex justify-content-between align-items-center"
                       >
                         <h5 class="title">{{ get_phrase('Staff') }}</h5>
                       </div>
                       <div
                         class="dsBody d-flex justify-content-between align-items-center"
                       >
                        <div class="ds_item_details">
                          @php $admin = DB::table('users')->where('role_id', 2)->where('school_id', auth()->user()->school_id)->get()->count() @endphp
                          @php $teacher = DB::table('users')->where('role_id', 3)->where('school_id', auth()->user()->school_id)->get()->count() @endphp
                          @php $accountant = DB::table('users')->where('role_id', 4)->where('school_id', auth()->user()->school_id)->get()->count() @endphp
                          @php $librarian = DB::table('users')->where('role_id', 5)->where('school_id', auth()->user()->school_id)->get()->count() @endphp
                           <h4 class="total_no">{{ $admin + $teacher + $accountant + $librarian }}</h4>
                           <p class="total_info">{{ get_phrase('Total Staff') }}</p>
                        </div>
                        <div class="ds_item_icon">
                           <img
                             src="{{ asset('assets/images/Staff_icon.png') }}"
                             alt=""
                           />
                        </div>
                       </div>
                     </div>
                   </div>
                 </div>
               </div>
             </div>
             <!-- Imcome Report -->
   
             <!-- Upcoming Events -->
             <div class="col-md-6 ms-auto">
               <div class="dashboard_report dashboard_upcoming_events">
                 <div
                   class="ds_report_header d-flex justify-content-between align-items-start"
                 >
                   <div class="ds_report_left">
                     <h4 class="title">{{ get_phrase('Upcoming Events') }}</h4>
                   </div>
                   
                 </div>
                 <div class="ds_report_list pt-38">
                   <ul class="upcoming_events_items d-flex flex-column">
   
                       @php $upcoming_events = DB::table('frontend_events')->where('school_id', auth()->user()->school_id)->where('timestamp', '>', time())->where('status', 1)->take(3)->orderBy('id', 'DESC')->get(); @endphp
                       @foreach($upcoming_events as $upcoming_event)
                       <li>
                           <div
                           class="upcoming_events_item d-flex justify-content-between align-items-start"
                           >
                           <div class="events_info">
                               <a href="#" class="title">{{ $upcoming_event->title }}</a>
                               <p class="date">{{ date('D, M d Y', $upcoming_event->timestamp) }}</p>
                           </div>
                           
                           </div>
                       </li>
                       @endforeach
                   </ul>
                   <div class="text-end">
                     <a href="{{ route('teacher.events.list') }}" class="all_report_btn_2">{{ get_phrase('See all') }}</a>
                   </div>
                 </div>
               </div>
             </div>
           </div>
         </div>
       </div>
       </div>
   
   
   <!-- Resources -->
   <script src="{{asset('assets/amchart/index.js')}}"></script>
   <script src="{{asset('assets/amchart/xy.js')}}"></script>
   <script src="{{asset('assets/amchart/animated.js')}}"></script>
   
   <!-- Chart code -->
   <script>
   "use strict";
   
   am5.ready(function() {
   
   // Create root element
   var root = am5.Root.new("chartdiv");
   
   
   // Set themes
   root.setThemes([
     am5themes_Animated.new(root)
   ]);
   
   
   // Create chart
   var chart = root.container.children.push(am5xy.XYChart.new(root, {
     panX: true,
     panY: true,
     wheelX: "panX",
     wheelY: "zoomX",
     pinchZoomX:true
   }));
   
   // Add cursor
   var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
   cursor.lineY.set("visible", false);
   
   
   // Create axes
   var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
   xRenderer.labels.template.setAll({
     rotation: -90,
     centerY: am5.p50,
     centerX: am5.p100,
     paddingRight: 15
   });
   
   var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
     maxDeviation: 0.3,
     categoryField: "class_name",
     renderer: xRenderer,
     tooltip: am5.Tooltip.new(root, {})
   }));
   
   var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
     maxDeviation: 0.3,
     renderer: am5xy.AxisRendererY.new(root, {})
   }));
   
   
   // Create series
   var series = chart.series.push(am5xy.ColumnSeries.new(root, {
     name: "Series 1",
     xAxis: xAxis,
     yAxis: yAxis,
     valueYField: "today_attended",
     sequencedInterpolation: true,
     categoryXField: "class_name",
     tooltip: am5.Tooltip.new(root, {
       labelText:"{valueY}"
     })
   }));
   
   series.columns.template.setAll({ cornerRadiusTL: 5, cornerRadiusTR: 5 });
   series.columns.template.adapters.add("fill", function(fill, target) {
     return chart.get("colors").getIndex(series.columns.indexOf(target));
   });
   
   series.columns.template.adapters.add("stroke", function(stroke, target) {
     return chart.get("colors").getIndex(series.columns.indexOf(target));
   });
   
   
   // Set data
   var data = <?php echo json_encode($class_wise_attandance); ?>;
   
   xAxis.data.setAll(data);
   series.data.setAll(data);
   
   
   // Make stuff animate on load
   // https://www.amcharts.com/docs/v5/concepts/animations/
   series.appear(1000);
   chart.appear(1000, 100);
   
   }); // end am5.ready()
   </script>
   
   <!-- HTML -->
   
   
@endsection
