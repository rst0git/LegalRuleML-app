@extends('layouts.app')

@section('title', $data['doc']->title . ' document view')

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
      <div class="col-md-8 col-md-offset-2">
        {!! $data['html'] ?: $data['doc']->html !!}
      </div>
      <div class="col-md-2 hidden-sm hidden-xs">
       <ul class="nav nav-pills nav-stacked legend" data-spy="affix" data-offset-top="205">
            <li><span onclick="toggleBgColor(this);" class="ConstitutiveStatement">Constitutive</span></li>
            <li><span onclick="toggleBgColor(this);" class="FactualStatement">Factual</span></li>
            <li><span onclick="toggleBgColor(this);" class="PenaltyStatement">Penalty</span></li>
            <li><span onclick="toggleBgColor(this);" class="PrescriptiveStatement">Prescriptive
                <ul>
                    <li><span onclick="toggleBgColor(this);" class="Obligation">Obligation</span></li>
                    <li><span onclick="toggleBgColor(this);" class="Permission">Permission</span></li>
                    <li><span onclick="toggleBgColor(this);" class="Prohibition">Prohibition</span></li>
                </ul>
            </span></li>
            <li><span onclick="toggleBgColor(this);" class="ReparationStatement">Reparation</span></li>
          </ul>
      </div>
    </div>
  @endif
@endsection
