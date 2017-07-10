@extends('layouts.app')

@section('content')
  @if($data['doc'])
    <div class="row">
      <h3>{{ $data['doc']->title }}</h3>
      <a class="btn btn-default btn-sm" href={{route('doc')}}>
        <span class="glyphicon glyphicon-arrow-left"></span> Go Back
      </a>
      <a class="btn btn-default btn-sm" href="{{route('doc_download', ['id' => $data['doc']->id])}}">
        <span class="glyphicon glyphicon-download"></span> Download
      </a>
      {!! Form::open(['action'=>['DocumentsController@destroy', $data['doc']->id], 'method'=>'POST', 'class'=>'pull-right']) !!}
          {!! Form::hidden('_method', 'DELETE') !!}
          {!! Form::submit('Delete', ['class'=> 'btn btn-sm btn-danger ']) !!}
      {!! Form::close() !!}
    </div>
    <hr />
    <div class="row">
      <p>{!! $data['html'] or $data['doc']->html !!}</p>
    </div>
  @endif
@endsection
