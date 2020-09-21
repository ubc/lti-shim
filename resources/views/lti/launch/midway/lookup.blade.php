@extends('layouts.basicvue')

@section('title', 'Midway Lookup Tool')

@section('content')
  <midway-main action='/lti/launch/midway/departure' method='post' class='mt-3'
      tool='{{ $tool }}' platform='{{ $platform }}'>
      <template #session>
          <div class='d-none'>
              <label for='lti_message_hint'>LTI Session Token</label>
              <input type='hidden' id='lti_message_hint'
                     name='lti_message_hint' value='{{ $lti_message_hint }}' />
          </div>
      </template>
      <template #users>
          <user-list :users='@json($users)' tool='{{ $tool }}'
              platform='{{ $platform }}'></user-list>
      </template>
  </midway-main>
@endsection
