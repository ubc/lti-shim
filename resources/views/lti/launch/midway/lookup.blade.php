@extends('layouts.midway')

@section('title', 'Midway Lookup Tool')

@section('content')
  <instructor-main-view action='/lti/launch/midway/departure' method='post'
                        class='mt-3'
                        course-context-id='{{ $courseContextId }}'
                        platform-name='{{ $platformName }}'
                        tool-name='{{ $toolName }}'
                        tool-id='{{ $toolId }}'
                        token='{{ $token }}'>
      <template #session>
          <div class='d-none'>
              <label for='lti_message_hint'>LTI Session Token</label>
              <input type='hidden' id='lti_message_hint'
                     name='lti_message_hint' value='{{ $lti_message_hint }}' />
          </div>
      </template>
  </instructor-main-view>
@endsection
