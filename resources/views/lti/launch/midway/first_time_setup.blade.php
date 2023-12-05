@extends('layouts.midway')

@section('title', 'Midway - Select Anonymization Option')

@section('content')
    <first-time-setup-view  action='{{ $midwayRedirectUri }}'
                            continuation-id-token='{{ $id_token }}'
                            continuation-state='{{isset($state) ? $state : ''}}'
                            :fake-user='@json($fakeUser)'
                            :is-midway-only='@json($isMidwayOnly)'
                            tool-name='{{ $toolName }}'
                            token='{{ $token }}'>
    </first-time-setup-view>
@endsection
