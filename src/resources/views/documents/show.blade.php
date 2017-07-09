@extends('layouts.app')

@section('content')
  @if($doc)
    <div class="row">
      <h3>{{ $doc->title }}</h3>
      <a class="btn btn-default btn-sm" href="/doc">
        <span class="glyphicon glyphicon-arrow-left"></span> Go Back
      </a>
      <a class="btn btn-default btn-sm" href="/doc/{{$doc->id}}/download">
        <span class="glyphicon glyphicon-download"></span> Download
      </a>
      {!! Form::open(['action'=>['DocumentsController@destroy', $doc->id], 'method'=>'POST', 'class'=>'pull-right']) !!}
          {!! Form::hidden('_method', 'DELETE') !!}
          {!! Form::submit('Delete', ['class'=> 'btn btn-sm btn-danger ']) !!}
      {!! Form::close() !!}
    </div>
    <hr />
    <div class="row">
      <p>{!! $doc->html !!}</p>
    </div>
  @endif
@endsection
