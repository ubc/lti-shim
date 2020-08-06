@extends('layouts.autosubmitform')

@section('title', 'Midway Transfer Station')

@section('content')
  <p>Working, please wait...</p>

  <form action='/lti/launch/midway/departure' method='post' id='autoSubmitForm'>
      <div>
        <label for='lti_message_hint'>LTI Session Token</label>
        <input type='hidden' id='lti_message_hint'
               name='lti_message_hint' value='{{ $lti_message_hint }}' />
      </div>

    <button type='submit'>
      Continue
    </button>
  </form>
@endsection
