@extends('layouts.app')

@section('title')
    @if(!empty($data['no_search']))
        Search
    @else
        @if(!empty($data['statement']))
            {{ $data['statement'] }}s
        @else
            All statements
        @endif
        @if(!empty($data['deontic_operator']))
            containing {{ $data['deontic_operator'] }}s
        @endif
        @if(!empty($data['search']))
            containing “{{ $data['search'] }}”
        @endif
        search results
    @endif
@endsection

@section('content')
    <h3>Search</h3>
    <div class="well">
        {!!  Form::open(['action' => 'SearchController@index', 'method' => 'GET']) !!}
        <div class="form-group">
            <label>Statements containing terms (leave blank to find all):</label>
            {!! Form::text('search', '', ['class' => 'form-control', 'placeholder' => 'Search for ...', 'style'=> 'font-family: Gill Sans Extrabold, sans-serif;']) !!}
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
                        <pre><code>"tobacco" not in "tobacco product"</code></pre>
                    </li>
                    <li><b>All words in the string need to be found:</b>
                        <pre><code>"Scottish Ministers make the following Regulations" all words</code></pre>
                    </li>
                    <li><b>Any word contained in the string need to be found:</b>
                        <pre><code>"Scottish Ministers make the following Regulations" any word</code></pre>
                    </li>
                    <li><b>All strings need to be found:</b>
                        <pre><code>{"Scottish Ministers", "make", "Regulations"} all</code></pre>
                    </li>
                    <li><b>Words are found in the specified order and results are returned if there are at most eight
                            words between <i>tobacco</i> and <i>regulations</i>.</b>
                        <pre><code>'tobacco regulations' all words ordered distance at most 8 words</code></pre>
                    </li>
                    <li><b>The <code>occurs</code> keyword comes into play when more than one occurrence of a token is
                            to be found. Varius range modifiers are available: <code>exactly</code>, <code>at
                                least</code>, <code>at most</code>, and <code>from ... to ....</code> </b>
                        <pre><code>"act" occurs at least 4 times</code></pre>
                    </li>
                    <li><b>The keywords <code>ftand</code>, <code>ftor</code> and <code>ftnot</code> can also be used to
                            combine multiple query terms.</b>
                        <pre><code>{ 'Medical', 'regulations' } all ordered distance at most 3 words</code></pre>
                    </li>
                    <li><b>The <code>window</code> keyword accepts those texts in which all keyword occur within the
                            specified number of tokens.</b>
                        <pre><code>{ 'tobacco', 'regulations' } all window 7 words</code></pre>
                    </li>
                    <li><b>Sometimes it is interesting to only select texts in which all searched terms occur in the
                            <code>same sentence</code> or <code>paragraph</code>.</b>
                        <pre><code>{ 'sale of tobacco', 'regulations' }  all words same sentence</code></pre>
                    </li>
                    <li><b>If case is insensitive, no distinction is made between characters in upper and lower case. By
                            default, the option is <code>insensitive</code>; it can also be set to
                            <code>sensitive</code>.</b>
                        <pre><code>{ 'sale of tobacco', 'regulations' } all words using case sensitive same sentence</code></pre>
                    </li>
                    <li><b>If <code>stemming</code> is activated, words are shortened to a base form by a
                            language-specific stemmer.</b>
                        <pre><code>"saling tobacco" all words using stemming same paragraph</code></pre>
                    </li>
                    <li><b>The wildcards option facilitates search operations similar to simple regular expressions:</b>
                        <ul>
                            <li><code>.</code> matches a single arbitrary character.</li>
                            <li><code>.?</code> matches either zero or one character.</li>
                            <li><code>.*</code> matches zero or more characters.</li>
                            <li><code>.+</code> matches one or more characters.</li>
                            <li><code>.{min,max}</code> matches min–max number of characters.</li>
                        </ul>
                        <pre><code>"Regulations 2017 and come into force on .* April 2017" using wildcards</code></pre>
                    </li>
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
