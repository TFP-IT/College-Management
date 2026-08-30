@extends('frontend.layouts.app')

@section('title', 'Academic Calendar | Maestro Crown College')

@section('content')
<div class="page-header">
    <div class="container">
        <h1>Academic Calendar</h1>
        <p>Stay updated with the important dates and events for the academic year.</p>
    </div>
</div>

<div class="page-content">
    <div class="container">
        <div class="content-card">
            <h2 style="color: var(--navy); border-bottom: 2px solid var(--gold); padding-bottom: 10px; margin-bottom: 30px;">Academic Calendar – 2026</h2>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: var(--navy); color: var(--white);">
                            <th style="padding: 12px 16px; text-align: left; border: 1px solid var(--gray-lt);">Month</th>
                            <th style="padding: 12px 16px; text-align: left; border: 1px solid var(--gray-lt);">Event / Activity</th>
                            <th style="padding: 12px 16px; text-align: left; border: 1px solid var(--gray-lt);">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background: var(--off-white);">
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">January</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">New Session Begins / Admission Starts</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">1st – 15th Jan</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">February</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">International Mother Language Day / Cultural Program</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">21st Feb</td>
                        </tr>
                        <tr style="background: var(--off-white);">
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">March</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Independence Day / 1st Term Exam</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">26th Mar</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">April</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Bengali New Year Celebration</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">14th Apr</td>
                        </tr>
                        <tr style="background: var(--off-white);">
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">May – June</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Half-Yearly Exam / Ramadan Vacation</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">As per Board Schedule</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">July – August</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Sports Week / Science Fair / 2nd Term Exam</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Mid July – End Aug</td>
                        </tr>
                        <tr style="background: var(--off-white);">
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">September</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Test Examination (Class XII)</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">1st – 20th Sep</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">October</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Durga Puja Vacation / Pre-Board Review</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">As per calendar</td>
                        </tr>
                        <tr style="background: var(--off-white);">
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">November</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Annual Exam / Final Assessment</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">1st – 25th Nov</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt); font-weight: 600;">December</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">Result Publication / Victory Day / Winter Break</td>
                            <td style="padding: 10px 16px; border: 1px solid var(--gray-lt);">16th Dec – 31st Dec</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: var(--off-white); border-radius: 8px; border: 1px solid var(--gray-lt);">
                <p style="margin: 0; color: var(--gray-dk); font-size: 0.9rem;"><strong>Note:</strong> Dates are subject to change as per government and Dhaka Education Board directives. Please check the <a href="{{ route('frontend.notice_board') }}" style="color: var(--navy); font-weight: 600;">Notice Board</a> regularly for updates.</p>
            </div>
        </div>
    </div>
</div>
@endsection
