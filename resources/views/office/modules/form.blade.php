@extends('office.layout')
@section('title', 'Add ' . lcfirst($module->get('label')))
@section('content')
<a class="back" href="{{ route('office.module', $module->key) }}">{{ $module->get('plural') }}</a>
<div class="page-head"><div><h1>Add {{ lcfirst($module->get('label')) }}</h1></div></div>
<section class="panel" style="max-width:820px">
    <form class="panel-body form-grid" method="post" action="{{ route('office.module.store', $module->key) }}" style="padding-top:22px" onsubmit="this.querySelector('[type=submit]').disabled = true">
        @csrf
        @if ($module->subject() === 'student')
            <div class="field wide"><label for="student">Student <span class="req" aria-hidden="true">*</span></label>
                <select id="student" name="student_id" required><option value="">Choose a student</option>@foreach ($students as $s)<option value="{{ $s->id }}" @selected((string) old('student_id', request('student_id')) === (string) $s->id)>{{ $s->name }} {{ $s->student_id }}</option>@endforeach</select>
            </div>
        @endif
        @if ($module->subject() === 'staff' || $module->has('staff_field'))
            <div class="field wide"><label for="staff">{{ $module->get('staff_field', 'Staff member') }}@if ($module->subject() === 'staff') <span class="req" aria-hidden="true">*</span>@endif</label>
                <select id="staff" name="staff_id" @required($module->subject() === 'staff')><option value="">Choose</option>@foreach ($staff as $id => $name)<option value="{{ $id }}" @selected((string) old('staff_id') === (string) $id)>{{ $name }}</option>@endforeach</select>
            </div>
        @endif
        @if ($module->has('parent_type'))
            <div class="field wide"><label for="parent">Route <span class="req" aria-hidden="true">*</span></label>
                <select id="parent" name="parent_id" required>@foreach ($parents as $id => $title)<option value="{{ $id }}">{{ $title }}</option>@endforeach</select>
            </div>
        @endif
        @foreach ($module->fields() as $field)
            @include('office.modules.field', ['field' => $field, 'value' => '', 'creating' => true])
        @endforeach
        <div class="wide form-actions">
            <a class="btn quiet" href="{{ route('office.module', $module->key) }}">Cancel</a>
            <button type="submit" class="btn">Save {{ lcfirst($module->get('label')) }}</button>
        </div>
    </form>
</section>
@endsection
