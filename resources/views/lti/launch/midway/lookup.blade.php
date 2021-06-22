@extends('layouts.midway')

@section('title', 'Midway Lookup Tool')

@section('content')
  <instructor-main-view action='{{ $midwayRedirectUri }}'
                        course-context-id='{{ $courseContextId }}'
                        :is-midway-only='@json($isMidwayOnly)'
                        platform-name='{{ $platformName }}'
                        tool-name='{{ $toolName }}'
                        tool-id='{{ $toolId }}'
                        token='{{ $token }}'
                        method='post'
                        class='mt-3'
                        >
      <template #redirect-params>
          <div class='d-none'>
              <div>{{ $midwayRedirectUri }}</div>
              @isset($state)
                  <label for='state'>State</label>
                  <input type='hidden' id='state'
                         name='state' value='{{ $state }}' />
              @endisset
              <label for='id_token'>ID Token</label>
              <input type='hidden' id='id_token'
                     name='id_token' value='{{ $id_token }}' />
          </div>
      </template>
  </instructor-main-view>
@endsection
