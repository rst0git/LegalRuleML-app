@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="jumbotron text-center">
        <h3>{{ config('app.name', 'LRML Search') }}</h3>
        <p>This is simple web application for browsing and searching a corpus of legal documents which are annotated with <a
                    href="https://www.oasis-open.org/committees/tc_home.php?wg_abbrev=legalruleml">LegalRuleML</a></p>
    </div>
@endsection
