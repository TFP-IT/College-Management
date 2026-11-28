{{-- The teacher's periods for today, with the way each one is being taken. --}}
<section class="mcc-panel tp-today">
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
                                        <span class="tp-meta">{{ collect([$period['class'], $period['room'], $period['end'] ? 'until '.$period['end'] : null])->filter()->implode(', ') }}</span>
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
                                                    <p class="tp-modal-sub mb-0">{{ $period['start'] }} to {{ $period['end'] }}, {{ $period['slot_label'] }}</p>
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
                </section>
