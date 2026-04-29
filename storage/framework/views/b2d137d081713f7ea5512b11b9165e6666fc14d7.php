<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="<?php echo e(route('admin.examination.admit_card_update', ['id' => $admitCardEdit->id])); ?>">
    <?php echo csrf_field(); ?> 

    <div class="fpb-7">
        <label for="template" class="eForm-label"><?php echo e(get_phrase('Template *')); ?></label>
        <input type="text" class="form-control eForm-control" id="template" name = "template" value="<?php echo e($admitCardEdit->template); ?>" required>
    </div>

    <div class="fpb-7">
        <label for="heading" class="eForm-label"><?php echo e(get_phrase('Heading')); ?></label>
        <input type="text" class="form-control eForm-control" id="heading" name = "heading" value="<?php echo e($admitCardEdit->heading); ?>" required>
    </div>

    <div class="fpb-7">
        <label for="title" class="eForm-label"><?php echo e(get_phrase('Title')); ?></label>
        <input type="text" class="form-control eForm-control" id="title" name = "title" value="<?php echo e($admitCardEdit->title); ?>" required>
    </div>

    <div class="fpb-7">
        <label for="exam_center" class="eForm-label"><?php echo e(get_phrase('Exam Center')); ?></label>
        <input type="text" class="form-control eForm-control" id="exam_center" name = "exam_center"value="<?php echo e($admitCardEdit->exam_center); ?>" required>
    </div>
    <div class="fpb-7">
        <label for="footer_text" class="eForm-label"><?php echo e(get_phrase('Footer Text')); ?></label>
        <input type="text" class="form-control eForm-control" id="footer_text" name = "footer_text" value="<?php echo e($admitCardEdit->footer_text); ?>" required>
    </div>

    <div class="fpb-7">
        <label for="sign" class="eForm-label"><?php echo e(get_phrase('Signature')); ?></label>
            <input class="form-control eForm-control-file" id="sign" name="sign" accept="image/*" type="file"
            />
    </div>


    <div class="fpb-7 pt-2">
        <button type="submit" class="btn-form"><?php echo e(get_phrase('Update')); ?></button>
    </div>
</form><?php /**PATH /Applications/MAMP/htdocs/ekattor8_v2.2/Ekattor8/resources/views/admin/examination/admit_card_edit.blade.php ENDPATH**/ ?>