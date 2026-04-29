   
<?php $__env->startSection('content'); ?>
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gr-15"
            >
                <div class="d-flex flex-column">
                    <h4><?php echo e(get_phrase('Admit Card')); ?></h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#"><?php echo e(get_phrase('Home')); ?></a></li>
                        <li><a href="#"><?php echo e(get_phrase('Examination')); ?></a></li>
                        <li><a href="#"><?php echo e(get_phrase('Admit Card')); ?></a></li>
                    </ul>
                </div>
                <div class="export-btn-area">
                    <a href="javascript:;" class="export_btn" onclick="rightModal('<?php echo e(route('admin.feedback.create_feedback')); ?>', '<?php echo e(get_phrase('Create Feedback')); ?>')"><i class="bi bi-plus"></i><?php echo e(get_phrase('Add New Feedback')); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Applications/MAMP/htdocs/ekattor8_v2.2/Ekattor8/resources/views/admin/examination/make_admit_card.blade.php ENDPATH**/ ?>