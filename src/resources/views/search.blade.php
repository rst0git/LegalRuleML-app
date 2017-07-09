@extends('layouts.app')

@section('content')
<h3>Search</h3>
<div class="well">
  {!!  Form::open(['action' => 'SearchController@search', 'method' => 'POST']) !!}
      <div class="form-group">
          {!! Form::text('search', '', ['class' => 'form-control', 'placeholder' => 'Search for ...']) !!}
      </div>
      <div class="form-group row">
        {{Form::label('statement', 'Statement Type:', ['class' => 'col-md-2 col-sm-3 control-label'])}}
        <div class="col-md-4 col-sm-6">
          {{Form::select('statement', $data['kinds'], 'ConstitutiveStatement', ['class' => 'form-control'])}}
        </div>
        <div class="col-md-2 col-sm-3 pull-right">
          {{ Form::submit('Search', ['class' => 'btn btn-block btn-primary']) }}
        </div>
      </div>
  {!! Form::close()!!}
</div>
    @if(!empty($data['query_result']))
      <div class="container well">
        {!! $data['query_result'] !!}
      </div>
    @endif
@endsection
