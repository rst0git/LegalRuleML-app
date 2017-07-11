@extends('layouts.app')

@section('content')
<h3>Search</h3>
<div class="well">
  {!!  Form::open(['action' => 'SearchController@index', 'method' => 'GET']) !!}
      <div class="form-group">
          <label>Statements containing terms (leave blank to find all):</label>
          {!! Form::text('search', '', ['class' => 'form-control', 'placeholder' => 'Search for ...']) !!}
          <label>{!! Form::checkbox('advanced', 'advanced'); !!} Enable Advanced Search</label>
           <a href="#" onclick="$('#advanced_search_info').slideToggle();">
             <span class="glyphicon glyphicon-info-sign"></span>
           </a>
      </div>
      <div class="form-group row">
        {{Form::label('statement', 'Statement Type:', ['class' => 'col-md-2 col-sm-3 control-label'])}}
        <div class="col-md-4 col-sm-6">
          {{Form::select('statement', $data['kinds'], '', ['class' => 'form-control'])}}
        </div>
        <div class="col-md-4 col-sm-6">
          {{Form::select('deontic_operator', $data['operator_kinds'], '', ['class' => 'form-control'])}}
        </div>
        <div class="col-md-2 col-sm-3 pull-right">
          {{ Form::submit('Search', ['class' => 'btn btn-block btn-primary']) }}
        </div>
      </div>
  {!! Form::close()!!}
  <div class="row" id="advanced_search_info" style="display: none;">
    <div class="col-md-12">
      <h4>Advanced search examples</h4>
      <ul>
        <li><b>Find tokens which are not part of a longer token sequence:</b>
          <pre><code>'New' not in 'New York'</code></pre></li>
        <li><b>All strings need to be found:</b>
          <pre><code>{'Christian', 'Jewish'} all</code></pre></li>
        <li><b>Words are found in the specified order and results are returned if there are at most three words between <i>some</i> and <i>reason</i>.</b>
          <pre><code>{ 'some', 'reason' } all ordered distance at most 3 words</code></pre>
        </ul>
    </div>
  </div>
</div>
  @if (empty($data['no_search']))
    <div class="well">
      @if(!empty($data['query_results']))
          <ul id="search-results">
              @foreach ($data['query_results'] as $result)
                  <li>
                      <a href="{{ $result['url'] }}">{{ $result['name'] }}</a>:
                      <div class="excerpt">
                          {!! $result['html'] !!}
                      </div>
                  </li>
              @endforeach
          </ul>
      @elseif(!empty($data['query_error_message']))
        <div class="">
          <h5>The requested query could not be executed:</h5>
          <span>{{$data['query_error_message']}}</span>
        </div>
      @else
        <span>There are no results that match your search.</span>
      @endif
    </div>
  @endif
@endsection
