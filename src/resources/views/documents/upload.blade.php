@extends('layouts.app')

@section('title', 'Upload documents')

@section('content')

    <div class="container" style="width:400px;">
        {!! Form::open(array('method'=>'POST', 'action' => 'DocumentsController@store', 'files'=>true)) !!}
        <div class="form-group">
            {{ Form::file('files[]', array('multiple'=>true)) }}
        </div>
        <div class="form-group" style="width:200px;">
            {{ Form::submit('Upload', ['class' => 'btn btn-block btn-primary']) }}
        </div>
        {!! Form::close() !!}
    </div>

@endsection
