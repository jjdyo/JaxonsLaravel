{{--
    Reusable alerts partial
    Usage: @include('partials.alerts')
    Renders:
    - session('success') as a green success alert
    - session('error') as a red danger alert
    - $errors bag as a list of errors
--}}

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
