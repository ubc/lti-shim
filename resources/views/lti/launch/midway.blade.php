@extends('layouts.basic')

@section('title', 'Midway Transfer Station')

@section('content')
  <h3>Midway Transfer</h3>

  <form action='/lti/launch/midway/departure' method='post'>
      <div class='form-group'>
        <label for='lti_message_hint'>LTI Session Token</label>
        <input class='form-control' type='text' id='lti_message_hint'
               name='lti_message_hint' value='{{ $lti_message_hint }}' />
      </div>

    <button type='submit' class='btn btn-primary'>
      Continue
    </button>
  </form>
@endsection
