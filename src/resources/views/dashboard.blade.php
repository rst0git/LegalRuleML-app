@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">Dashboard</div>

                    <div class="panel-body">
                        <h4>
                            You are logged in!
                        </h4>
                        <ul class="dashboard">
                            <a href={{route('doc')}}>
                                <li>
                                    <span class="glyphicon glyphicon-list-alt"></span>
                                    <span class="dashboard-title">Browse documents</span>
                                </li>
                            </a>
                            <a href={{route('search')}}>
                                <li>
                                    <span class="glyphicon glyphicon-search"></span>
                                    <span class="dashboard-title">Search</span>
                                </li>
                            </a>
                            <a href={{route('doc_upload')}}>
                                <li>
                                    <span class="glyphicon glyphicon-upload"></span>
                                    <span class="dashboard-title">Upload new document</span>
                                </li>
                            </a>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
