   
<?php $__env->startSection('content'); ?>

<?php

use App\Models\User;
use App\Models\Section;
use App\Http\Controllers\CommonController;

?>

<style>
    .admit-card::before {
        content: "";
        background: url('<?php echo e(asset('assets/uploads/school_logo/'.DB::table('schools')->where('id', auth()->user()->school_id)->value('school_logo') )); ?>') no-repeat center;
        background-size: 60%;
        opacity: 0.1;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 0;
    }
</style>

<div class="mainSection-title">
    <div class="row">
      <div class="col-12">
        <div
          class="d-flex justify-content-between align-items-center flex-wrap gr-15"
        >
          <div class="d-flex flex-column">
            <h4><?php echo e(get_phrase('Print Admit Card')); ?></h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
              <li><a href="#"><?php echo e(get_phrase('Home')); ?></a></li>
              <li><a href="#"><?php echo e(get_phrase('Examination')); ?></a></li>
              <li><a href="#"><?php echo e(get_phrase('Admit Card')); ?></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
</div>


<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
             <div class="row">
                <form action="<?php echo e(route('admin.examination.admitCardFilter')); ?>">
                    <div class="att-filter d-flex flex-wrap">
                        <div class="att-filter-option">
                            <select class="form-select eForm-select eChoice-multiple-with-remove" id = "admit_card_id" name="admit_card_id">
                                <option value=""><?php echo e(get_phrase('Select category')); ?></option>
                                <?php $__currentLoopData = $admit_cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admit_card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($admit_card->id); ?>"  <?php echo e($selected_admit_card->id == $admit_card->id ?  'selected':''); ?>><?php echo e($admit_card->template); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="att-filter-option">
                            <select name="class_id" id="class_id" class="form-select eForm-select eChoice-multiple-with-remove" onchange="classWiseSection(this.value)" required>
                                <option value=""><?php echo e(get_phrase('Select a class')); ?></option>
                                <?php foreach($classes as $class): ?>
                                    <option value="<?php echo e($class['id']); ?>" <?php echo e($page_data['class_id'] == $class['id'] ?  'selected':''); ?>><?php echo e($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            </div>

                            <div class="att-filter-option">
                            <select name="section_id" id="section_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                                <option value=""><?php echo e(get_phrase('Select a section')); ?></option>
                                <?php $sections = Section::where(['class_id' => $page_data['class_id']])->get(); ?>
                                <?php foreach($sections as $section): ?>
                                    <option value="<?php echo e($section['id']); ?>" <?php echo e($page_data['section_id'] == $section['id'] ?  'selected':''); ?>><?php echo e($section['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            </div>
                        
                        <div class="att-filter-option">
                            <select name="session_id" id="session_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                                <option value=""><?php echo e(get_phrase('Select a session')); ?></option>
                                <?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($session->id); ?>" <?php echo e($page_data['session_id'] == $session['id'] ?  'selected':''); ?>><?php echo e($session->session_title); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        
                        <div class="att-filter-btn">
                            <button type="submit" class="eBtn eBtn btn-secondary" onclick="filter_admit_card()"><?php echo e(get_phrase('Filter')); ?></button>
                        </div>
                    </div>
                </form>
                
                <div class="admitCardPrintBtn_f">
                    <div class="admitCardPrintBtn">
                        <button
                            class="eBtn-3 dropdown-toggle"
                            type="button"
                            id="defaultDropdown"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="true"
                            aria-expanded="false"
                        >
                            <span class="pr-10">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="12.31"
                                height="10.77"
                                viewBox="0 0 10.771 12.31"
                            >
                                <path
                                id="arrow-right-from-bracket-solid"
                                d="M3.847,1.539H2.308a.769.769,0,0,0-.769.769V8.463a.769.769,0,0,0,.769.769H3.847a.769.769,0,0,1,0,1.539H2.308A2.308,2.308,0,0,1,0,8.463V2.308A2.308,2.308,0,0,1,2.308,0H3.847a.769.769,0,1,1,0,1.539Zm8.237,4.39L9.007,9.007A.769.769,0,0,1,7.919,7.919L9.685,6.155H4.616a.769.769,0,0,1,0-1.539H9.685L7.92,2.852A.769.769,0,0,1,9.008,1.764l3.078,3.078A.77.77,0,0,1,12.084,5.929Z"
                                transform="translate(0 12.31) rotate(-90)"
                                fill="#00a3ff"
                                />
                            </svg>
                            </span>
                            <?php echo e(get_phrase('Export')); ?>

                        </button>
                        <ul
                            class="dropdown-menu dropdown-menu-end eDropdown-menu-2"
                        >
                            <li>
                                <a class="dropdown-item" id="pdf" href="javascript:;" onclick="Export()"><?php echo e(get_phrase('PDF')); ?></a>
                            </li>
                            <li>
                                <a class="dropdown-item" id="print" href="javascript:;" onclick="printableDiv('admit_card_body')"><?php echo e(get_phrase('Print')); ?></a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="admit_card_body" id="admit_card_body">
                    <?php $__currentLoopData = $enroll_students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enroll_student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                    <?php
                            $id = $enroll_student->user_id;

                            $student_details = (new CommonController)->get_student_details_by_id($id);

                    ?>
                        <div class="admit-card">
                            <div class="d-flex justify-content-between align-items-center">
                            <?php if(empty($school_data->school_logo)): ?>
                                <img class="header-logo" src="<?php echo e(asset('assets/uploads/school_logo/'.DB::table('schools')->where('id', auth()->user()->school_id)->value('school_logo') )); ?>">
                            <?php else: ?>
                                <img class="header-logo" src="<?php echo e(asset('assets')); ?>/images/id_logo.png">
                            <?php endif; ?>
                                
                                <h3><?php echo e(DB::table('schools')->where('id', auth()->user()->school_id)->value('title')); ?></h3>
                                <?php if(empty($school_data->school_logo)): ?>
                                <img class="header-logo" src="<?php echo e(asset('assets/uploads/school_logo/'.DB::table('schools')->where('id', auth()->user()->school_id)->value('school_logo') )); ?>">
                            <?php else: ?>
                                <img class="header-logo" src="<?php echo e(asset('assets')); ?>/images/id_logo.png">
                            <?php endif; ?>
                            </div>
                            <h4 class="mt-3"><?php echo e($selected_admit_card->heading); ?></h4>
                            <p class="mt-3 mb-3"><strong><?php echo e($selected_admit_card->title); ?></strong></p>

                            <div class="d-flex justify-content-between">
                                <table class="table table-borderless info-table">
                                    <tr>
                                        <td><strong>ROLL NUMBER</strong></td>
                                        <td><?php echo e($student_details->code); ?></td>
                                        <td><strong><?php echo e(get_phrase('CLASS')); ?></strong></td>   
                                        <?php if(empty($student_details->class_name)): ?>
                                        <td><?php echo e(get_phrase('Removed')); ?> </td>
                                        <?php else: ?>
                                        <td><?php echo e(null_checker($student_details->class_name)); ?> </td>
                                        <?php endif; ?>
                                    </tr>
                                    <tr>
                                        <td><strong>CANDIDATE'S NAME</strong></td>
                                        <td><?php echo e($student_details->name); ?></td>
                                        <td><strong><?php echo e(get_phrase('SECTION')); ?></strong></td>
                                        <?php if(empty($student_details->section_name)): ?>
                                        <td><?php echo e(get_phrase('Removed')); ?> </td>
                                        <?php else: ?>
                                        <td><?php echo e(null_checker($student_details->section_name)); ?> </td>
                                        <?php endif; ?>
                                    </tr>
                                    <tr>
                                        <td><strong>D.O.B</strong></td>
                                        <td><?php echo e(date('d M Y',$student_details->birthday)); ?></td>
                                        
                                        <td><strong>GENDER</strong></td>
                                        <td><?php echo e(null_checker($student_details->gender)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>PARENTS NAME</strong></td>
                                        <td><?php echo e(null_checker($student_details->parent_name)); ?></td>
                                        <td><strong>CONTACT</strong></td>
                                        <td><?php echo e(null_checker($student_details->phone)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>ADDRESS</strong></td>
                                        <td colspan="3"><?php echo e(null_checker($student_details->address)); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>SCHOOL NAME</strong></td>
                                        <td><?php echo e(DB::table('schools')->where('id', auth()->user()->school_id)->value('title')); ?></td>
                                        <td><strong>EXAM CENTER</strong></td>
                                        <td><?php echo e($selected_admit_card->exam_center); ?></td>
                                    </tr>
                                </table>
                                <div class="student-image ml-3"><img src="<?php echo e($student_details->photo); ?>" alt=""></div>
                            </div>

                            <div class="signature">
                            <?php if($selected_admit_card->sign): ?>
                                <img src="<?php echo e(asset('assets/upload/user-docs/' . $selected_admit_card->sign)); ?>" alt="Signature" style="width: 150px; height: auto;">
                            <?php else: ?>
                                <p>No signature uploaded.</p>
                            <?php endif; ?>
                                <p>_________________________</p>
                                <p>Signature</p>
                            </div>
                        </div>

                    <br>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                    <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                        <p class="admin-tInfo"><?php echo e(get_phrase('Showing').' 1 - '.count($enroll_students).' '.get_phrase('from').' '.$enroll_students->total().' '.get_phrase('data')); ?></p>
                        <div class="admin-pagi">
                        <?php echo $enroll_students->appends(request()->all())->links(); ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
      "use strict";

    function Export() {
    const element = document.getElementById("admit_card_body");
    var opt = {
    margin:       1,
    filename:     'admit_card_' + new Date().toISOString().slice(0, 10) + '.pdf',
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2 }
    };

    html2pdf().set(opt).from(element).save();
    }


    function printableDiv(printableAreaDivId) {
    var printContents = document.getElementById(printableAreaDivId).innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;

    window.print();

    document.body.innerHTML = originalContents;
    }

    function classWiseSection(classId) {
        let url = "<?php echo e(route('admin.class_wise_sections', ['id' => ":classId"])); ?>";
        url = url.replace(":classId", classId);
        $.ajax({
            url: url,
            success: function(response){
                $('#section_id').html(response);
                classWiseSubect(classId);
            }
        });
    }

    function classWiseSubect(classId) {
        let url = "<?php echo e(route('admin.class_wise_subject', ['id' => ":classId"])); ?>";
        url = url.replace(":classId", classId);
        $.ajax({
            url: url,
            success: function(response){
                $('#subject_id').html(response);
            }
        });
    }

    function filter_admit_card(){
        var admit_card_id = $('#admit_card_id').val();
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();
        var session_id = $('#session_id').val();
        if(admit_card_id != "" &&  class_id != "" && section_id != "" && session_id != ""){
            getFilteredAdmitCard();
        }else{
            toastr.error('<?php echo e(get_phrase('Please select all the fields')); ?>');
        }
    }

    var getFilteredAdmitCard = function() {
        var admit_card_id = $('#admit_card_id').val();
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();
        var session_id = $('#session_id').val();
        if(admit_card_id != "" &&  class_id != "" && section_id!= "" && session_id != ""){
            let url = "<?php echo e(route('admin.examination.admitCardFilter')); ?>";
            $.ajax({
                url: url,
                headers: {
                    'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                },
                data: {admit_card_id: admit_card_id, class_id : class_id, section_id : section_id, session_id: session_id},
                
            });
        }
    }

</script>

<?php $__env->stopSection(); ?>



<?php echo $__env->make('admin.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Applications/MAMP/htdocs/ekattor8_v2.2/Ekattor8/resources/views/admin/examination/admitCardFilter.blade.php ENDPATH**/ ?>