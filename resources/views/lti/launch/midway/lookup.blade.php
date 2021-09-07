@extends('layouts.midway')

@section('title', 'Midway Lookup Tool')

@section('content')
  <instructor-main-view action='{{ $midwayRedirectUri }}'
                        continuation-id-token='{{ $id_token }}'
                        continuation-state='{{ $state }}'
                        course-context-id='{{ $courseContextId }}'
                        :is-midway-only='@json($isMidwayOnly)'
                        platform-name='{{ $platformName }}'
                        tool-name='{{ $toolName }}'
                        tool-id='{{ $toolId }}'
                        token='{{ $token }}'>
  </instructor-main-view>
@endsection
