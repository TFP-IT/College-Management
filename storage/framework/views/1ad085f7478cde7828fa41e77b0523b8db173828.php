
   
<?php $__env->startSection('content'); ?>
<?php
    $menu_permissions = (empty($user->menu_permission) || $user->menu_permission == 'null') ? array():json_decode($user->menu_permission, true);

    $info = json_decode($user->user_information);
    $user_image = $info->photo;
    if(!empty($info->photo)){
        $user_image = 'uploads/user-images/'.$info->photo;
    }else{
        $user_image = 'uploads/user-images/thumbnail.png';
    }

$routes = ['admin.admin' => get_phrase('Admin list'), 'admin.teacher' => get_phrase('Teacher list'), 'admin.accountant' => get_phrase('Accountant list'), 'admin.librarian' => get_phrase('Librarian list'), 'admin.parent' => get_phrase('Parent list'), 'admin.student' => get_phrase('Students list'), 'admin.teacher.permission' => get_phrase('Teacher Permission list'), 'admin.offline_admission.single' => get_phrase('Addmission'), 'admin.exam_category' => get_phrase('Exam Category'), 'admin.offline_exam' => get_phrase('Offline Exam'), 'admin.marks' => get_phrase('Marks'), 'admin.grade_list' => get_phrase('Grades'), 'admin.promotion' => get_phrase('Promotion'), 'admin.examination.admit_card_list' => get_phrase('Admit Card List'), 'admin.examination.admit_card_print' => get_phrase('Admit Card Print'), 'admin.daily_attendance' => get_phrase('Daily Attendance'), 'admin.class_list' => get_phrase('Class List'), 'admin.routine' => get_phrase('Class Routine'), 'admin.subject_list' => get_phrase('Subject List'), 'admin.gradebook' => get_phrase('Gradebooks'), 'admin.syllabus' => get_phrase('Syllabus'), 'admin.class_room_list' => get_phrase('Class Room'), 'admin.department_list' => get_phrase('Department'), 'admin.fee_manager.list' => get_phrase('Students Fee Manager'), 'admin.offline_payment_pending' => get_phrase('Offline Payment Request'), 'admin.expense.list' => get_phrase('Expense Manager'), 'admin.expense.category_list' => get_phrase('Expense Category'), 'admin.book.book_list' => get_phrase('Book List Manager'), 'admin.book_issue.list' => get_phrase('Book Issue Report'), 'admin.noticeboard.list' => get_phrase('Noticeboard'), 'admin.subscription' => get_phrase('Subscription'), 'admin.events.list' => get_phrase('Events'), 'admin.feedback.feedback_list' => get_phrase('Feedback'), 'admin.settings.school' => get_phrase('School Settings'), 'admin.settings.session_manager' => get_phrase('Session Manager'), 'admin.settings.payment' => get_phrase('Payment settings'), 'admin.profile' => get_phrase('My Account')];
                    
?>


<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4><?php echo e(get_phrase('Navigation Menu Settings')); ?></h4>
                <div class="export-btn-area">
                    <a href="<?php echo e(route('admin.admin')); ?>" class="export_btn"><i class="mx-2 bi bi-arrow-left"></i><?php echo e(get_phrase('Back')); ?></a>
                  </div>
            </div>
            
        </div>
    </div>
</div>

<div class="row d-flex justify-content-center">
    <div class="col-7">
        <div class="eSection-wrap">
            <div class="eMain">
                <div class="row">
                    <div class="col-6">
                        <div class="userImG d-flex justify-content-center mt-4">
                            <img width="40%" height="100px" src="<?php echo e(asset('assets')); ?>/<?php echo e($user_image); ?>" alt="" />
                        </div>
                        
                        <h4 class="mt-3 text-center"><?php echo e($user->name); ?></h4>
                        <p  class="text-center"><?php echo e($user->email); ?></p>
                        
                    </div>
                    <div class="col-6">
                        <div class="eForm-layouts">
                            <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="<?php echo e(route('admin.admin.menu_permission_update', ['id' => $user->id])); ?>">
                                <?php echo csrf_field(); ?> 

                                <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route_name => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="<?php echo e($route_name); ?>" name="permissions[]" value="<?php echo e($route_name); ?>" 
                                        <?php if(!empty($user->menu_permission) || $user->menu_permission != 'null'): ?> 
                                            <?php if(in_array($route_name, $menu_permissions)): ?>
                                                checked 
                                            <?php endif; ?>
                                        <?php else: ?>
                                            checked
                                        <?php endif; ?>
                                        />
                                        <label class="form-check-label" for="<?php echo e($route_name); ?>"><?php echo e($label); ?></label>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                
                                <div class="fpb-7 pt-2">
                                    <button class="btn-form" type="submit"><?php echo e(get_phrase('Update')); ?></button>
                                </div>
                            </form> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Applications/MAMP/htdocs/ekattor8_v2.2/Ekattor8/resources/views/admin/admin/menu_permission.blade.php ENDPATH**/ ?>