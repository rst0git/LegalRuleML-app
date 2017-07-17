@extends('layouts.app')

@section('title', 'Upload document')

@section('content')

<div class="container" style="width:400px;">
    {!! Form::open(array('method'=>'POST', 'files'=>true)) !!}
      <div class="form-group">
        {{ Form::file('files[]', array('multiple'=>true)) }}
      </div>
      <div class="form-group" style="width:200px;">
        {{ Form::submit('Upload', ['class' => 'btn btn-block btn-primary']) }}
      </div>
    {!! Form::close() !!}
</div>

@endsection
