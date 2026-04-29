<?php if(count($exams) > 0): ?>
<table id="basic-datatable" class="table eTable">
    <thead>
        <tr>
            <th>#</th>
            <th scope="col"><?php echo e(get_phrase('Exam')); ?></th>
            <th scope="col"><?php echo e(get_phrase('Subject')); ?></th>
            <th scope="col"><?php echo e(get_phrase('Room Number')); ?></th>
            <th scope="col"><?php echo e(get_phrase('Starting Time')); ?></th>
            <th scope="col"><?php echo e(get_phrase('Ending Time')); ?></th>
            <th scope="col"><?php echo e(get_phrase('Total Marks')); ?></th>
            <th scope="col" class="text-center"><?php echo e(get_phrase('Action')); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $exams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exam): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php 
            $subject_name = App\Models\Subject::where('id', $exam->subject_id)->value('name'); 
            ?>
            <tr>
                <td><?php echo e($loop->index + 1); ?></td>
                <td><?php echo e($exam->name); ?></td>
                <td><?php echo e($subject_name); ?></td>
                <?php $class_room = DB::table('class_rooms')->find($exam->room_number); ?>
                <?php if(!empty($class_room)): ?>
                <td><?php echo e($class_room->name); ?></td>
                <?php else: ?>
                <td><?php echo e(get_phrase('No Room Found')); ?></td>
                <?php endif; ?>
                <td><?php echo e(date('d M Y - h:i A', $exam->starting_time)); ?></td>
                <td><?php echo e(date('d M Y - h:i A', $exam->ending_time)); ?></td>
                <td><?php echo e($exam->total_marks); ?></td>
                <td class="text-start">
                    <div class="adminTable-action">
                        <button
                          type="button"
                          class="eBtn eBtn-black dropdown-toggle table-action-btn-2"
                          data-bs-toggle="dropdown"
                          aria-expanded="false"
                        >
                          <?php echo e(get_phrase('Actions')); ?>

                        </button>
                        <ul
                          class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action"
                        >
                          <li>
                            <a class="dropdown-item" href="javascript:;" onclick="rightModal('<?php echo e(route('admin.edit.offline_exam', ['id' => $exam->id])); ?>', '<?php echo e(get_phrase('Edit Exam')); ?>')"><?php echo e(get_phrase('Edit')); ?></a>
                          </li>
                          <li>
                            <a class="dropdown-item" href="javascript:;" onclick="confirmModal('<?php echo e(route('admin.offline_exam.delete', ['id' => $exam->id])); ?>', 'undefined');"><?php echo e(get_phrase('Delete')); ?></a>
                          </li>
                        </ul>
                    </div>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
<?php else: ?>
<div class="empty_box center">
    <img class="mb-3" width="150px" src="<?php echo e(asset('assets/images/empty_box.png')); ?>" />
    <br>
    <span class=""><?php echo e(get_phrase('No data found')); ?></span>
</div>
<?php endif; ?><?php /**PATH /Applications/MAMP/htdocs/ekattor8_v2.2/Ekattor8/resources/views/admin/examination/exam_list.blade.php ENDPATH**/ ?>