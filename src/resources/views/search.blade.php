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
  <div class="container well">
    @if(!empty($data['query_results']))
        <ul>
            @foreach ($data['query_results'] as $result)
                <li>
                    <a href="{{ $result['url'] }}">{{ $result['name'] }}</a>:
                    {!! $result['html'] !!}
                </li>
            @endforeach
        </ul>
    @else
      <span>There are no results that match your search.</span>
    @endif
  </div>
@endsection
