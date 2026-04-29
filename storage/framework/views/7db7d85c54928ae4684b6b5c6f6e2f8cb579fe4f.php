   
<?php $__env->startSection('content'); ?>


<?php

$msg_user_lists = DB::table('users')
    ->join('message_thrades', function ($join) {
        $join->on('users.id', '=', 'message_thrades.sender_id')
            ->orWhere(function ($query) {
                $query->on('users.id', '=', 'message_thrades.reciver_id');
            });
    })
    ->join('chats', 'chats.message_thrade', '=', 'message_thrades.id')
    ->select('users.*', 'message_thrades.*', DB::raw('MAX(chats.id) as latest_chat_id'))
    ->where('message_thrades.school_id', auth()->user()->school_id)
    ->where(function($query) {
        $query->where('message_thrades.sender_id', auth()->user()->id)
              ->orWhere('message_thrades.reciver_id', auth()->user()->id);
    })
    ->where('users.id', '<>', auth()->user()->id)
    ->groupBy('message_thrades.id', 'users.id') // Group by message thread and user
    ->orderBy('latest_chat_id', 'desc') // Order by latest chat ID in descending order
    ->get();
        
?>


<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gr-15"
            >
                <div class="d-flex flex-column">
                    <h4><?php echo e(get_phrase('All Message')); ?></h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#"><?php echo e(get_phrase('Contact Us')); ?></a></li>
                        <li><a href="#"><?php echo e(get_phrase('All message')); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<main class="content">
    <div class="container p-0">
        <div class="card">
            <div class="row g-0">
                <div class="col-12 col-lg-5 col-xl-3 border-right">
                    <div class="px-4 d-none d-md-block">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <input type="text" id="search" class="form-control my-3" placeholder="Search...">
                            </div>
                        </div>

                        <div id="user-results" class="d-none">
                        </div>
                    </div>
                    <div class="chat_users">
						<?php $__currentLoopData = $msg_user_lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg_user_list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($msg_user_list->sender_id == auth()->user()->id || $msg_user_list->reciver_id == auth()->user()->id): ?>
							<a href="<?php echo e(route('parent.message.all_message', ['id' => $msg_user_list->id])); ?>" class="list-group-item list-group-item-action">
                                <?php
                                        $unreadCount = DB::table('chats')->where('message_thrade', $msg_user_list->id)
                                        ->where('read_status', 0)
                                        ->count();
                                        $single_chat = DB::table('chats')->where('message_thrade', $msg_user_list->id)->orderBy('id', 'desc')->first();
                                    ?>

                                    
                                    <?php if(!empty($single_chat->reciver_id)): ?>
                                        <?php if( $single_chat->reciver_id == auth()->user()->id): ?>
                                        <?php if($unreadCount != 0): ?>
                                        <div class="badge bg-success float-right">
                                            
                                                <?php echo e($unreadCount); ?>

                                        </div>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    
								<div class="d-flex align-items-start">
									<?php 
										$info = json_decode($msg_user_list->user_information);
										$user_image = $info->photo;
										if(!empty($info->photo)){
											$user_image = 'uploads/user-images/'.$info->photo;
										}else{
											$user_image = 'uploads/user-images/thumbnail.png';
										}
									?>
									<img src="<?php echo e(asset('assets')); ?>/<?php echo e($user_image); ?>" class="rounded-circle mr-1" alt="<?php echo e($msg_user_list->name); ?>" width="40" height="40">
									<div class="flex-grow-1 ml-3 user_list_username">
										<?php echo e($msg_user_list->name); ?>

									</div>
								</div>
							</a>
                        <?php endif; ?>
						<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <hr class="d-block d-lg-none mt-1 mb-0">
                    </div>
                </div>
                <div class="col-12 col-lg-7 col-xl-9">
                    <div class="chat_body">
                        <?php 
                                $info = json_decode($msg_user_details->user_information);
                                $user_image = $info->photo;
                                if(!empty($info->photo)){
                                    $user_image = 'uploads/user-images/'.$info->photo;
                                }else{
                                    $user_image = 'uploads/user-images/thumbnail.png';
                                }
                            ?>
                        <div class="py-2 px-4 border-bottom d-none d-lg-block">
                            <div class="d-flex align-items-center py-1">
                                <div class="position-relative">
                                    <img src="<?php echo e(asset('assets')); ?>/<?php echo e($user_image); ?>" class="rounded-circle mr-1"  width="40" height="40">
                                </div>
                                <div class="flex-grow-1 pl-3">
                                    <strong class="chat_user_name"><?php echo e($msg_user_details->name); ?></strong>
                                </div>
                                
                            </div>
                        </div>

                        <div class="position-relative">
                            <div class="chat-messages p-4" id="chat_messages_body">
                                <?php $__currentLoopData = $chat_datas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chat_data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($msg_user_details->thread_id ==  $chat_data->message_thrade): ?>
                                        <?php if( auth()->user()->id == $chat_data->sender_id): ?>
                                    
                                            <div class="chat-message-right pb-3" style="display: block;">
                                                <div class="flex-shrink-1">
                                                    <?php echo e($chat_data->message); ?>

                                                </div>
                                                <div class="text-muted small text-nowrap mt-2">
                                                    <?php if($chat_data->created_at->isToday()): ?>
                                                        <?php echo e($chat_data->created_at->format('h:i A')); ?> 
                                                    <?php else: ?>
                                                        <?php echo e($chat_data->created_at->format('d M Y h:i A')); ?>

                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($msg_user_details->user_id == $chat_data->sender_id): ?> 
                                        <div class="chat-message-left pb-3">
                                            <div>
                                                <img src="<?php echo e(asset('assets')); ?>/<?php echo e($user_image); ?>" class="rounded-circle mr-1" alt="<?php echo e($msg_user_list->name); ?>" width="40" height="40">
                                                <div class="text-muted small text-nowrap mt-2">
                                                    <?php if($chat_data->created_at->isToday()): ?>
                                                        <?php echo e($chat_data->created_at->format('h:i A')); ?>

                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="msg_text_body">
                                                <div class="flex-shrink-1 bg-light ">
                                                    <div class="font-weight-bold"></div>
                                                    <?php echo e($chat_data->message); ?>

                                                </div>
                                                <div class="text-muted small text-nowrap mt-2">
                                                    <?php if($chat_data->created_at->isToday()): ?>
                                                    <?php else: ?>
                                                        <?php echo e($chat_data->created_at->format('d M Y h:i A')); ?>

                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>  
                                <?php
                                            $unreadCount = DB::table('chats')->where('message_thrade', $msg_user_details->thread_id)
                                            ->where('read_status', 0)
                                            ->count();
                                            $single_chat = DB::table('chats')->where('message_thrade', $msg_user_details->thread_id)->orderBy('id', 'desc')->first();

                                            $info = json_decode($msg_user_details->user_information);
                                            $user_image = $info->photo;
                                            if(!empty($info->photo)){
                                                $user_image = 'uploads/user-images/'.$info->photo;
                                            }else{
                                                $user_image = 'uploads/user-images/thumbnail.png';
                                            }
                                        ?>
                                        
                                        <?php if(!empty($single_chat->sender_id)): ?>
                                            <?php if( $single_chat->sender_id == auth()->user()->id): ?>
                                            <?php if($unreadCount == 0): ?>
                                            <div class="seen_meesage">
                                                <img src="<?php echo e(asset('assets')); ?>/<?php echo e($user_image); ?>" alt="<?php echo e($msg_user_list->name); ?>" class="rounded-circle mr-1"  width="20" height="20">
                                            </div>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            
                                            <?php endif; ?>
                                        <?php endif; ?>                        
                            </div>
                        </div>

                        <div class="flex-grow-0 py-3 px-4 border-top">
                            <div class="chat_write_box">
                                <form action="<?php echo e(route('parent.message.chat_save')); ?>" method="post">
                                <?php echo csrf_field(); ?>
                                    <div class="input-group">
                                        <input type="hidden" name="reciver_id" value="<?php echo e($msg_user_details->user_id); ?>">
                                        <input type="hidden" name="message_thrade" value="<?php echo e($msg_user_details->thread_id); ?>">
                                        <textarea type="text" class="form-control chat_write_box_text_area" name="message" placeholder="Type your message"></textarea>
                                        
                                        <button class="btn chat_submit" type="submit">
                                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m12 18-7 3 7-18 7 18-7-3Zm0 0v-5"/>
                                            </svg>

                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                        
                </div>
            </div>
        </div>
    </div>
</main>


<script>

window.onload = function() {
            const messageContainer = document.getElementById("chat_messages_body");
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        };


    $(document).ready(function() {
        $('#search').on('keyup', function() {
            let query = $(this).val();

            if (query.trim() === '') {
                $('#user-results').addClass('d-none');
            } else {
                $('#user-results').removeClass('d-none');
                
                $.ajax({
                    url: "<?php echo e(route('parent.message.all_message', ['id' => $msg_user_details->thread_id])); ?>",
                    type: "GET",
                    data: { query: query },
                    success: function(data) {
                        $('#user-results').html(data);
                    }
                });
            }
        });

        $('#user-results').on('click', function() {
            $(this).removeClass('d-none'); 
        });
    });
</script>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('parent.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Applications/MAMP/htdocs/ekattor8_v2.2/Ekattor8/resources/views/parent/message/all_message.blade.php ENDPATH**/ ?>