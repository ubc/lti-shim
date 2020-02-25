@extends('layouts.basic')

@section('title', 'Authorization Response')

@section('content')
  <h3>Authorization Response</h3>
  <form action='{{ $auth_resp_url }}'
      method='post'>
    @foreach ($response as $key => $val)
      <div class='form-group'>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input class='form-control' type='text' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit' class='btn btn-primary'>
      Send Authorization Response
    </button>
  </form>
@endsection
