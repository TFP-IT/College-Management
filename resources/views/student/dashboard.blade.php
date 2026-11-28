@extends('student.navigation')

@section('content')
    @include('portal.dashboard', ['dash' => app(\App\Services\PortalDashboard::class)->build(auth()->user())])
@endsection
